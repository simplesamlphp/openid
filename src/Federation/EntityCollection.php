<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionFilter;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionPaginator;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionSorter;

class EntityCollection
{
    /**
     * @param array<string, array<string, mixed>> $entities  Keyed by entity ID,
     * value is JWT payload
     */
    public function __construct(
        protected readonly EntityCollectionFilter $entityCollectionFilter,
        protected readonly EntityCollectionSorter $entityCollectionSorter,
        protected readonly EntityCollectionPaginator $entityCollectionPaginator,
        protected array $entities,
        protected ?string $nextPageToken = null,
        protected ?int $lastUpdated = null,
    ) {
    }


    /**
     * @return array<string, array<string, mixed>>
     */
    public function getEntities(): array
    {
        return $this->entities;
    }


    /**
     * Apply filters to the collection. Supported criteria keys:
     * - entity_type: array of entity types to include
     * (e.g. ['openid_relying_party'])
     * - trust_mark_type: array of trust mark types to include
     * (e.g. ['https://example.com/marks/approved'])
     * - query: string to search for in display_name, organization_name,
     * and entity_id (case-insensitive)
     *
     * @param array{
     *    entity_type?: string[],
     *    trust_mark_type?: string[],
     *    query?: string,
     *  } $criteria
     * @return $this
     */
    public function filter(array $criteria): static
    {
        $this->entities = $this->entityCollectionFilter->filter($this->entities, $criteria);

        return $this;
    }


    /**
     * @param non-empty-array<int, non-empty-string[]> $claimPaths
     * @param 'asc'|'desc' $sortOrder
     * @return $this
     */
    public function sort(array $claimPaths, string $sortOrder): static
    {
        $this->entities = $this->entityCollectionSorter->sort(
            $this->entities,
            $claimPaths,
            $sortOrder,
        );

        return $this;
    }


    /**
     * @param positive-int $limit Maximum number of entries to return
     * @param string|null $from Opaque cursor (base64 encoded entity ID to start AFTER)
     */
    public function paginate(int $limit, ?string $from = null): static
    {
        [
            'entities' => $this->entities,
            'next' => $this->nextPageToken,
            ] = $this->entityCollectionPaginator->paginate(
                $this->entities,
                $limit,
                $from,
            );

        return $this;
    }


    public function getNextPageToken(): ?string
    {
        return $this->nextPageToken;
    }
}
