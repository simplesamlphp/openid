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

/**
 * @see \SimpleSAML\Test\OpenID\Federation\FederationDiscoveryTest
 */
class FederationDiscovery
{
    /**
     * An entity collection response carries metadata per entity, so it runs larger than a bare listing.
     */
    public const DEFAULT_MAX_FETCH_SIZE_BYTES = 1048576;

    public const DEFAULT_MAX_DISCOVERED_ENTITIES = 10000;


    protected int $maxDiscoveredEntities;

    protected int $maxFetchSizeBytes;


    /**
     * @param int $maxDiscoveredEntities Total number of entities a single discovery may collect. Depth alone
     * does not bound the traversal, since one listing endpoint can name any number of subordinates.
     * @param int $maxFetchSizeBytes Maximum response body size to read for an entity collection.
     */
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
        int $maxDiscoveredEntities = self::DEFAULT_MAX_DISCOVERED_ENTITIES,
        int $maxFetchSizeBytes = self::DEFAULT_MAX_FETCH_SIZE_BYTES,
    ) {
        $this->maxDiscoveredEntities = max(1, $maxDiscoveredEntities);
        $this->maxFetchSizeBytes = max(1, $maxFetchSizeBytes);
    }


    public function getMaxDiscoveredEntities(): int
    {
        return $this->maxDiscoveredEntities;
    }


    public function getMaxFetchSizeBytes(): int
    {
        return $this->maxFetchSizeBytes;
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

            $discoveredEntities = $this->traverse($trustAnchorId, $taConfig, $filters, $forceRefresh);

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
            $responseBody = $this->artifactFetcher->fromNetworkAsString($uri, $this->maxFetchSizeBytes);

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
     * Walk the federation from the given entity, breadth first.
     *
     * Breadth first rather than depth first so that every entity is reached by its shortest path. Depth
     * first would reach some of them the long way round first, and whether their own subordinates then fell
     * inside the depth limit would come down to the order authorities happen to list them in.
     *
     * @param non-empty-string $rootId
     * @param array<string, string|string[]> $filters
     * @return array<string, array<string, mixed>>
     */
    protected function traverse(
        string $rootId,
        EntityStatement $rootConfig,
        array $filters,
        bool $forceRefresh = false,
    ): array {
        $allCollectedEntities = [$rootId => $rootConfig->getPayload()];
        // Doubles as the entity budget: every entry costs exactly one fetch, so its size bounds both the
        // work done and the result returned.
        $visited = [$rootId => true];
        $queue = [[$rootId, $rootConfig, 0]];

        while ($queue !== []) {
            /** @var array{0:non-empty-string,1:\SimpleSAML\OpenID\Federation\EntityStatement,2:int} $entry */
            $entry = array_shift($queue);
            [$entityId, $entityConfig, $depth] = $entry;

            // Anything below this node sits past the depth limit, so neither the listing nor the
            // configurations it names are worth fetching.
            if ($depth >= $this->maxDepth) {
                continue;
            }

            $listEndpoint = $entityConfig->getFederationListEndpoint();
            if (is_null($listEndpoint)) {
                continue;
            }

            try {
                $subordinateIds = $this->subordinateListingFetcher->fetch($listEndpoint, $filters, $forceRefresh);
            } catch (Throwable $throwable) {
                $this->logger?->error('Failed to fetch subordinate listing during discovery.', [
                    'entityId' => $entityId,
                    'error' => $throwable->getMessage(),
                ]);
                continue;
            }

            foreach ($subordinateIds as $subId) {
                // Already reached, by a path no longer than this one. Also what stops a cycle.
                if (isset($visited[$subId])) {
                    continue;
                }

                // Checked before the fetch below, not after: the fetch is the cost being bounded, and every
                // listed ID would otherwise be fetched even once there is no room left to keep it.
                if (count($visited) >= $this->maxDiscoveredEntities) {
                    $this->logger?->warning(
                        sprintf(
                            'Discovery reached the maximum of %s entities, so the traversal stops here and ' .
                            'the result is incomplete.',
                            $this->maxDiscoveredEntities,
                        ),
                        ['entityId' => $entityId],
                    );

                    break 2;
                }

                $visited[$subId] = true;

                try {
                    $subConfig = $this->entityStatementFetcher->fromCacheOrWellKnownEndpoint($subId);
                    $allCollectedEntities[$subId] = $subConfig->getPayload();
                    $queue[] = [$subId, $subConfig, $depth + 1];
                } catch (Throwable $e) {
                    $this->logger?->warning('Failed to fetch subordinate configuration during discovery.', [
                        'entityId' => $entityId,
                        'subId' => $subId,
                        'error' => $e->getMessage(),
                    ]);
                    // Still include the ID if we discovered it from the list, but with an empty payload
                    $allCollectedEntities[$subId] = [];
                }
            }
        }

        return $allCollectedEntities;
    }
}
