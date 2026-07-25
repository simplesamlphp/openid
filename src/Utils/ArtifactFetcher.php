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

/**
 * @see \SimpleSAML\Test\OpenID\Utils\ArtifactFetcherTest
 */
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
     * @param array<string, mixed> $options See https://docs.guzzlephp.org/en/stable/request-options.html
     * @param ?int $maxSizeBytes Overrides the configured maximum response body size for this single request.
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromNetwork(
        string $uri,
        HttpMethodsEnum $httpMethodsEnum = HttpMethodsEnum::GET,
        array $options = [],
        ?int $maxSizeBytes = null,
    ): ResponseInterface {
        $this->logger?->debug('Fetching artifact on network from URI.', ['uri' => $uri]);
        try {
            $response = $this->httpClientDecorator->request($httpMethodsEnum, $uri, $options, $maxSizeBytes);
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
     * Request options that stop a single request from running longer than the given number of seconds.
     *
     * @see \SimpleSAML\OpenID\Decorators\HttpClientDecorator::timeoutCeilingOptions()
     * @return array<string,mixed>
     */
    public function timeoutCeilingOptions(float $deadlineTimestamp): array
    {
        return $this->httpClientDecorator->timeoutCeilingOptions($deadlineTimestamp);
    }


    /**
     * Read a response body into a string, up to the maximum size allowed by the HTTP client decorator.
     *
     * @param ?int $maxSizeBytes Overrides the configured maximum for this single read.
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function readResponseBodyAsString(ResponseInterface $response, ?int $maxSizeBytes = null): string
    {
        try {
            return $this->httpClientDecorator->readResponseBodyAsString($response, $maxSizeBytes);
        } catch (Throwable $throwable) {
            $message = 'Error reading HTTP response body. Error was: ' . $throwable->getMessage();
            $this->logger?->error($message);
            throw new FetchException($message, (int)$throwable->getCode(), $throwable);
        }
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromNetworkAsString(string $uri): string
    {
        $this->logger?->debug('Fetching artifact on network from URI (as string).', ['uri' => $uri]);

        $artifact = $this->readResponseBodyAsString($this->fromNetwork($uri));

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
