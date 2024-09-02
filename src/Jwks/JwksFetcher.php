<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jwks;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use Throwable;

readonly class JwksFetcher
{
    public function __construct(
        protected HttpClientDecorator $httpClientDecorator,
        protected JwksFactory $jwksFactory,
        protected DateIntervalDecorator $maxCacheDuration,
        protected ?CacheDecorator $cacheDecorator = null,
        protected ?LoggerInterface $logger = null,
        protected Helpers $helpers = new Helpers(),
    ) {
    }

    public function fromCache(string $uri): ?JwksDecorator
    {
        $this->logger?->debug(
            'Trying to get JWKS document from cache.',
            compact('uri'),
        );

        try {
            /** @var ?string $jwks */
            $jwks = $this->cacheDecorator?->get(null, $uri);
        } catch (Throwable $exception) {
            $this->logger?->error(
                'Error trying to get JWKS document from cache: ' . $exception->getMessage(),
                compact('uri'),
            );
            return null;
        }

        if (!is_string($jwks)) {
            return null;
        }

        $this->logger?->debug(
            'JWKS document token found in cache, trying to JSON decode it.',
            compact('uri', 'jwks'),
        );


        try {
            $jwksArr = $this->helpers->json()->decode($jwks);
        } catch (\JsonException $exception) {
            $this->logger?->error(
                'Error trying to decode JWKS document: ' . $exception->getMessage(),
                compact('uri', 'jwks'),
            );
            return null;
        }

        $this->logger?->debug('JWKS JSON decoded, proceeding instance building.', compact('uri', 'jwksArr'));

        return $this->jwksFactory->fromKeyData($jwksArr);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromJwksUri(string $uri): ?JwksDecorator
    {
        $this->logger?->debug('Trying to get JWKS from JWKS URI.', compact('uri'));

        // TODO mivanci continue
    }
}
