<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Utils;

use DateInterval;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\Exceptions\HttpException;
use Throwable;

class ArtifactFetcher
{
    public function __construct(
        protected readonly HttpClientDecorator $httpClientDecorator,
        protected readonly ?CacheDecorator $cacheDecorator = null,
        protected readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function fromCacheAsString(string $keyElement, string ...$keyElements): ?string
    {
        if (is_null($this->cacheDecorator)) {
            $this->logger?->debug(
                'Cache instance not available, skipping cache query.',
                compact('keyElement', 'keyElements'),
            );
            return null;
        }

        try {
            /** @psalm-suppress MixedAssignment */
            $artifact = $this->cacheDecorator->get(null, $keyElement, ...$keyElements);
        } catch (Throwable $exception) {
            $this->logger?->error(
                'Error trying to get artifact from cache: ' . $exception->getMessage(),
                compact('keyElement', 'keyElements'),
            );
            return null;
        }

        if (is_null($artifact)) {
            $this->logger?->debug(
                'Artifact not found in cache.',
                compact('keyElement', 'keyElements'),
            );
        }

        if (is_string($artifact)) {
            $this->logger?->debug(
                'Artifact found in cache, returning.',
                compact('artifact', 'keyElement', 'keyElements'),
            );
            return $artifact;
        }

        $this->logger?->warning(
            'Unexpected value for cached artifact (expected string).',
            compact('artifact', 'keyElement', 'keyElements'),
        );

        return null;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromNetwork(string $uri): ResponseInterface
    {
        $this->logger?->debug('Fetching artifact on network from URI.', compact('uri'));
        try {
            $response = $this->httpClientDecorator->request(HttpMethodsEnum::GET, $uri);
        } catch (Throwable $e) {
            $message = sprintf(
                'Error sending HTTP request to %s. Error was: %s',
                $uri,
                $e->getMessage(),
            );
            $this->logger?->error($message);
            throw new FetchException($message, (int)$e->getCode(), $e);
        }

        $this->logger?->debug('Artifact fetched on network from URI, returning HTTP response.', compact('uri'));

        return $response;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromNetworkAsString(string $uri): string
    {
        $this->logger?->debug('Fetching artifact on network from URI (as string).', compact('uri'));

        $artifact = $this->fromNetwork($uri)->getBody()->getContents();

        $this->logger?->debug(
            'Fetched artifact on network from URI as string.',
            compact('artifact', 'uri'),
        );

        return $artifact;
    }

    public function cacheIt(string $artifact, int|DateInterval $ttl, string $keyElement, string ...$keyElements): void
    {
        if (is_null($this->cacheDecorator)) {
            $this->logger?->debug(
                'Cache instance not available, skipping caching.',
                compact('artifact', 'ttl', 'keyElement', 'keyElements'),
            );
            return;
        }

        try {
            $this->cacheDecorator->set(
                $artifact,
                $ttl,
                $keyElement,
                ...$keyElements,
            );
            $this->logger?->debug(
                'Artifact saved to cache.',
                compact('artifact', 'ttl', 'keyElement', 'keyElements'),
            );
        } catch (Throwable $exception) {
            $this->logger?->error(
                'Error saving artifact to cache: ' . $exception->getMessage(),
                compact('artifact', 'ttl', 'keyElement', 'keyElements'),
            );
        }
    }
}
