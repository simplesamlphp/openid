<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Factories;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;

/**
 * @see \SimpleSAML\Test\OpenID\Factories\HttpClientDecoratorFactoryTest
 */
class HttpClientDecoratorFactory
{
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
     *        http). See https://docs.guzzlephp.org/en/stable/request-options.html
     * @param int $maxFetchSizeBytes Maximum response body size to read, regardless of which client is used.
     *        Enforced with a per request "sink" and "on_headers", which take precedence over client level
     *        defaults for those two options. So a "sink" or "on_headers" set in $httpClientConfig (or on a
     *        pre-built $client) does not apply to requests made through the decorator. Pass them per request
     *        instead, which for "sink" also opts that request out of the size cap. Guzzle features that swap
     *        the sink out mid transfer, notably digest "auth", likewise bypass the cap during their retries.
     */
    public function build(
        ?Client $client = null,
        array $httpClientConfig = [],
        int $maxFetchSizeBytes = HttpClientDecorator::DEFAULT_MAX_FETCH_SIZE_BYTES,
    ): HttpClientDecorator {
        if (!is_null($client)) {
            $this->logger?->info(
                'A pre-built HTTP client was supplied, so the library defaults for timeouts and redirect ' .
                'handling do not apply. The supplied client is expected to set equivalent options itself.',
            );

            return new HttpClientDecorator($client, $maxFetchSizeBytes);
        }

        if ($httpClientConfig === []) {
            return new HttpClientDecorator(maxFetchSizeBytes: $maxFetchSizeBytes);
        }

        $clientConfig = $this->buildClientConfig($httpClientConfig);

        $this->warnAboutWeakenedDefaults($clientConfig);

        return new HttpClientDecorator(
            /** @phpstan-ignore argument.type */
            new Client($clientConfig),
            $maxFetchSizeBytes,
        );
    }


    /**
     * @param array<string,mixed> $httpClientConfig
     * @return array<string,mixed>
     */
    protected function buildClientConfig(array $httpClientConfig): array
    {
        // The merge is shallow, so a caller supplying only some redirect keys would drop the rest.
        return HttpClientDecorator::withHardenedRedirectOptions(
            array_merge(HttpClientDecorator::DEFAULT_HTTP_CLIENT_CONFIG, $httpClientConfig),
        );
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
