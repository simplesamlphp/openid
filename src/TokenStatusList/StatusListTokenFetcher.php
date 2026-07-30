<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\TokenStatusList;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\ContentTypesEnum;
use SimpleSAML\OpenID\Codebooks\HttpHeadersEnum;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jws\JwsFetcher;
use SimpleSAML\OpenID\TokenStatusList\Factories\StatusListTokenFactory;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

/**
 * Resolves a Status List Token from the URI a Referenced Token points at.
 *
 * https://datatracker.ietf.org/doc/html/draft-ietf-oauth-status-list#name-status-list-request
 *
 * @see \SimpleSAML\Test\OpenID\TokenStatusList\StatusListTokenFetcherTest
 */
class StatusListTokenFetcher extends JwsFetcher
{
    public function __construct(
        private readonly StatusListTokenFactory $parsedJwsFactory,
        ArtifactFetcher $artifactFetcher,
        DateIntervalDecorator $maxCacheDuration,
        Helpers $helpers,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($parsedJwsFactory, $artifactFetcher, $maxCacheDuration, $helpers, $logger);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function buildJwsInstance(string $token): StatusListToken
    {
        return $this->parsedJwsFactory->fromToken($token);
    }


    public function getExpectedContentTypeHttpHeader(): string
    {
        return ContentTypesEnum::ApplicationStatusListJwt->value;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromCacheOrNetwork(string $uri, ?float $deadlineTimestamp = null): StatusListToken
    {
        return $this->fromCache($uri) ?? $this->fromNetwork(
            $uri,
            HttpMethodsEnum::GET,
            $this->timeoutCeilingOptions($deadlineTimestamp),
        );
    }


    /**
     * Fetch a Status List Token from the network without caching it.
     *
     * This is what a caller wanting to cache only what it has verified reaches for: nothing about a token is
     * trustworthy until its signature and subject have been checked, and this fetcher holds no key material to
     * check them with. StatusResolver fetches this way and calls cacheIt() once verification has succeeded.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromNetworkWithoutCaching(string $uri, ?float $deadlineTimestamp = null): StatusListToken
    {
        return $this->fromNetwork(
            $uri,
            HttpMethodsEnum::GET,
            $this->timeoutCeilingOptions($deadlineTimestamp),
            false,
        );
    }


    /**
     * Cache a Status List Token that the caller has established it can trust, for the shortest of the configured
     * maximum cache duration, the time left until the token expires, and the token's own `ttl`.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function cacheIt(StatusListToken $statusListToken, string $uri, string ...$additionalCacheKeyElements): void
    {
        $cacheTtl = $this->resolveCacheTtl($statusListToken);

        if ($cacheTtl < 1) {
            return;
        }

        $this->artifactFetcher->cacheIt(
            $statusListToken->getToken(),
            $cacheTtl,
            $uri,
            ...$additionalCacheKeyElements,
        );
    }


    /**
     * Adds the Accept header stating that this fetcher wants the JWT representation.
     *
     * The specification defines both a JWT and a CWT representation of a Status List Token and expects them to be
     * chosen between by HTTP content negotiation. This fetcher only understands the JWT one, so asking for it is
     * what keeps a provider that would otherwise default to CWT interoperable, rather than having its perfectly
     * valid response rejected on arrival.
     *
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    protected function acceptJwtOptions(array $options): array
    {
        $headers = (isset($options['headers']) && is_array($options['headers'])) ? $options['headers'] : [];

        // HTTP header names are case-insensitive, so a caller's `accept` is the same header as our `Accept`.
        // Adding a second entry would have the client send both, combining them into a negotiation the caller
        // never asked for.
        foreach (array_keys($headers) as $name) {
            if (is_string($name) && strcasecmp($name, HttpHeadersEnum::Accept->value) === 0) {
                return $options;
            }
        }

        $headers[HttpHeadersEnum::Accept->value] = ContentTypesEnum::ApplicationStatusListJwt->value;

        $options['headers'] = $headers;

        return $options;
    }


    /**
     * Fetch Status List Token from cache, if available. URI is used as cache key.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromCache(string $uri): ?StatusListToken
    {
        $statusListToken = parent::fromCache($uri);

        if (is_null($statusListToken)) {
            return null;
        }

        if ($statusListToken instanceof StatusListToken) {
            return $statusListToken;
        }

        // @codeCoverageIgnoreStart
        $message = 'Unexpected Status List Token instance encountered for cache fetch.';
        $this->logger?->error($message, ['uri' => $uri]);

        throw new FetchException($message);
        // @codeCoverageIgnoreEnd
    }


    /**
     * Fetch Status List Token from network.
     *
     * Caching honours the `ttl` claim in addition to the expiration time, since `ttl` is precisely the issuer
     * saying how long a consumer may hold on to a copy, and a token may well carry a long expiration time
     * together with a short `ttl`. Holding a token past its `ttl` is how a revocation goes unnoticed for longer
     * than the issuer intended.
     *
     * @param array<string, mixed> $options See https://docs.guzzlephp.org/en/stable/request-options.html
     * @param bool $shouldCache If true, each successful fetch will be cached, with URI being used as a cache key.
     * @param string ...$additionalCacheKeyElements Additional string elements to be used as cache key.
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromNetwork(
        string $uri,
        HttpMethodsEnum $httpMethodsEnum = HttpMethodsEnum::GET,
        array $options = [],
        bool $shouldCache = true,
        string ...$additionalCacheKeyElements,
    ): StatusListToken {
        // Caching is taken over below, so that the ttl claim can be taken into account.
        $statusListToken = parent::fromNetwork($uri, $httpMethodsEnum, $this->acceptJwtOptions($options), false);

        if (!$statusListToken instanceof StatusListToken) {
            // @codeCoverageIgnoreStart
            $message = 'Unexpected Status List Token instance encountered for network fetch.';
            $this->logger?->error($message, ['uri' => $uri]);

            throw new FetchException($message);
            // @codeCoverageIgnoreEnd
        }

        if ($shouldCache) {
            $this->cacheIt($statusListToken, $uri, ...$additionalCacheKeyElements);
        }

        return $statusListToken;
    }


    /**
     * Shortest of the configured maximum cache duration, the time left until the token expires, and the token's
     * own `ttl`.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function resolveCacheTtl(StatusListToken $statusListToken): int
    {
        $cacheTtl = is_int($expirationTime = $statusListToken->getExpirationTime()) ?
        $this->maxCacheDuration->lowestInSecondsComparedToExpirationTime($expirationTime) :
        $this->maxCacheDuration->getInSeconds();

        $ttl = $statusListToken->getTimeToLive();

        if ($ttl !== null) {
            return min($cacheTtl, (int)$ttl);
        }

        return $cacheTtl;
    }
}
