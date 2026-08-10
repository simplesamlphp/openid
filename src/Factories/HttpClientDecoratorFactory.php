<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Factories;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Utils;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Network\AddressPinner;
use SimpleSAML\OpenID\Network\DestinationPolicy;
use Throwable;

/**
 * @see \SimpleSAML\Test\OpenID\Factories\HttpClientDecoratorFactoryTest
 */
class HttpClientDecoratorFactory
{
    /**
     * Prefix of the name the destination guard is pushed under. The policy is appended to it, so that
     * building twice from one handler stack leaves one guard per policy rather than two, while a stack
     * shared between policies keeps a guard for each.
     */
    public const DESTINATION_GUARD_MIDDLEWARE_NAME = 'openid_destination_guard';

    /**
     * Client options Guzzle feeds into its own handler selection. With any of them set, which handler the
     * client ended up with can no longer be worked out from the outside, so pinning is not claimed.
     *
     * @var list<string>
     */
    protected const HANDLER_SELECTION_OPTIONS = [
        'max_host_connections',
        'max_total_connections',
        'transport_sharing',
        'multiplex',
    ];


    /**
     * Whether Guzzle's own handler for this system is a cURL one. Settled once, since nothing about it
     * changes within a process.
     */
    protected ?bool $isCurlHandlerAvailable = null;


    /**
     * @param ?\Psr\Log\LoggerInterface $logger Used to report a configuration that undoes a hardening default.
     */
    public function __construct(
        protected readonly ?LoggerInterface $logger = null,
    ) {
    }


    /**
     * @param \GuzzleHttp\Client|null $client A pre-built client. If given, $httpClientConfig is ignored, and
     *        so is every hardening default in HttpClientDecorator::DEFAULT_HTTP_CLIENT_CONFIG: request and
     *        connect timeouts, and the restriction of redirects to at most 3 https hops. A client supplied
     *        here is expected to set equivalent options itself, otherwise a single unresponsive remote entity
     *        can occupy a worker indefinitely, which no resolution-wide budget can bound.
     * @param array<string,mixed> $httpClientConfig Guzzle client options merged OVER the defaults when no
     *        $client is supplied, so anything set here replaces the corresponding hardening default. Take
     *        particular care with "timeout" and "connect_timeout" (0 means no timeout in Guzzle) and with
     *        "allow_redirects" (true restores Guzzle's permissive defaults, which allow a downgrade to plain
     *        http). A "handler" set here has the destination guard attached to it: a handler stack has the
     *        middleware pushed onto it, which modifies that stack in place, and a bare callable handler is
     *        wrapped. See https://docs.guzzlephp.org/en/stable/request-options.html
     * @param int $maxFetchSizeBytes Maximum response body size to read, regardless of which client is used.
     *        Enforced with a per request "sink" and "on_headers", which take precedence over client level
     *        defaults for those two options. So a "sink" or "on_headers" set in $httpClientConfig (or on a
     *        pre-built $client) does not apply to requests made through the decorator. Pass them per request
     *        instead, which for "sink" also opts that request out of the size cap. Guzzle features that swap
     *        the sink out mid transfer, notably digest "auth", likewise bypass the cap during their retries.
     *        The same applies to "progress", which fetches made under a duration ceiling use to hold that
     *        ceiling across a redirect chain.
     * @param ?\SimpleSAML\OpenID\Network\DestinationPolicy $destinationPolicy Where outbound requests may be
     *        sent. Defaults to refusing every non-public destination, so pass one built with the deployment's
     *        own allowed hosts and ranges if it legitimately fetches from an internal address. Applied as
     *        middleware on the client built here, and therefore NOT to a pre-built $client: push
     *        $destinationPolicy->middleware() onto that client's own handler stack instead.
     */
    public function build(
        ?Client $client = null,
        array $httpClientConfig = [],
        int $maxFetchSizeBytes = HttpClientDecorator::DEFAULT_MAX_FETCH_SIZE_BYTES,
        ?DestinationPolicy $destinationPolicy = null,
    ): HttpClientDecorator {
        $destinationPolicy ??= new DestinationPolicy(logger: $this->logger);

        if (!is_null($client)) {
            $this->logger?->info(
                'A pre-built HTTP client was supplied, so the library defaults for timeouts and redirect ' .
                'handling do not apply. The supplied client is expected to set equivalent options itself.',
            );

            // Guarding it here would mean reaching into a handler stack owned by whoever built the client,
            // and every other client sharing that stack would silently be guarded too.
            $this->logger?->warning(
                'The outbound destination policy is not applied to a pre-built HTTP client. Push the ' .
                "middleware from DestinationPolicy::middleware() onto that client's handler stack, or let " .
                'this library build the client, otherwise its requests are not restricted to public ' .
                'destinations.',
            );

            // The decorator reads the timeout off the client itself, so a duration ceiling imposed later can
            // only ever shorten what this client was already configured with.
            return new HttpClientDecorator($client, $maxFetchSizeBytes);
        }

        // No early return for an empty configuration: that is the common case, and it is exactly the case
        // that must not end up with an unguarded client.
        $clientConfig = $this->buildClientConfig($httpClientConfig, $destinationPolicy);

        $this->warnAboutWeakenedDefaults($clientConfig);

        /** @phpstan-ignore argument.type */
        $client = new Client($clientConfig);

        if (!isset($clientConfig['handler'])) {
            $this->guardHandlerStackOf($client, $clientConfig, $destinationPolicy);
        }

        return new HttpClientDecorator($client, $maxFetchSizeBytes);
    }


