<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Decorators;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Exceptions\DestinationPolicyException;
use SimpleSAML\OpenID\Exceptions\HttpException;
use SimpleSAML\OpenID\Utils\SizeLimitedStream;
use Throwable;

/**
 * Note that where outbound requests are allowed to go is decided by the client, not by this decorator: the
 * destination policy is middleware on the handler stack. A decorator built around a client that this library
 * did not build (including the default one this class constructs) carries no such policy.
 *
 * @see \SimpleSAML\Test\OpenID\Decorators\HttpClientDecoratorTest
 * @see \SimpleSAML\OpenID\Factories\HttpClientDecoratorFactory
 */
class HttpClientDecorator
{
    /**
     * Tighter than Guzzle's own redirect defaults (max 5, both http and https, strict false).
     *
     * Federation endpoints are https only by specification, so allowing a redirect to plain http buys nothing
     * and lets an attacker controlled entity downgrade the connection or point it at an internal address.
     */
    public const DEFAULT_ALLOW_REDIRECTS_CONFIG = [
        'max' => 3,
        'protocols' => ['https'],
        'strict' => true,
        'referer' => false,
    ];

    /**
     * The request timeout is chosen against the Trust Chain resolution deadline rather than on its own: a
     * resolution is many fetches, and only as many slow ones fit as the deadline divided by this. One chain
     * costs about five fetches (three entity configurations and two subordinate statements), so a timeout
     * near a third of the deadline would fail a merely sluggish federation before a single chain resolved.
     *
     * Federation artifacts are small signed documents that a healthy endpoint serves well inside a second,
     * so this stays generous by an order of magnitude either way. An endpoint that is simply dark is dealt
     * with by the connect timeout instead, without the request timeout having to be short.
     */
    public const DEFAULT_HTTP_CLIENT_CONFIG = [
        RequestOptions::ALLOW_REDIRECTS => self::DEFAULT_ALLOW_REDIRECTS_CONFIG,
        RequestOptions::CONNECT_TIMEOUT => 3,
        RequestOptions::TIMEOUT => 5,
        RequestOptions::HTTP_ERRORS => true,
    ];

    public const DEFAULT_MAX_FETCH_SIZE_BYTES = 102400;

    protected const BODY_READ_CHUNK_SIZE_BYTES = 8192;

    /**
     * Floor for a computed timeout ceiling. Guzzle reads 0 as "no timeout", so a budget that has run down to
     * nothing must not be allowed to turn into an unbounded request.
     */
    protected const MIN_REQUEST_TIMEOUT_SECONDS = 0.001;

    /**
     * How far to follow an exception chain when looking for the cause underneath. Bounded so that a chain
     * that loops back on itself can not hang the search.
     */
    protected const MAX_EXCEPTION_CHAIN_DEPTH = 10;


    protected int $maxFetchSizeBytes;

    /**
     * The request timeout the client is known to be configured with, or null when it can not be known (a
     * pre-built client, whose configuration belongs to whoever built it).
     */
    protected ?float $requestTimeout;


    public function __construct(
        public readonly Client $client = new Client(self::DEFAULT_HTTP_CLIENT_CONFIG),
        int $maxFetchSizeBytes = self::DEFAULT_MAX_FETCH_SIZE_BYTES,
        ?float $requestTimeout = null,
    ) {
        $this->maxFetchSizeBytes = max(1, $maxFetchSizeBytes);
        $this->requestTimeout = $this->resolveRequestTimeout($requestTimeout);
    }


    /**
     * Falls back to reading the timeout off the client, so that a duration ceiling imposed later can only ever
     * shorten what the client was already configured with, never lengthen it.
     *
     * Reading configuration back off a client is deprecated in Guzzle, but it is the only way to see a timeout
     * belonging to a client built elsewhere. A timeout that cannot be established, or one that is disabled, is
     * reported as unknown so that it can never act as a ceiling of zero.
     */
    protected function resolveRequestTimeout(?float $requestTimeout): ?float
    {
        $configuredTimeout = $requestTimeout ?? $this->client->getConfig(RequestOptions::TIMEOUT);

        return (is_numeric($configuredTimeout) && $configuredTimeout > 0) ? (float)$configuredTimeout : null;
    }


    public function getMaxFetchSizeBytes(): int
    {
        return $this->maxFetchSizeBytes;
    }


    public function getRequestTimeout(): ?float
    {
        return $this->requestTimeout;
    }


