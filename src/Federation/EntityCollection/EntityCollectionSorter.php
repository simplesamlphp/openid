<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityCollection;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Helpers;

class EntityCollectionSorter
{
    public function __construct(
        protected readonly Helpers $helpers,
    ) {
    }


    /**
     * Sort entities by a claim nested inside their metadata.
     *
     * @param array<string, array<string, mixed>> $entities  Keyed by entity ID
     * @param non-empty-string[] $claimPath  Nested claim path within the metadata
     * object (e.g. ['federation_entity', 'display_name'])
     * @param 'asc'|'desc'  $direction
     * @return array<string, array<string, mixed>>  Sorted copy
     */
    public function sortByMetadataClaim(
        array $entities,
        array $claimPath,
        string $direction = 'asc',
    ): array {
        if ($entities === []) {
            return [];
        }

        uasort($entities, function (array $a, array $b) use ($claimPath, $direction): int {
            $metadataA = $a[ClaimsEnum::Metadata->value] ?? [];
            $metadataA = is_array($metadataA) ? $metadataA : [];

            $metadataB = $b[ClaimsEnum::Metadata->value] ?? [];
            $metadataB = is_array($metadataB) ? $metadataB : [];

            $valA = $this->helpers->arr()->getNestedValue($metadataA, ...$claimPath);
            $valB = $this->helpers->arr()->getNestedValue($metadataB, ...$claimPath);

            // Treat nulls or non-strings as empty strings for comparison
            $strA = is_string($valA) ? $valA : '';
            $strB = is_string($valB) ? $valB : '';

            $cmp = strcasecmp($strA, $strB);

            return $direction === 'desc' ? -$cmp : $cmp;
        });

        return $entities;
    }
}
