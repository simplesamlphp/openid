<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Factories;

use SimpleSAML\OpenID\Federation\EntityCollection;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionFilter;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionPaginator;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionSorter;

class EntityCollectionFactory
{
    public function __construct(
        protected readonly EntityCollectionFilter $entityCollectionFilter,
        protected readonly EntityCollectionSorter $entityCollectionSorter,
        protected readonly EntityCollectionPaginator $entityCollectionPaginator,
    ) {
    }


    /**
     * @param array<string, array<string, mixed>> $entities  Keyed by entity ID,
     * value is JWT payload
     */
    public function build(array $entities, ?int $lastUpdated): EntityCollection
    {
        return new EntityCollection(
            $this->entityCollectionFilter,
            $this->entityCollectionSorter,
            $this->entityCollectionPaginator,
            $entities,
            $lastUpdated,
        );
    }
}