    /**
     * Request options that stop a single request from running longer than the given number of seconds.
     *
     * Meant for callers that hold a budget for a whole operation: a per-request timeout on the client cannot
     * bound an operation made of many requests, and a client supplied by the consumer may carry no timeout at
     * all. Per-request options take precedence over client configuration for every client, so this bounds the
     * request whatever the client was built with.
     *
     * The known client timeout still wins when it is the smaller of the two, so this only ever tightens.
     *
     * Note on redirects: Guzzle applies "timeout" to each hop separately, so on a chain it would bound one
     * hop rather than the fetch, handing every further hop a fresh allowance. The callbacks below close that
     * by cutting the fetch as a whole once the allowance is spent, redirects included. The progress callback
     * also covers a hop that stalls without ever sending headers, though only where the handler reports
     * progress, as cURL does. Behind a handler that reports none, a chain can still outlast the allowance by
     * up to one hop. Configure "allow_redirects" more tightly (or off) where that matters.
     *
     * @return array<string,mixed>
     */
    public function timeoutCeilingOptions(float $deadlineTimestamp): array
    {
        // Taken as an absolute point in time rather than a duration, so that whatever happened between the
        // caller working out its budget and the request going out (a cache lookup, say) is already accounted
        // for, instead of the request starting a fresh allowance of a duration decided earlier.
        // One reading, used for both the allowance and the deadline built from it. Reading the clock twice
        // would add whatever passed in between back onto the allowance.
        $now = microtime(true);
        $maxDurationSeconds = max(self::MIN_REQUEST_TIMEOUT_SECONDS, $deadlineTimestamp - $now);
        $timeout = $this->perHopTimeout($maxDurationSeconds);

        // What one fetch may take in total, redirects included, and never past the caller's own deadline.
        $fetchDeadlineTimestamp = min($deadlineTimestamp, $now + $timeout);

        return [
            RequestOptions::TIMEOUT => $timeout,
            RequestOptions::ON_HEADERS => $this->buildDeadlineCallback($fetchDeadlineTimestamp, $timeout),
            RequestOptions::PROGRESS => $this->buildDeadlineCallback($fetchDeadlineTimestamp, $timeout),
        ];
    }


    /**
     * The timeout to give a single hop, given how long the request as a whole still has.
     */
    protected function perHopTimeout(float $maxDurationSeconds): float
    {
        return is_null($this->requestTimeout) ?
        $maxDurationSeconds :
        min($this->requestTimeout, $maxDurationSeconds);
    }


    /**
     * Aborts a request that has outlived the ceiling, however many redirect hops it took to get there.
     */
    protected function buildDeadlineCallback(float $deadlineTimestamp, float $maxDurationSeconds): callable
    {
        return function () use ($deadlineTimestamp, $maxDurationSeconds): void {
            if (microtime(true) <= $deadlineTimestamp) {
                return;
            }

            throw new HttpException(
                sprintf(
                    'Request exceeded the maximum allowed duration of %s seconds.',
                    $maxDurationSeconds,
                ),
            );
        };
    }


    /**
     * Fill in the hardened redirect defaults for any key a caller left out of an "allow_redirects" array.
     *
     * Guzzle replaces such a nested array wholesale rather than merging it, and then backfills the missing
     * keys from its own permissive defaults (5 hops, http allowed, strict off) instead of from ours. Without
     * this, a caller overriding a single key silently loses the rest of the hardening. The caller's own
     * entries still win, since this only unions in what is absent.
     *
     * An empty array is left untouched: Guzzle reads that as "do not follow redirects", which is stricter
     * than the default and must not be turned into a permissive configuration.
     *
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public static function withHardenedRedirectOptions(array $options): array
    {
        $allowRedirects = $options[RequestOptions::ALLOW_REDIRECTS] ?? null;

        if (!is_array($allowRedirects) || $allowRedirects === []) {
            return $options;
        }

        $options[RequestOptions::ALLOW_REDIRECTS] = $allowRedirects + self::DEFAULT_ALLOW_REDIRECTS_CONFIG;

        return $options;
    }


    /**
     * One sink is reused for every hop of a redirect chain, while on_headers fires once per response. It is
     * therefore the handler independent signal for where one response body ends and the next one begins.
     *
     * @param ?callable $callerOnHeaders Callback supplied by the caller, which still has to run.
     */
    protected function buildOnHeadersCallback(SizeLimitedStream $sink, ?callable $callerOnHeaders): callable
    {
        // Variadic on purpose: the handlers call this with ($response) or ($response, $request) depending on
        // version and handler, and the caller's own callback has to receive whatever it declared.
        return function (mixed ...$arguments) use ($sink, $callerOnHeaders): void {
            $sink->startNewResponse();

            if (!is_null($callerOnHeaders)) {
                $callerOnHeaders(...$arguments);
            }
        };
    }


