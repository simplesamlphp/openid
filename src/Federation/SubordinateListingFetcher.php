<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\EntityDiscoveryException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;
use Throwable;

class SubordinateListingFetcher
{
    public function __construct(
        protected readonly ArtifactFetcher $artifactFetcher,
        protected readonly Helpers $helpers,
        protected readonly DateIntervalDecorator $maxCacheDurationDecorator,
        protected readonly ?LoggerInterface $logger = null,
    ) {
    }


    /**
     * Fetch immediate subordinate entity IDs from a federation list endpoint.
     *
     * @param non-empty-string $listEndpointUri
     * @param array<string, string|string[]> $filters  Optional query params: entity_type, intermediate, etc.
     * @param bool $forceRefresh  If true, ignore cached listing and fetch from network.
     * @return non-empty-string[]
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityDiscoveryException
     */
    public function fetch(string $listEndpointUri, array $filters = [], bool $forceRefresh = false): array
    {
        $uri = $this->helpers->url()->withMultiValueParams($listEndpointUri, $filters);

        if (!$forceRefresh) {
            $this->logger?->debug('Checking for cached subordinate listing.', ['uri' => $uri]);
            $cached = $this->artifactFetcher->fromCacheAsString($uri);
            if (is_string($cached)) {
                $this->logger?->debug('Returning cached subordinate listing.', ['uri' => $uri]);
                return $this->decodeAndEnsureType($cached);
            }

            $this->logger?->debug('No cached subordinate listing found.', ['uri' => $uri]);
        }

        $this->logger?->debug('Fetching subordinate listing from network.', ['uri' => $uri, 'filters' => $filters]);

        try {
            $responseBody = $this->artifactFetcher->fromNetworkAsString($uri);
            $this->logger?->debug('Fetched subordinate listing from network.', ['uri' => $uri]);

            $result = $this->decodeAndEnsureType($responseBody);

            $this->artifactFetcher->cacheIt(
                $responseBody,
                $this->maxCacheDurationDecorator->getInSeconds(),
                $uri,
            );

            return $result;
        } catch (Throwable $throwable) {
            $message = sprintf(
                'Unable to fetch subordinate listing from %s. Error: %s',
                $uri,
                $throwable->getMessage(),
            );
            $this->logger?->error($message);
            throw new EntityDiscoveryException($message, (int)$throwable->getCode(), $throwable);
        }
    }


    /**
     * @return non-empty-string[]
     * @throws \SimpleSAML\OpenID\Exceptions\EntityDiscoveryException
     */
    protected function decodeAndEnsureType(string $responseBody): array
    {
        $decoded = $this->helpers->json()->decode($responseBody);

        if (!is_array($decoded)) {
            throw new EntityDiscoveryException('Subordinate listing response is not a JSON array.');
        }

        return $this->helpers->type()->ensureArrayWithValuesAsNonEmptyStrings($decoded, 'Subordinate Listing');
    }
}
