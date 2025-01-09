<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\HttpHeadersEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

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
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromCacheOrNetwork(string $uri): ParsedJws
    {
        return $this->fromCache($uri) ?? $this->fromNetwork($uri);
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
            compact('uri'),
        );

        $jws = $this->artifactFetcher->fromCacheAsString($uri);

        if (!is_string($jws)) {
            $this->logger?->debug('JWS token not found in cache.', compact('uri'));
            return null;
        }

        $this->logger?->debug(
            'JWS token found in cache, trying to build instance.',
            compact('uri'),
        );

        return $this->buildJwsInstance($jws);
    }

    /**
     * Fetch JWS from network. Each successful fetch will be cached, with URI being used as a cache key.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromNetwork(string $uri): ParsedJws
    {
        $this->logger?->debug(
            'Trying to fetch JWS token from network.',
            compact('uri'),
        );

        $response = $this->artifactFetcher->fromNetwork($uri);

        if ($response->getStatusCode() !== 200) {
            $message = sprintf(
                'Unexpected HTTP response for JWS fetch, status code: %s, reason: %s. URI %s',
                $response->getStatusCode(),
                $response->getReasonPhrase(),
                $uri,
            );
            $this->logger?->error($message);
            throw new FetchException($message);
        }

        /** @psalm-suppress InvalidLiteralArgument */
        if (
            is_string($expectedContentTypeHttpHeader = $this->getExpectedContentTypeHttpHeader()) &&
            (!str_contains(
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

        $token = $response->getBody()->getContents();
        $this->logger?->debug('Successful HTTP response for JWS fetch.', compact('uri', 'token'));
        $this->logger?->debug('Proceeding to JWS instance building.');

        $jwsInstance = $this->buildJwsInstance($token);
        $this->logger?->debug('JWS instance built, saving its token to cache.', compact('uri', 'token'));

        $cacheTtl = is_int($expirationTime = $jwsInstance->getExpirationTime()) ?
        $this->maxCacheDuration->lowestInSecondsComparedToExpirationTime(
            $expirationTime,
        ) :
        $this->maxCacheDuration->getInSeconds();

        $this->artifactFetcher->cacheIt($token, $cacheTtl, $uri);

        $this->logger?->debug('Returning built JWS instance.', compact('uri', 'token'));

        return $jwsInstance;
    }
}