    /**
     * @param array<string, mixed> $options See https://docs.guzzlephp.org/en/stable/request-options.html
     * @param ?int $maxSizeBytes Overrides the configured maximum response body size for this single request.
     * @throws \SimpleSAML\OpenID\Exceptions\DestinationPolicyException When the client carries a destination
     *         policy and the destination, or one of the redirect hops taken towards it, is not one the
     *         deployment permits. A subclass of HttpException, so catching that alone still works.
     * @throws \SimpleSAML\OpenID\Exceptions\HttpException
     */
    public function request(
        HttpMethodsEnum $httpMethodsEnum,
        string $uri,
        array $options = [],
        ?int $maxSizeBytes = null,
    ): ResponseInterface {
        $maxSizeBytes = is_null($maxSizeBytes) ? $this->maxFetchSizeBytes : max(1, $maxSizeBytes);

        // Per request options replace the client configuration, so a partial redirect override supplied here
        // needs the same treatment the factory gives the client level one.
        $options = self::withHardenedRedirectOptions($options);

        // Bound the response body by giving the client a sink that stops accepting data once the limit is
        // crossed, which aborts the transfer instead of letting the whole body arrive first. A caller supplying
        // its own sink is left alone, since it has taken over responsibility for where the body goes.
        $sizeLimitedSink = isset($options[RequestOptions::SINK]) ?
        null :
        new SizeLimitedStream($maxSizeBytes);

        if ($sizeLimitedSink instanceof SizeLimitedStream) {
            /** @var ?callable $callerOnHeaders */
            $callerOnHeaders = $options[RequestOptions::ON_HEADERS] ?? null;

            $options = array_merge($options, [
                RequestOptions::SINK => $sizeLimitedSink,
                // Streaming mode bypasses the sink entirely, which would leave the body unbounded. The body is
                // read into a string further down regardless, so there is nothing to gain from streaming here.
                RequestOptions::STREAM => false,
                RequestOptions::ON_HEADERS => $this->buildOnHeadersCallback($sizeLimitedSink, $callerOnHeaders),
            ]);
        }

        try {
            /** @phpstan-ignore argument.type */
            $response = $this->client->request($httpMethodsEnum->value, $uri, $options);
        } catch (Throwable $throwable) {
            // A refused destination is a policy decision rather than a transport failure, and a caller has to
            // be able to tell the two apart, so it keeps its own type instead of being folded into a generic
            // HTTP error like everything else caught here.
            $destinationPolicyException = $this->findDestinationPolicyException($throwable);

            if ($destinationPolicyException instanceof DestinationPolicyException) {
                throw $destinationPolicyException;
            }

            // The handlers report an aborted transfer as a generic failure, so name the actual cause.
            if ($sizeLimitedSink instanceof SizeLimitedStream && $sizeLimitedSink->hasExceededLimit()) {
                throw new HttpException(
                    sprintf(
                        'Response body from %s exceeded the limit of %s bytes.',
                        $uri,
                        $maxSizeBytes,
                    ),
                    (int)$throwable->getCode(),
                    $throwable,
                );
            }

            $message = sprintf(
                'Error sending HTTP request to %s. Error was: %s',
                $uri,
                $throwable->getMessage(),
            );
            throw new HttpException($message, (int)$throwable->getCode(), $throwable);
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
            $message = sprintf(
                'Unexpected HTTP response for URI %s. Status code: %s, reason: %s.',
                $uri,
                $response->getStatusCode(),
                $response->getReasonPhrase(),
            );
            throw new HttpException($message);
        }

        return $response;
    }


    /**
     * The destination policy rejection behind a failure, where that is what it was.
     *
     * The chain is walked rather than only the exception itself examined, since a handler or a middleware
     * further out may have caught the rejection and wrapped it in a failure of its own.
     */
    protected function findDestinationPolicyException(Throwable $throwable): ?DestinationPolicyException
    {
        $candidate = $throwable;

        for ($depth = 0; $depth < self::MAX_EXCEPTION_CHAIN_DEPTH; ++$depth) {
            if ($candidate instanceof DestinationPolicyException) {
                return $candidate;
            }

            $previous = $candidate->getPrevious();

            if (is_null($previous)) {
                break;
            }

            $candidate = $previous;
        }

        return null;
    }


    /**
     * Read a response body into a string, stopping as soon as the maximum allowed size is exceeded.
     *
     * The transfer itself is already bounded by the sink installed in request(). This is the backstop for
     * responses that did not come from there, such as a body produced by a caller supplied sink or by a
     * pre-built client used directly.
     *
     * @param ?int $maxSizeBytes Overrides the configured maximum for this single read.
     * @throws \SimpleSAML\OpenID\Exceptions\HttpException
     */
    public function readResponseBodyAsString(ResponseInterface $response, ?int $maxSizeBytes = null): string
    {
        $maxSizeBytes = is_null($maxSizeBytes) ? $this->maxFetchSizeBytes : max(1, $maxSizeBytes);

        $body = $response->getBody();
        $content = '';

        try {
            // Some handlers hand back a body whose cursor has already been moved to the end (Guzzle's own
            // MockHandler does this when a sink is set), which would otherwise read as an empty body.
            if ($body->isSeekable()) {
                $body->rewind();
            }

            while (!$body->eof()) {
                $chunk = $body->read(self::BODY_READ_CHUNK_SIZE_BYTES);

                // A blocking stream only returns an empty chunk once it is spent, so stop instead of spinning.
                if ($chunk === '') {
                    break;
                }

                $content .= $chunk;

                if (strlen($content) > $maxSizeBytes) {
                    throw new HttpException(
                        sprintf('Response body size exceeded the limit of %s bytes.', $maxSizeBytes),
                    );
                }
            }
        } catch (HttpException $httpException) {
            throw $httpException;
        } catch (Throwable $throwable) {
            // A body that fails mid read (a reset or timed out connection) has to stay within the error
            // contract of this class, so that callers catching HttpException keep working.
            throw new HttpException(
                'Error reading HTTP response body. Error was: ' . $throwable->getMessage(),
                (int)$throwable->getCode(),
                $throwable,
            );
        }

        return $content;
    }
}
