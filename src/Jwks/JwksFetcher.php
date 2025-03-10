<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jwks;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Exceptions\HttpException;
use SimpleSAML\OpenID\Exceptions\JwksException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jwks\Factories\SignedJwksFactory;
use Throwable;

class JwksFetcher
{
    public function __construct(
        protected readonly HttpClientDecorator $httpClientDecorator,
        protected readonly JwksFactory $jwksFactory,
        protected readonly SignedJwksFactory $signedJwksFactory,
        protected readonly DateIntervalDecorator $maxCacheDurationDecorator,
        protected readonly Helpers $helpers,
        protected readonly ClaimFactory $claimFactory,
        protected readonly ?CacheDecorator $cacheDecorator = null,
        protected readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @return array{keys:non-empty-array<array<string,mixed>>}
     * @throws \SimpleSAML\OpenID\Exceptions\JwksException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    protected function decodeJwksJson(string $jwksJson): array
    {
        try {
            $jwks = $this->helpers->json()->decode($jwksJson);
        } catch (\JsonException $jsonException) {
            $message = 'Error trying to decode JWKS JSON document: ' . $jsonException->getMessage();
            $this->logger?->error(
                $message,
                ['jwksJson' => $jwksJson],
            );
            throw new JwksException($message);
        }

        return $this->claimFactory->buildJwks($jwks)->getValue();
    }

    public function fromCache(string $uri): ?JwksDecorator
    {
        $this->logger?->debug(
            'Trying to get JWKS document from cache.',
            ['uri' => $uri],
        );

        try {
            /** @var ?string $jwksJson */
            $jwksJson = $this->cacheDecorator?->get(null, $uri);
        } catch (Throwable $throwable) {
            $this->logger?->error(
                'Error trying to get JWKS document from cache: ' . $throwable->getMessage(),
                ['uri' => $uri],
            );
            return null;
        }

        if (!is_string($jwksJson)) {
            $this->logger?->debug('JWKS JSON not found in cache.', ['uri' => $uri]);
            return null;
        }

        $this->logger?->debug(
            'JWKS JSON found in cache, trying to decode it.',
            ['uri' => $uri, 'jwksJson' => $jwksJson],
        );

        try {
            $jwks = $this->decodeJwksJson($jwksJson);
        } catch (JwksException $jwksException) {
            $this->logger?->error(
                'Error trying to decode JWKS JSON: ' . $jwksException->getMessage(),
                ['uri' => $uri, 'jwksJson' => $jwksJson],
            );
            return null;
        }

        $this->logger?->debug('JWKS JSON decoded, proceeding to instance building.', ['uri' => $uri, 'jwks' => $jwks]);

        return $this->jwksFactory->fromKeyData($jwks);
    }

    /**
     */
    public function fromCacheOrJwksUri(string $uri): ?JwksDecorator
    {
        return $this->fromCache($uri) ?? $this->fromJwksUri($uri);
    }

    /**
     */
    public function fromJwksUri(string $uri): ?JwksDecorator
    {
        $this->logger?->debug('Trying to get JWKS from URI.', ['uri' => $uri]);

        try {
            $response = $this->httpClientDecorator->request(HttpMethodsEnum::GET, $uri);
        } catch (HttpException $httpException) {
            $this->logger?->error(
                'Error trying to get JWKS from URI: ' . $httpException->getMessage(),
                ['uri' => $uri],
            );
            return null;
        }

        $jwksJson = $response->getBody()->getContents();
        $this->logger?->info(
            'Successful HTTP response for JWKS URI fetch, trying to decode it.',
            ['uri' => $uri, 'jwksJson' => $jwksJson],
        );

        try {
            $jwks = $this->decodeJwksJson($jwksJson);
        } catch (JwksException $jwksException) {
            $this->logger?->error(
                'Error trying to decode JWKS document: ' . $jwksException->getMessage(),
                ['uri' => $uri, 'jwksJson' => $jwksJson],
            );
            return null;
        }

        $this->logger?->debug('JWKS JSON decoded, saving it to cache.', ['uri' => $uri, 'jwks' => $jwks]);

        try {
            $this->cacheDecorator?->set(
                $jwksJson,
                $this->maxCacheDurationDecorator->getInSeconds(),
                $uri,
            );
            $this->logger?->debug('JWKS JSON saved to cache.', ['uri' => $uri, 'jwks' => $jwks]);
        } catch (Throwable $throwable) {
            $this->logger?->error(
                'Error setting JWKS JSON to cache: ' . $throwable->getMessage(),
                ['uri' => $uri, 'jwksJson' => $jwksJson],
            );
        }

        $this->logger?->debug('Proceeding to instance building.', ['uri' => $uri, 'jwks' => $jwks]);

        return $this->jwksFactory->fromKeyData($jwks);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @phpstan-ignore missingType.iterableValue (JWKS array is validated later)
     */
    public function fromCacheOrSignedJwksUri(string $uri, array $federationJwks): ?JwksDecorator
    {
        return $this->fromCache($uri) ?? $this->fromSignedJwksUri($uri, $federationJwks);
    }

    /**
     * @param string $uri URI from which to fetch SignedJwks statement.
     * @param array $federationJwks Federation JWKS which will be used to check signature on SignedJwks statement.
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @phpstan-ignore missingType.iterableValue (JWKS array is validated later)
     */
    public function fromSignedJwksUri(string $uri, array $federationJwks): ?JwksDecorator
    {
        $this->logger?->debug('Trying to get Signed JWKS from URI.', ['uri' => $uri]);

        try {
            $response = $this->httpClientDecorator->request(HttpMethodsEnum::GET, $uri);
        } catch (HttpException $httpException) {
            $this->logger?->error(
                'Error trying to get Signed JWKS from URI: ' . $httpException->getMessage(),
                ['uri' => $uri],
            );
            return null;
        }

        $token = $response->getBody()->getContents();
        $this->logger?->info('Successful HTTP response for Signed JWKS fetch.', ['uri' => $uri, 'token' => $token]);
        $this->logger?->debug('Proceeding to Signed JWKS instance building.');

        $signedJwks = $this->signedJwksFactory->fromToken($token);
        $this->logger?->debug(
            'Signed JWKS instance built. Trying to verify signature.',
            ['uri' => $uri, 'token' => $token],
        );

        $signedJwks->verifyWithKeySet($federationJwks);
        $this->logger?->debug('Signed JWKS signature verified.', ['uri' => $uri, 'token' => $token]);

        try {
            $jwksJson = $this->helpers->json()->encode($signedJwks->jsonSerialize());
            $this->logger?->debug('Signed JWKS JSON decoded.', ['uri' => $uri, 'jwksJson' => $jwksJson]);
            $signedJwksExpirationTime = $signedJwks->getExpirationTime();
            $cacheTtl = is_null($signedJwksExpirationTime) ?
            $this->maxCacheDurationDecorator->getInSeconds() :
            $this->maxCacheDurationDecorator->lowestInSecondsComparedToExpirationTime($signedJwksExpirationTime);
            $this->cacheDecorator?->set($jwksJson, $cacheTtl, $uri);
            $this->logger?->debug(
                'Signed JWKS JSON successfully cached.',
                ['uri' => $uri, 'jwksJson' => $jwksJson, 'cacheTtl' => $cacheTtl],
            );
        } catch (Throwable $throwable) {
            $this->logger?->error(
                'Error setting Signed JWKS JSON to cache: ' . $throwable->getMessage(),
                ['uri' => $uri],
            );
        }

        return $this->jwksFactory->fromKeyData($signedJwks->jsonSerialize());
    }
}
