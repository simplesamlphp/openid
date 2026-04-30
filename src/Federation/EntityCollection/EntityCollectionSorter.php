<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityCollection;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\OpenIdException;
use SimpleSAML\OpenID\Helpers;

class EntityCollectionSorter
{
    public function __construct(
        protected readonly Helpers $helpers,
    ) {
    }


    /**
     * Sort entities by one or more claims nested inside their metadata.
     *
     * @param array<string, array<string, mixed>> $entities  Keyed by entity ID
     * @param non-empty-array<int, non-empty-string[]> $claimPaths Array of
     * nested claim paths within the metadata object
     * (e.g. [['openid_provider', 'display_name'], ['federation_entity', 'display_name']])
     * @param 'asc'|'desc'  $direction
     * @return array<string, array<string, mixed>>  Sorted copy
     */
    public function sortByMetadataClaims(
        array $entities,
        array $claimPaths,
        string $direction = 'asc',
    ): array {
        if ($entities === []) {
            return [];
        }

        uasort($entities, function (array $a, array $b) use ($claimPaths, $direction): int {
            $metadataA = $a[ClaimsEnum::Metadata->value] ?? [];
            $metadataA = is_array($metadataA) ? $metadataA : [];

            $metadataB = $b[ClaimsEnum::Metadata->value] ?? [];
            $metadataB = is_array($metadataB) ? $metadataB : [];

            foreach ($claimPaths as $claimPath) {
                try {
                    $valA = $this->helpers->arr()->getNestedValue($metadataA, ...$claimPath);
                } catch (OpenIdException $e) {
                    // If the claim path doesn't exist, treat it as null
                    $valA = null;
                }
                try {
                    $valB = $this->helpers->arr()->getNestedValue($metadataB, ...$claimPath);
                } catch (OpenIdException $e) {
                    // If the claim path doesn't exist, treat it as null
                    $valB = null;
                }

                // Treat nulls or non-strings as empty strings for comparison
                $strA = is_string($valA) ? $valA : '';
                $strB = is_string($valB) ? $valB : '';

                $cmp = strcasecmp($strA, $strB);

                if ($cmp !== 0) {
                    return $direction === 'desc' ? -$cmp : $cmp;
                }
            }

            return 0;
        });

        return $entities;
    }
}
