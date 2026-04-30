<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityCollection;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Federation\EntityCollection;
use SimpleSAML\OpenID\Federation\FederationDiscovery;

class EntityCollectionResponseFactory
{
    public function __construct(
        protected readonly FederationDiscovery $federationDiscovery,
        protected readonly EntityCollectionFilter $filter,
        protected readonly EntityCollectionSorter $sorter,
        protected readonly EntityCollectionPaginator $paginator,
        protected readonly EntityCollectionStoreInterface $entityCollectionStore,
    ) {
    }


    /**
     * Build an EntityCollectionResponse.
     *
     * @param non-empty-string $trustAnchorId
     * @param array{
     *   entity_type?: string[],
     *   trust_mark_type?: string,
     *   query?: string,
     *   trust_anchor?: string,
     *   sort_by?: string|string[],
     *   sort_dir?: 'asc'|'desc',
     *   entity_claims?: string[],
     *   ui_claims?: string[],
     *   limit?: positive-int|string,
     *   from?: string,
     * } $requestParams
     */
    public function build(string $trustAnchorId, array $requestParams = []): EntityCollectionResponse
    {
        // 1. Discover full configurations
        $entities = $this->federationDiscovery->discover($trustAnchorId);
        $collection = new EntityCollection(
            $this->filter,
            $this->sorter,
            $this->paginator,
            $entities->getEntities(),
        );

        // 2. Filter
        $collection->filter($requestParams);

        // 3. Sort
        if (isset($requestParams['sort_by'])) {
            $sortByParams = is_array($requestParams['sort_by'])
            ? $requestParams['sort_by']
            : [$requestParams['sort_by']];

            $claimPaths = [];
            foreach ($sortByParams as $sortBy) {
                if (!is_string($sortBy)) {
                    continue;
                }
                $claimPaths[] = explode('.', $sortBy);
            }

            if ($claimPaths !== []) {
                /** @var non-empty-array<int, non-empty-string[]> $claimPaths */
                $collection->sort(
                    $claimPaths,
                    (string)($requestParams['sort_dir'] ?? 'asc'),
                );
            }
        }

        // 4. Claims sub-selection (Projection)
        $entries = [];
        $uiClaims = $requestParams['ui_claims'] ?? null;

        foreach ($collection->getEntities() as $id => $payload) {
            $metadata = $payload[ClaimsEnum::Metadata->value] ?? [];
            if (!is_array($metadata)) {
                $metadata = [];
            }

            /** @var non-empty-string[] $entityTypes */
            $entityTypes = array_keys($metadata);

            // ui_info projection
            $uiInfo = null;
            if (is_array($uiClaims) && $uiClaims !== []) {
                $uiInfo = [];
                foreach ($metadata as $typePayload) {
                    if (!is_array($typePayload)) {
                        continue;
                    }

                    foreach ($uiClaims as $claim) {
                        if (isset($typePayload[$claim])) {
                            $uiInfo[$claim] = $typePayload[$claim];
                        }
                    }
                }
            }

            // trust_marks projection is handled by getting them from statement
            $trustMarks = null;
            $marks = $payload[ClaimsEnum::TrustMarks->value] ?? null;
            if (is_array($marks)) {
                /** @var array<array<mixed>> $marks */
                $trustMarks = $marks;
            }

            // If entity_claims is provided, we might want to filter the metadata itself,
            // but the EntityCollectionEntry DTO currently separates ui_info.
            // For now, project into the Entry VO.
            /** @var non-empty-string $id */
            $entries[$id] = new EntityCollectionEntry(
                $id,
                $entityTypes,
                $uiInfo,
                $trustMarks,
            );
        }

        // 5. Paginate
        $limit = isset($requestParams['limit']) ? (int)$requestParams['limit'] : 100;
        $limit = max(1, $limit);

        $from = $requestParams['from'] ?? null;

        $paginated = $this->paginator->paginate($entries, $limit, $from);

        return new EntityCollectionResponse(
            entities: array_values($paginated['entities']),
            next: $paginated['next'],
            lastUpdated: $this->entityCollectionStore->getLastUpdated($trustAnchorId) ?? time(),
        );
    }
}
