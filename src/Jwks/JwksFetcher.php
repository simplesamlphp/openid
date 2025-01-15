<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jwks;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Exceptions\HttpException;
use SimpleSAML\OpenID\Exceptions\JwksException;
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
        protected readonly ?CacheDecorator $cacheDecorator = null,
        protected readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @return array{keys:array <string,mixed>}
     * @throws \SimpleSAML\OpenID\Exceptions\JwksException
     */
    protected function decodeJwksJson(string $jwksJson): array
    {
        try {
            $jwks = $this->helpers->json()->decode($jwksJson);
        } catch (\JsonException $exception) {
            $message = 'Error trying to decode JWKS JSON document: ' . $exception->getMessage();
            $this->logger?->error(
                'Error trying to decode JWKS JSON document: ' . $exception->getMessage(),
                ['jwksJson' => $jwksJson],
            );
            throw new JwksException($message);
        }

        if (!is_array($jwks)) {
            $message = sprintf('Unexpected JWKS type: %s.', var_export($jwks, true));
            $this->logger?->error($message, ['jwks' => $jwks]);
            throw new JwksException($message);
        }

        if (
            (!array_key_exists(ClaimsEnum::Keys->value, $jwks)) ||
            (!is_array($jwks[ClaimsEnum::Keys->value])) ||
            empty($jwks[ClaimsEnum::Keys->value])
        ) {
            $message = sprintf('Unexpected JWKS format: %s.', var_export($jwks, true));
            $this->logger?->error($message, ['jwks' => $jwks]);
            throw new JwksException($message);
        }

        $jwks[ClaimsEnum::Keys->value] = $this->helpers->arr()->ensureStringKeys($jwks[ClaimsEnum::Keys->value]);
        /** @var array{keys:array<string,mixed>} $jwks */
        return $jwks;
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
        } catch (Throwable $exception) {
            $this->logger?->error(
                'Error trying to get JWKS document from cache: ' . $exception->getMessage(),
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
        } catch (JwksException $exception) {
            $this->logger?->error(
                'Error trying to decode JWKS JSON: ' . $exception->getMessage(),
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
        } catch (HttpException $e) {
            $this->logger?->error('Error trying to get JWKS from URI: ' . $e->getMessage(), ['uri' => $uri]);
            return null;
        }

        $jwksJson = $response->getBody()->getContents();
        $this->logger?->info(
            'Successful HTTP response for JWKS URI fetch, trying to decode it.',
            ['uri' => $uri, 'jwksJson' => $jwksJson],
        );

        try {
            $jwks = $this->decodeJwksJson($jwksJson);
        } catch (JwksException $exception) {
            $this->logger?->error(
                'Error trying to decode JWKS document: ' . $exception->getMessage(),
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
        } catch (Throwable $exception) {
            $this->logger?->error(
                'Error setting JWKS JSON to cache: ' . $exception->getMessage(),
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
        } catch (HttpException $e) {
            $this->logger?->error('Error trying to get Signed JWKS from URI: ' . $e->getMessage(), ['uri' => $uri]);
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
        } catch (Throwable $exception) {
            $this->logger?->error(
                'Error setting Signed JWKS JSON to cache: ' . $exception->getMessage(),
                ['uri' => $uri],
            );
        }

        return $this->jwksFactory->fromKeyData($signedJwks->jsonSerialize());
    }
}
