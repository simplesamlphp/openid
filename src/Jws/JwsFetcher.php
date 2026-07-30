<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\HttpHeadersEnum;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

/**
 * @see \SimpleSAML\Test\OpenID\Jws\JwsFetcherTest
 */
class JwsFetcher extends AbstractJwsFetcher
{
    public function __construct(
        private readonly ParsedJwsFactory $parsedJwsFactory,
        ArtifactFetcher $artifactFetcher,
        DateIntervalDecorator $maxCacheDuration,
        Helpers $helpers,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($artifactFetcher, $maxCacheDuration, $helpers, $logger);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function buildJwsInstance(string $token): ParsedJws
    {
        return $this->parsedJwsFactory->fromToken($token);
    }


    public function getExpectedContentTypeHttpHeader(): ?string
    {
        return null;
    }


    /**
     * @param ?float $deadlineTimestamp Point in time the network fetch must not run past, if one is reached for.
     * Lets a caller holding a budget for a whole operation bound each request it is made of.
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromCacheOrNetwork(string $uri, ?float $deadlineTimestamp = null): ParsedJws
    {
        return $this->fromCache($uri) ?? $this->fromNetwork(
            $uri,
            HttpMethodsEnum::GET,
            $this->timeoutCeilingOptions($deadlineTimestamp),
        );
    }


    /**
     * Whether a Content-Type response header conveys the media type this fetcher expects.
     *
     * Only the media type itself is compared, since parameters such as charset may legitimately follow it, and
     * the comparison ignores case because RFC 9110 defines type and subtype as case-insensitive. Matching on the
     * media type as a whole rather than as a substring also keeps a lookalike such as
     * `application/entity-statement+jwtfoo` from passing for the type it merely starts with.
     */
    protected function hasExpectedContentType(string $contentTypeHeaderLine, string $expectedContentType): bool
    {
        $mediaType = trim(explode(';', $contentTypeHeaderLine, 2)[0]);

        return strcasecmp($mediaType, $expectedContentType) === 0;
    }


    /**
     * @return array<string,mixed>
     */
    protected function timeoutCeilingOptions(?float $deadlineTimestamp): array
    {
        return is_null($deadlineTimestamp) ?
        [] :
        $this->artifactFetcher->timeoutCeilingOptions($deadlineTimestamp);
    }


    /**
     * Fetch JWS from cache, if available. URI is used as cache key.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromCache(string $uri): ?ParsedJws
    {
        $this->logger?->debug(
            'Trying to get JWS token from cache.',
            ['uri' => $uri],
        );

        $jws = $this->artifactFetcher->fromCacheAsString($uri);

        if (!is_string($jws)) {
            $this->logger?->debug('JWS token not found in cache.', ['uri' => $uri]);
            return null;
        }

        $this->logger?->debug(
            'JWS token found in cache, trying to build instance.',
            ['uri' => $uri],
        );

        return $this->buildJwsInstance($jws);
    }


    /**
     * Fetch JWS from the network.
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
    ): ParsedJws {
        $this->logger?->debug(
            'Trying to fetch JWS token from network.',
            ['uri' => $uri],
        );

        $response = $this->artifactFetcher->fromNetwork($uri, $httpMethodsEnum, $options);

        if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
            $message = sprintf(
                'Unexpected HTTP response for JWS fetch, status code: %s, reason: %s. URI %s',
                $response->getStatusCode(),
                $response->getReasonPhrase(),
                $uri,
            );
            $this->logger?->error($message);
            throw new FetchException($message);
        }

        if (
            is_string($expectedContentTypeHttpHeader = $this->getExpectedContentTypeHttpHeader()) &&
            (!$this->hasExpectedContentType(
                $response->getHeaderLine(HttpHeadersEnum::ContentType->value),
                $expectedContentTypeHttpHeader,
            ))
        ) {
            $message = sprintf(
                'Unexpected content type in response for JWS fetch: %s, expected: %s. URI %s',
                $response->getHeaderLine(HttpHeadersEnum::ContentType->value),
                $expectedContentTypeHttpHeader,
                $uri,
            );
            $this->logger?->error($message);
            throw new FetchException($message);
        }

        $token = $this->artifactFetcher->readResponseBodyAsString($response);
        $this->logger?->debug('Successful HTTP response for JWS fetch.', ['uri' => $uri, 'token' => $token]);
        $this->logger?->debug('Proceeding to JWS instance building.');

        $jwsInstance = $this->buildJwsInstance($token);
        $this->logger?->debug('JWS instance built.', ['uri' => $uri, 'token' => $token]);

        if ($shouldCache) {
            $this->logger?->debug('Saving JWS token to cache.', ['uri' => $uri, 'token' => $token]);
            $cacheTtl = is_int($expirationTime = $jwsInstance->getExpirationTime()) ?
            $this->maxCacheDuration->lowestInSecondsComparedToExpirationTime(
                $expirationTime,
            ) :
            $this->maxCacheDuration->getInSeconds();

            $this->artifactFetcher->cacheIt($token, $cacheTtl, $uri, ...$additionalCacheKeyElements);
        }

        $this->logger?->debug('Returning built JWS instance.', ['uri' => $uri, 'token' => $token]);

        return $jwsInstance;
    }
}
