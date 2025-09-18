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
                ['keyElement' => $keyElement, 'keyElements' => $keyElements],
            );
            return null;
        }

        try {
            $artifact = $this->cacheDecorator->get(null, $keyElement, ...$keyElements);
        } catch (Throwable $throwable) {
            $this->logger?->error(
                'Error trying to get artifact from cache: ' . $throwable->getMessage(),
                ['keyElement' => $keyElement, 'keyElements' => $keyElements],
            );
            return null;
        }

        if (is_null($artifact)) {
            $this->logger?->debug(
                'Artifact not found in cache.',
                ['keyElement' => $keyElement, 'keyElements' => $keyElements],
            );
            return null;
        }

        if (is_string($artifact)) {
            $this->logger?->debug(
                'Artifact found in cache, returning.',
                ['artifact' => $artifact, 'keyElement' => $keyElement, 'keyElements' => $keyElements],
            );
            return $artifact;
        }

        $this->logger?->warning(
            'Unexpected value for cached artifact (expected string).',
            ['artifact' => $artifact, 'keyElement' => $keyElement, 'keyElements' => $keyElements],
        );

        return null;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromNetwork(string $uri): ResponseInterface
    {
        $this->logger?->debug('Fetching artifact on network from URI.', ['uri' => $uri]);
        try {
            $response = $this->httpClientDecorator->request(HttpMethodsEnum::GET, $uri);
        } catch (Throwable $throwable) {
            $message = sprintf(
                'Error sending HTTP request to %s. Error was: %s',
                $uri,
                $throwable->getMessage(),
            );
            $this->logger?->error($message);
            throw new FetchException($message, (int)$throwable->getCode(), $throwable);
        }

        $this->logger?->debug('Artifact fetched on network from URI, returning HTTP response.', ['uri' => $uri]);

        return $response;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromNetworkAsString(string $uri): string
    {
        $this->logger?->debug('Fetching artifact on network from URI (as string).', ['uri' => $uri]);

        $artifact = $this->fromNetwork($uri)->getBody()->getContents();

        $this->logger?->debug(
            'Fetched artifact on network from URI as string.',
            ['artifact' => $artifact, 'uri' => $uri],
        );

        return $artifact;
    }


    public function cacheIt(string $artifact, int|DateInterval $ttl, string $keyElement, string ...$keyElements): void
    {
        if (is_null($this->cacheDecorator)) {
            $this->logger?->debug(
                'Cache instance not available, skipping caching.',
                ['artifact' => $artifact, 'ttl' => $ttl, 'keyElement' => $keyElement, 'keyElements' => $keyElements],
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
                ['artifact' => $artifact, 'ttl' => $ttl, 'keyElement' => $keyElement, 'keyElements' => $keyElements],
            );
        } catch (Throwable $throwable) {
            $this->logger?->error(
                'Error saving artifact to cache: ' . $throwable->getMessage(),
                ['artifact' => $artifact, 'ttl' => $ttl, 'keyElement' => $keyElement, 'keyElements' => $keyElements],
            );
        }
    }
}
