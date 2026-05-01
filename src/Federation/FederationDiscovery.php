<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\EntityDiscoveryException;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionStoreInterface;
use SimpleSAML\OpenID\Federation\Factories\EntityCollectionFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;
use Throwable;

class FederationDiscovery
{
    public function __construct(
        protected readonly EntityStatementFetcher $entityStatementFetcher,
        protected readonly SubordinateListingFetcher $subordinateListingFetcher,
        protected readonly EntityCollectionStoreInterface $entityCollectionStore,
        protected readonly DateIntervalDecorator $maxCacheDurationDecorator,
        protected readonly EntityCollectionFactory $entityCollectionFactory,
        protected readonly ArtifactFetcher $artifactFetcher,
        protected readonly Helpers $helpers,
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
     */
    public function discover(
        string $trustAnchorId,
        array $filters = [],
        bool $forceRefresh = false,
    ): EntityCollection {
        if (!$forceRefresh) {
            $cachedEntities = $this->entityCollectionStore->get($trustAnchorId);
            if (is_array($cachedEntities)) {
                $this->logger?->debug(
                    'Returning discovered entities from entity collection store.',
                    ['trustAnchorId' => $trustAnchorId],
                );
                return $this->entityCollectionFactory->build(
                    $cachedEntities,
                    $this->entityCollectionStore->getLastUpdated($trustAnchorId),
                );
            }
        }

        $this->logger?->info(
            'Starting federation discovery.',
            ['trustAnchorId' => $trustAnchorId, 'filters' => $filters],
        );

        $discoveredEntities = [];
        $lastUpdated = null;
        try {
            // Step 1: Fetch TA config
            $taConfig = $this->entityStatementFetcher->fromCacheOrWellKnownEndpoint($trustAnchorId);

            // Recursive traversal
            $discoveredEntities = $this->traverse($trustAnchorId, $taConfig, $filters, 0, [], $forceRefresh);

            // Compute TTL: lowest of maxCacheDuration and TA expiry
            $ttl = $this->maxCacheDurationDecorator->lowestInSecondsComparedToExpirationTime(
                $taConfig->getExpirationTime(),
            );

            ksort($discoveredEntities);

            $this->entityCollectionStore->store($trustAnchorId, $discoveredEntities, $ttl);
            $lastUpdated = time();
            $this->entityCollectionStore->storeLastUpdated($trustAnchorId, $lastUpdated, $ttl);

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

        return $this->entityCollectionFactory->build($discoveredEntities, $lastUpdated);
    }


    /**
     * Fetch an entity collection from a remote endpoint.
     *
     * @param non-empty-string $endpointUri
     * @param array{
     *   entity_type?: string[],
     *   trust_mark_type?: string[],
     *   query?: string,
     *   trust_anchor?: string,
     *   entity_claims?: string[],
     *   ui_claims?: string[],
     *   limit?: positive-int,
     *   from?: string,
     * } $filters
     * @throws \SimpleSAML\OpenID\Exceptions\EntityDiscoveryException
     */
    public function fetchFromCollectionEndpoint(
        string $endpointUri,
        array $filters = [],
        bool $forceRefresh = false,
    ): EntityCollection {
        $uri = $this->helpers->url()->withMultiValueParams($endpointUri, $filters);

        if (!$forceRefresh) {
            $this->logger?->debug('Checking for cached entity collection.', ['uri' => $uri]);
            $cached = $this->artifactFetcher->fromCacheAsString($uri);
            if ($cached !== null) {
                $this->logger?->debug('Returning cached entity collection.', ['uri' => $uri]);
                return $this->buildEntityCollectionFromResponse($cached);
            }

            $this->logger?->debug('No cached entity collection found.', ['uri' => $uri]);
        }

        $this->logger?->debug('Fetching entity collection.', ['uri' => $uri, 'filters' => $filters]);

        try {
            $responseBody = $this->artifactFetcher->fromNetworkAsString($uri);

            $collection = $this->buildEntityCollectionFromResponse($responseBody);

            $this->artifactFetcher->cacheIt(
                $responseBody,
                $this->maxCacheDurationDecorator->getInSeconds(),
                $uri,
            );

            $this->logger?->debug('Fetched and cached entity collection.', ['uri' => $uri]);

            return $collection;
        } catch (Throwable $throwable) {
            $message = sprintf('Unable to fetch entity collection from %s. Error: %s', $uri, $throwable->getMessage());
            $this->logger?->error($message);
            throw new EntityDiscoveryException($message, (int)$throwable->getCode(), $throwable);
        }
    }


    protected function buildEntityCollectionFromResponse(string $responseBody): EntityCollection
    {
        $decoded = $this->helpers->json()->decode($responseBody);

        if (
            !is_array($decoded) ||
            !isset($decoded[ClaimsEnum::Entities->value]) ||
            !is_array($decoded[ClaimsEnum::Entities->value])
        ) {
            throw new EntityDiscoveryException('Entity collection response is missing "entities" array.');
        }

        $entities = [];
        foreach ($decoded[ClaimsEnum::Entities->value] as $entryData) {
            if (!is_array($entryData)) {
                continue;
            }

            $entityId = $this->helpers->type()->ensureNonEmptyString(
                $entryData[ClaimsEnum::EntityId->value] ?? null,
                ClaimsEnum::EntityId->value,
            );

            $metadata = [];
            $uiInfos = $entryData[ClaimsEnum::UiInfos->value] ?? [];
            if (is_array($uiInfos)) {
                foreach ($uiInfos as $type => $typePayload) {
                    if (is_string($type) && is_array($typePayload)) {
                        $metadata[$type] = $typePayload;
                    }
                }
            }

            $payload = [
                ClaimsEnum::Sub->value => $entityId,
                ClaimsEnum::Metadata->value => $metadata,
            ];

            if (isset($entryData[ClaimsEnum::TrustMarks->value])) {
                $payload[ClaimsEnum::TrustMarks->value] = $entryData[ClaimsEnum::TrustMarks->value];
            }

            $entities[$entityId] = $payload;
        }

        $next = is_string($next = $decoded[ClaimsEnum::Next->value] ?? null) ? $next : null;
        $lastUpdated = is_numeric($lastUpdated = $decoded[ClaimsEnum::LastUpdated->value] ?? null) ?
        $this->helpers->type()->ensureInt($lastUpdated) :
        null;

        return $this->entityCollectionFactory->build(
            $entities,
            $lastUpdated,
            $next,
        );
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
        return array_keys($this->discover($trustAnchorId, $filters, $forceRefresh)->getEntities());
    }


    /**
     * @param non-empty-string $entityId
     * @param array<string, string|string[]> $filters
     * @param string[] $visited
     * @return array<string, array<string, mixed>>
     */
    protected function traverse(
        string $entityId,
        EntityStatement $entityConfig,
        array $filters,
        int $depth = 0,
        array $visited = [],
        bool $forceRefresh = false,
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
            $subordinateIds = $this->subordinateListingFetcher->fetch($listEndpoint, $filters, $forceRefresh);

            foreach ($subordinateIds as $subId) {
                // If we've already visited this subId (loop), skip to avoid infinite recursion
                if (in_array($subId, $visited, true)) {
                    continue;
                }

                try {
                    $subConfig = $this->entityStatementFetcher->fromCacheOrWellKnownEndpoint($subId);
                    $allCollectedEntities = array_merge(
                        $allCollectedEntities,
                        $this->traverse($subId, $subConfig, $filters, $depth + 1, $visited, $forceRefresh),
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
