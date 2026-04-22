<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use Throwable;

class FederationDiscovery
{
    public function __construct(
        private readonly EntityStatementFetcher $entityStatementFetcher,
        private readonly SubordinateListingFetcher $subordinateListingFetcher,
        private readonly EntityCollectionStoreInterface $store,
        private readonly DateIntervalDecorator $maxCacheDurationDecorator,
        private readonly ?LoggerInterface $logger = null,
        private readonly int $maxDepth = 10,
    ) {
    }


    /**
     * Discover all entity IDs in the federation rooted at $trustAnchorId.
     * Results are stored in the EntityCollectionStoreInterface and returned.
     *
     * @param non-empty-string $trustAnchorId
     * @param array<string, string|string[]> $filters  Passed through to
     * SubordinateListingFetcher
     * @param bool $forceRefresh  If true, ignore stored entity IDs and
     * re-traverse the federation
     * @return non-empty-string[]
     */
    public function discoverEntities(
        string $trustAnchorId,
        array $filters = [],
        bool $forceRefresh = false,
    ): array {
        if ($forceRefresh) {
            $this->store->clearEntityIds($trustAnchorId);
        }

        $cachedIds = $this->store->getEntityIds($trustAnchorId);
        if (is_array($cachedIds)) {
            $this->logger?->debug('Returning discovered entity IDs from store.', ['trustAnchorId' => $trustAnchorId]);
            return $cachedIds;
        }

        $this->logger?->info(
            'Starting federation discovery.',
            ['trustAnchorId' => $trustAnchorId, 'filters' => $filters],
        );

        $discoveredIds = [];
        try {
            // Step 1: Fetch TA config
            $taConfig = $this->entityStatementFetcher->fromCacheOrWellKnownEndpoint($trustAnchorId);

            // Recursive traversal
            $discoveredIds = $this->traverse($trustAnchorId, $taConfig, $filters);
            $discoveredIds = array_unique($discoveredIds);

            // Compute TTL: lowest of maxCacheDuration and TA expiry
            $ttl = $this->maxCacheDurationDecorator->lowestInSecondsComparedToExpirationTime(
                $taConfig->getExpirationTime(),
            );

            $this->store->storeEntityIds($trustAnchorId, $discoveredIds, $ttl);
            $this->logger?->info('Federation discovery completed.', [
                'trustAnchorId' => $trustAnchorId,
                'discoveredCount' => count($discoveredIds),
            ]);
        } catch (Throwable $throwable) {
            $this->logger?->error('Federation discovery failed.', [
                'trustAnchorId' => $trustAnchorId,
                'error' => $throwable->getMessage(),
            ]);
        }

        return $discoveredIds;
    }


    /**
     * @param non-empty-string $entityId
     * @param array<string, string|string[]> $filters
     * @param string[] $visited
     * @return non-empty-string[]
     */
    private function traverse(
        string $entityId,
        EntityStatement $entityConfig,
        array $filters,
        int $depth = 0,
        array $visited = [],
    ): array {
        if ($depth > $this->maxDepth || in_array($entityId, $visited, true)) {
            return [];
        }

        $visited[] = $entityId;
        $allCollectedIds = [$entityId];

        $listEndpoint = $entityConfig->getFederationListEndpoint();
        if (is_null($listEndpoint)) {
            return $allCollectedIds;
        }

        try {
            $subordinateIds = $this->subordinateListingFetcher->fetch($listEndpoint, $filters);

            foreach ($subordinateIds as $subId) {
                try {
                    $subConfig = $this->entityStatementFetcher->fromCacheOrWellKnownEndpoint($subId);
                    $allCollectedIds = array_merge(
                        $allCollectedIds,
                        $this->traverse($subId, $subConfig, $filters, $depth + 1, $visited),
                    );
                } catch (Throwable $e) {
                    $this->logger?->warning('Failed to fetch subordinate configuration during discovery.', [
                        'entityId' => $entityId,
                        'subId' => $subId,
                        'error' => $e->getMessage(),
                    ]);
                    // Still include the ID if we discovered it from the list
                    $allCollectedIds[] = $subId;
                }
            }
        } catch (Throwable $throwable) {
            $this->logger?->error('Failed to fetch subordinate listing during discovery.', [
                'entityId' => $entityId,
                'error' => $throwable->getMessage(),
            ]);
        }

        return $allCollectedIds;
    }


    /**
     * Return Entity Configurations for the given entity IDs, fetched from cache or network.
     *
     * @param non-empty-string[] $entityIds
     * @return array<string, \SimpleSAML\OpenID\Federation\EntityStatement>  keyed by entity ID
     */
    public function fetchEntityConfigurations(array $entityIds): array
    {
        $entities = [];
        foreach ($entityIds as $id) {
            try {
                $entities[$id] = $this->entityStatementFetcher->fromCacheOrWellKnownEndpoint($id);
            } catch (Throwable $e) {
                $this->logger?->warning('Failed to fetch entity configuration.', [
                    'entityId' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $entities;
    }


    /**
     * Convenience: discover entity IDs then fetch their Entity Configurations.
     *
     * @param non-empty-string $trustAnchorId
     * @param array<string, string|string[]> $filters
     * @return array<string, \SimpleSAML\OpenID\Federation\EntityStatement>
     */
    public function discoverAndFetch(
        string $trustAnchorId,
        array $filters = [],
        bool $forceRefresh = false,
    ): array {
        $ids = $this->discoverEntities($trustAnchorId, $filters, $forceRefresh);
        return $this->fetchEntityConfigurations($ids);
    }
}
