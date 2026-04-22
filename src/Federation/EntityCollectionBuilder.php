<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

class EntityCollectionBuilder
{
    public function __construct(
        private readonly FederationDiscovery $federationDiscovery,
        private readonly EntityCollectionFilter $filter,
        private readonly EntityCollectionSorter $sorter,
        private readonly EntityCollectionPaginator $paginator,
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
     *   sort_by?: string,
     *   sort_dir?: 'asc'|'desc',
     *   entity_claims?: string[],
     *   ui_claims?: string[],
     *   limit?: positive-int|string,
     *   from?: string,
     * } $requestParams
     */
    public function build(string $trustAnchorId, array $requestParams = []): EntityCollectionResponse
    {
        // 1. Discover and fetch full configurations
        $entities = $this->federationDiscovery->discoverAndFetch($trustAnchorId);
        $collection = new EntityCollection($entities);

        // 2. Filter
        $filtered = $this->filter->filter($collection, $requestParams);

        // 3. Sort
        if (isset($requestParams['sort_by'])) {
            $path = explode('.', $requestParams['sort_by']);
            /** @var non-empty-string[] $path */
            $filtered = $this->sorter->sortByMetadataClaim(
                $filtered,
                $path,
                (string)($requestParams['sort_dir'] ?? 'asc'),
            );
        }

        // 4. Claims sub-selection (Projection)
        $entries = [];
        $uiClaims = $requestParams['ui_claims'] ?? null;

        foreach ($filtered as $id => $statement) {
            $metadata = $statement->getMetadata() ?? [];
            /** @var non-empty-string[] $entityTypes */
            $entityTypes = array_keys($metadata);

            // ui_info projection
            $uiInfo = null;
            if (is_array($uiClaims) && $uiClaims !== []) {
                $uiInfo = [];
                foreach ($metadata as $payload) {
                    if (!is_array($payload)) {
                        continue;
                    }

                    foreach ($uiClaims as $claim) {
                        if (isset($payload[$claim])) {
                            $uiInfo[$claim] = $payload[$claim];
                        }
                    }
                }
            }

            // trust_marks projection is handled by getting them from statement
            $trustMarks = null;
            try {
                // In a real projection, we might filter which trust marks to return,
                // but for now we return all if asked or if no specific selection is implemented.
                $trustMarks = $statement->getTrustMarks();
            } catch (\Throwable) {
            }

            // If entity_claims is provided, we might want to filter the metadata itself,
            // but the EntityCollectionEntry DTO currently separates ui_info.
            // For now, project into the Entry VO.
            /** @var non-empty-string $id */
            $entries[$id] = new EntityCollectionEntry(
                $id,
                $entityTypes,
                $uiInfo,
                $trustMarks?->jsonSerialize(),
            );
        }

        // 5. Paginate
        $limit = isset($requestParams['limit']) ? (int)$requestParams['limit'] : 100;
        $limit = max(1, $limit);

        $from = $requestParams['from'] ?? null;

        $paginated = $this->paginator->paginate($entries, $limit, $from);

        return new EntityCollectionResponse(
            array_values($paginated['entities']),
            $paginated['next'],
            time(), // last_updated
        );
    }
}
