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
        protected readonly DateIntervalDecorator $maxCacheDuration,
        protected readonly ?CacheDecorator $cacheDecorator = null,
        protected readonly ?LoggerInterface $logger = null,
        protected readonly Helpers $helpers = new Helpers(),
    ) {
    }

    /**
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
                compact('jwksJson'),
            );
            throw new JwksException($message);
        }

        if (!is_array($jwks)) {
            $message = sprintf('Unexpected JWKS type: %s.', var_export($jwks, true));
            $this->logger?->error($message, compact('jwks'));
            throw new JwksException($message);
        }

        if (
            (!array_key_exists(ClaimsEnum::Keys->value, $jwks)) ||
            (!is_array($jwks[ClaimsEnum::Keys->value])) ||
            empty($jwks[ClaimsEnum::Keys->value])
        ) {
            $message = sprintf('Unexpected JWKS format: %s.', var_export($jwks, true));
            $this->logger?->error($message, compact('jwks'));
            throw new JwksException($message);
        }

        return $jwks;
    }

    public function fromCache(string $uri): ?JwksDecorator
    {
        $this->logger?->debug(
            'Trying to get JWKS document from cache.',
            compact('uri'),
        );

        try {
            /** @var ?string $jwksJson */
            $jwksJson = $this->cacheDecorator?->get(null, $uri);
        } catch (Throwable $exception) {
            $this->logger?->error(
                'Error trying to get JWKS document from cache: ' . $exception->getMessage(),
                compact('uri'),
            );
            return null;
        }

        if (!is_string($jwksJson)) {
            return null;
        }

        $this->logger?->debug(
            'JWKS JSON found in cache, trying to decode it.',
            compact('uri', 'jwksJson'),
        );

        try {
            $jwks = $this->decodeJwksJson($jwksJson);
        } catch (JwksException $exception) {
            $this->logger?->error(
                'Error trying to decode JWKS JSON: ' . $exception->getMessage(),
                compact('uri', 'jwksJson'),
            );
            return null;
        }

        $this->logger?->debug('JWKS JSON decoded, proceeding to instance building.', compact('uri', 'jwks'));

        return $this->jwksFactory->fromKeyData($jwks);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromJwksUri(string $uri): ?JwksDecorator
    {
        $this->logger?->debug('Trying to get JWKS from URI.', compact('uri'));

        try {
            $response = $this->httpClientDecorator->request(HttpMethodsEnum::GET, $uri);
        } catch (HttpException $e) {
            $this->logger?->error('Error trying to get JWKS from URI: ' . $e->getMessage(), compact('uri'));
            return null;
        }

        $jwksJson = $response->getBody()->getContents();
        $this->logger?->info(
            'Successful HTTP response for JWKS URI fetch, trying to decode it.',
            compact('uri', 'jwksJson'),
        );

        try {
            $jwks = $this->decodeJwksJson($jwksJson);
        } catch (JwksException $exception) {
            $this->logger?->error(
                'Error trying to decode JWKS document: ' . $exception->getMessage(),
                compact('uri', 'jwksJson'),
            );
            return null;
        }

        $this->logger?->debug('JWKS JSON decoded, saving it to cache.', compact('uri', 'jwks'));

        try {
            $this->cacheDecorator?->set(
                $jwksJson,
                $this->maxCacheDuration->inSeconds,
                $uri,
            );
            $this->logger?->debug('JWKS JSON saved to cache.', compact('uri', 'jwks'));
        } catch (Throwable $exception) {
            $this->logger?->error(
                'Error setting JWKS JSON to cache: ' . $exception->getMessage(),
                compact('uri', $jwksJson),
            );
        }

        $this->logger?->debug('Proceeding to instance building.', compact('uri', 'jwks'));

        return $this->jwksFactory->fromKeyData($jwks);
    }

    /**
     * @param string $uri URI from which to fetch SignedJwks statement.
     * @param array $federationJwks Federation JWKS which will be used to check signature on SignedJwks statement.
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromSignedJwksUri(string $uri, array $federationJwks): ?JwksDecorator
    {
        $this->logger?->debug('Trying to get Signed JWKS from URI.', compact('uri'));

        try {
            $response = $this->httpClientDecorator->request(HttpMethodsEnum::GET, $uri);
        } catch (HttpException $e) {
            $this->logger?->error('Error trying to get Signed JWKS from URI: ' . $e->getMessage(), compact('uri'));
            return null;
        }

        $token = $response->getBody()->getContents();
        $this->logger?->info('Successful HTTP response for Signed JWKS fetch.', compact('uri', 'token'));
        $this->logger?->debug('Proceeding to Signed JWKS instance building.');

        $signedJwks = $this->signedJwksFactory->fromToken($token);
        $this->logger?->debug(
            'Signed JWKS instance built. Trying to verify signature.',
            compact('uri', 'token'),
        );

        $signedJwks->verifyWithKeySet($federationJwks);
        $this->logger?->debug('Signed JWKS signature verified.', compact('uri', 'token'));

        try {
            $jwksJson = $this->helpers->json()->encode($signedJwks->jsonSerialize());
            $this->logger?->debug('Signed JWKS JSON decoded, saving it to cache.', compact('uri', 'jwksJson'));
            $signedJwksExpirationTime = $signedJwks->getExpirationTime();
            $cacheTtl = is_null($signedJwksExpirationTime) ?
            $this->maxCacheDuration->inSeconds :
            $this->maxCacheDuration->lowestInSecondsComparedToExpirationTime($signedJwksExpirationTime);
            $this->cacheDecorator?->set($jwksJson, $cacheTtl, $uri);
            $this->logger?->debug('Signed JWKS JSON successfully cached.', compact('uri', 'jwksJson'));
        } catch (Throwable $exception) {
            $this->logger?->error(
                'Error setting Signed JWKS JSON to cache: ' . $exception->getMessage(),
                compact('uri'),
            );
        }

        return $this->jwksFactory->fromKeyData($signedJwks->jsonSerialize());
    }
}
