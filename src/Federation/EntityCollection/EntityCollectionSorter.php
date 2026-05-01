<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityCollection;

use SimpleSAML\OpenID\Exceptions\OpenIdException;
use SimpleSAML\OpenID\Helpers;

class EntityCollectionSorter
{
    public function __construct(
        protected readonly Helpers $helpers,
    ) {
    }


    /**
     * Sort entities by one or more claims nested inside their payload.
     *
     * @param array<string, array<string, mixed>> $entities  Keyed by entity ID
     * @param non-empty-array<int, non-empty-string[]> $claimPaths Array of
     * nested claim paths within the entity payload object
     * (e.g. [['metadata', 'openid_provider', 'display_name'], ['metadata', 'federation_entity', 'display_name']])
     * @param 'asc'|'desc'  $direction
     * @return array<string, array<string, mixed>>  Sorted copy
     */
    public function sort(
        array $entities,
        array $claimPaths,
        string $direction = 'asc',
    ): array {
        if ($entities === []) {
            return [];
        }

        uasort($entities, function (array $a, array $b) use ($claimPaths, $direction): int {
            foreach ($claimPaths as $claimPath) {
                try {
                    $valA = $this->helpers->arr()->getNestedValue($a, ...$claimPath);
                } catch (OpenIdException) {
                    // If the claim path doesn't exist, treat it as null
                    $valA = null;
                }

                try {
                    $valB = $this->helpers->arr()->getNestedValue($b, ...$claimPath);
                } catch (OpenIdException) {
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