    /**
     * Attach the guard to the handler stack Guzzle built for a client of ours.
     *
     * Done after construction rather than by handing Guzzle a handler, so that its own derivation of the
     * handler from the client configuration (connection caps, transport sharing, multiplexing) is left
     * intact. The stack belongs to a client this factory just built, so pushing onto it affects nothing else,
     * and nothing has been sent through it yet.
     *
     * @param array<string,mixed> $clientConfig
     */
    protected function guardHandlerStackOf(
        Client $client,
        array $clientConfig,
        DestinationPolicy $destinationPolicy,
    ): void {
        // Reading configuration back off a client is deprecated in Guzzle, but it is the only way to reach
        // the stack it built for itself.
        $handlerStack = $client->getConfig('handler');

        if (!$handlerStack instanceof HandlerStack) {
            $this->logger?->warning(
                'The outbound destination policy could not be attached to the HTTP client, because the ' .
                'handler Guzzle built for it is not a handler stack. Requests made through it are not ' .
                'restricted to public destinations.',
            );

            return;
        }

        $guardName = $this->guardNameFor($destinationPolicy);
        $handlerStack->remove($guardName);
        $handlerStack->push(
            $destinationPolicy->middleware(new AddressPinner($this->isCurlHandlerUsedFor($clientConfig))),
            $guardName,
        );
    }


    /**
     * Whether the client built from this configuration will make its requests through cURL.
     *
     * @param array<string,mixed> $clientConfig
     */
    protected function isCurlHandlerUsedFor(array $clientConfig): bool
    {
        // With any of these set, Guzzle picks the handler on more than this system's capabilities, and its
        // choice cannot be read back off the client. Fail closed rather than guess at it.
        foreach (self::HANDLER_SELECTION_OPTIONS as $option) {
            if (array_key_exists($option, $clientConfig)) {
                return false;
            }
        }

        return $this->isCurlHandlerAvailable();
    }


    /**
     * Whether Guzzle's own handler for this system is a cURL one, which is what decides whether a pin can
     * reach the connection.
     *
     * Established by asking Guzzle, rather than inferred from the extension being loaded: that stays true
     * where the cURL functions are disabled or libcurl was built without the SSL support Guzzle requires,
     * and Guzzle then quietly selects the stream handler, which ignores a cURL option. Its choice is a
     * stream handler exactly when no cURL handler could be made.
     */
    protected function isCurlHandlerAvailable(): bool
    {
        if (!is_null($this->isCurlHandlerAvailable)) {
            return $this->isCurlHandlerAvailable;
        }

        try {
            return $this->isCurlHandlerAvailable = !Utils::chooseHandler() instanceof StreamHandler;
        } catch (Throwable) {
            // No usable handler at all, which the client construction reports in its own way.
            return $this->isCurlHandlerAvailable = false;
        }
    }


    /**
     * @param array<string,mixed> $httpClientConfig
     * @return array<string,mixed>
     */
    protected function buildClientConfig(array $httpClientConfig, DestinationPolicy $destinationPolicy): array
    {
        // The merge is shallow, so a caller supplying only some redirect keys would drop the rest.
        $clientConfig = HttpClientDecorator::withHardenedRedirectOptions(
            array_merge(HttpClientDecorator::DEFAULT_HTTP_CLIENT_CONFIG, $httpClientConfig),
        );

        // A handler the caller named is guarded here. One they left to Guzzle is guarded afterwards, on the
        // stack Guzzle builds, so that its own derivation of the handler from the client configuration is
        // not taken away from it.
        if (isset($clientConfig['handler'])) {
            $clientConfig['handler'] = $this->guardCallerHandler($clientConfig['handler'], $destinationPolicy);
        }

        return $clientConfig;
    }


