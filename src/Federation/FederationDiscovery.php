<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionStoreInterface;
use Throwable;

class FederationDiscovery
{
    public function __construct(
        protected readonly EntityStatementFetcher $entityStatementFetcher,
        protected readonly SubordinateListingFetcher $subordinateListingFetcher,
        protected readonly EntityCollectionStoreInterface $entityCollectionStore,
        protected readonly DateIntervalDecorator $maxCacheDurationDecorator,
        protected readonly ?LoggerInterface $logger = null,
        protected readonly int $maxDepth = 10,
    ) {
    }


    /**
     * Discover all entities (ID -> payload map) in the federation rooted at $trustAnchorId.
     * Results are stored in the EntityCollectionStoreInterface and returned.
     *
     * @param non-empty-string $trustAnchorId
     * @param array<string, string|string[]> $filters  Passed through to
     * SubordinateListingFetcher
     * @param bool $forceRefresh  If true, ignore stored entities and
     * re-traverse the federation
     * @return array<string, array<string, mixed>>
     */
    public function discover(
        string $trustAnchorId,
        array $filters = [],
        bool $forceRefresh = false,
    ): array {
        if (!$forceRefresh) {
            $cachedEntities = $this->entityCollectionStore->get($trustAnchorId);
            if (is_array($cachedEntities)) {
                $this->logger?->debug(
                    'Returning discovered entities from store.',
                    ['trustAnchorId' => $trustAnchorId],
                );
                return $cachedEntities;
            }
        }

        $this->logger?->info(
            'Starting federation discovery.',
            ['trustAnchorId' => $trustAnchorId, 'filters' => $filters],
        );

        $discoveredEntities = [];
        try {
            // Step 1: Fetch TA config
            $taConfig = $this->entityStatementFetcher->fromCacheOrWellKnownEndpoint($trustAnchorId);

            // Recursive traversal
            $discoveredEntities = $this->traverse($trustAnchorId, $taConfig, $filters);

            // Compute TTL: lowest of maxCacheDuration and TA expiry
            $ttl = $this->maxCacheDurationDecorator->lowestInSecondsComparedToExpirationTime(
                $taConfig->getExpirationTime(),
            );

            $this->entityCollectionStore->store($trustAnchorId, $discoveredEntities, $ttl);
            $this->logger?->info('Federation discovery completed.', [
                'trustAnchorId' => $trustAnchorId,
                'discoveredCount' => count($discoveredEntities),
            ]);
        } catch (Throwable $throwable) {
            $this->logger?->error('Federation discovery failed.', [
                'trustAnchorId' => $trustAnchorId,
                'error' => $throwable->getMessage(),
            ]);
        }

        return $discoveredEntities;
    }


    /**
     * Discover just the entity IDs in the federation.
     *
     * @param non-empty-string $trustAnchorId
     * @param array<string, string|string[]> $filters
     * @return string[]
     */
    public function discoverEntityIds(
        string $trustAnchorId,
        array $filters = [],
        bool $forceRefresh = false,
    ): array {
        return array_keys($this->discover($trustAnchorId, $filters, $forceRefresh));
    }


    /**
     * @param non-empty-string $entityId
     * @param array<string, string|string[]> $filters
     * @param string[] $visited
     * @return array<string, array<string, mixed>>
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
        $allCollectedEntities = [$entityId => $entityConfig->getPayload()];

        $listEndpoint = $entityConfig->getFederationListEndpoint();
        if (is_null($listEndpoint)) {
            return $allCollectedEntities;
        }

        try {
            $subordinateIds = $this->subordinateListingFetcher->fetch($listEndpoint, $filters);

            foreach ($subordinateIds as $subId) {
                // If we've already visited this subId (loop), skip to avoid infinite recursion
                if (in_array($subId, $visited, true)) {
                    continue;
                }

                try {
                    $subConfig = $this->entityStatementFetcher->fromCacheOrWellKnownEndpoint($subId);
                    $allCollectedEntities = array_merge(
                        $allCollectedEntities,
                        $this->traverse($subId, $subConfig, $filters, $depth + 1, $visited),
                    );
                } catch (Throwable $e) {
                    $this->logger?->warning('Failed to fetch subordinate configuration during discovery.', [
                        'entityId' => $entityId,
                        'subId' => $subId,
                        'error' => $e->getMessage(),
                    ]);
                    // Still include the ID if we discovered it from the list, but with an empty payload
                    if (!isset($allCollectedEntities[$subId])) {
                        $allCollectedEntities[$subId] = [];
                    }
                }
            }
        } catch (Throwable $throwable) {
            $this->logger?->error('Failed to fetch subordinate listing during discovery.', [
                'entityId' => $entityId,
                'error' => $throwable->getMessage(),
            ]);
        }

        return $allCollectedEntities;
    }
}