    /**
     * Attach the guard to a handler the caller named.
     *
     * Pushing onto a stack puts the guard below the redirect middleware, so it runs again for every hop. A
     * caller who supplied a bare callable as the handler has no middleware at all, redirects included, so
     * wrapping that callable guards everything it will be asked to do.
     *
     * A handler that is neither is handed back untouched, so that a configuration error keeps surfacing as
     * one when the client is constructed rather than quietly turning into the default live transport.
     */
    protected function guardCallerHandler(mixed $handler, DestinationPolicy $destinationPolicy): mixed
    {
        // Nothing is known about a transport that came from outside, and a pinner assumes nothing by
        // default, so this one reports every request through it as unpinnable.
        $middleware = $destinationPolicy->middleware(new AddressPinner());

        if ($handler instanceof HandlerStack) {
            // Building twice from one stack has to leave one guard for this policy on it, not two. The name
            // carries the policy, so a stack shared between policies keeps every one of their guards rather
            // than the newest quietly replacing an older and possibly narrower one.
            $guardName = $this->guardNameFor($destinationPolicy);
            $handler->remove($guardName);
            $handler->push($middleware, $guardName);

            return $handler;
        }

        if (is_callable($handler)) {
            // Guzzle uses a bare callable as the entire stack, so wrapping it is enough, and it adds none of
            // the middleware that a caller passing a bare handler deliberately did without.
            return $middleware($handler);
        }

        return $handler;
    }


    /**
     * The name one policy's guard is pushed under. Two policies on one stack are two guards, and a
     * destination then has to satisfy both, which is the safe way round.
     */
    protected function guardNameFor(DestinationPolicy $destinationPolicy): string
    {
        return self::DESTINATION_GUARD_MIDDLEWARE_NAME . '.' . spl_object_id($destinationPolicy);
    }


    /**
     * Caller options are merged over the defaults, so it is easy to turn a bound off without noticing. The
     * caller's intent is respected either way, but a configuration that removes a bound is worth reporting.
     *
     * @param array<string,mixed> $clientConfig The effective configuration, after merging over the defaults.
     */
    protected function warnAboutWeakenedDefaults(array $clientConfig): void
    {
        $logger = $this->logger;

        if (is_null($logger)) {
            return;
        }

        foreach ([RequestOptions::TIMEOUT, RequestOptions::CONNECT_TIMEOUT] as $timeoutOption) {
            $configuredTimeout = $clientConfig[$timeoutOption] ?? null;

            if (is_numeric($configuredTimeout) && $configuredTimeout > 0) {
                continue;
            }

            $logger->warning(
                sprintf(
                    'HTTP client option "%s" is set to %s, which disables the timeout. A single unresponsive ' .
                    'remote entity can then occupy a worker indefinitely.',
                    $timeoutOption,
                    var_export($configuredTimeout, true),
                ),
            );
        }

        $allowRedirects = $clientConfig[RequestOptions::ALLOW_REDIRECTS] ?? null;

        if ($allowRedirects === true) {
            $logger->warning(
                'HTTP client option "allow_redirects" is set to true, which restores Guzzle\'s permissive ' .
                'defaults. Redirects to plain http and to more hops than the library default are allowed again.',
            );

            return;
        }

        if (!is_array($allowRedirects)) {
            return;
        }

        $configuredMaxHops = $allowRedirects['max'] ?? null;
        $defaultMaxHops = HttpClientDecorator::DEFAULT_ALLOW_REDIRECTS_CONFIG['max'];

        if (is_numeric($configuredMaxHops) && $configuredMaxHops > $defaultMaxHops) {
            $logger->warning(
                sprintf(
                    'HTTP client option "allow_redirects" permits %s redirect hops, more than the library ' .
                    'default of %s. Each hop is another fetch a remote entity can make the deployment perform.',
                    var_export($configuredMaxHops, true),
                    $defaultMaxHops,
                ),
            );
        }

        $protocols = $allowRedirects['protocols'] ?? [];

        if (!is_array($protocols)) {
            return;
        }

        $nonHttpsProtocols = array_filter(
            $protocols,
            fn(mixed $protocol): bool => !is_string($protocol) || strtolower($protocol) !== 'https',
        );

        if ($nonHttpsProtocols === []) {
            return;
        }

        $logger->warning(
            'HTTP client option "allow_redirects" permits redirects to protocols other than https, which ' .
            'lets a remote entity downgrade the connection or point it at an internal address.',
        );
    }
}
