<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityCollection;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Helpers;

class EntityCollectionFilter
{
    public function __construct(
        protected readonly Helpers $helpers,
    ) {
    }


    /**
     * Filters a list of entities based on the provided criteria.
     *
     * The method applies multiple filters in the following order:
     * 1. Filters entities by their type, based on the 'entity_type' criteria.
     * 2. Filters entities by their trust mark type, based on the
     * 'trust_mark_type' criteria.
     * 3. Filters entities by a textual query that checks multiple fields
     * (e.g., `display_name` or `organization_name`).
     *
     * @param array<string, array<string, mixed>> $entities The list of entities
     * to be filtered. Each entity is expected to be an associative array.
     * @param array{
     *    entity_type?: string[],
     *    trust_mark_type?: string[],
     *    query?: string,
     *  } $criteria The array of filtering criteria. It may contain:
     *  - 'entity_type': An array of entity types to filter by.
     *  - 'trust_mark_type': An array of trust mark types to filter by.
     *  - 'query': A string used to perform a case-insensitive search on
     *  specific fields.
     * @return array<string, array<string, mixed>> The filtered list of entities
     * that match all provided criteria.
     */
    public function filter(array $entities, array $criteria): array
    {
        // 1. entity_type
        if (isset($criteria['entity_type']) && $criteria['entity_type'] !== []) {
            $types = $criteria['entity_type'];
            $entities = array_filter($entities, function (array $payload) use ($types): bool {
                $metadata = $payload[ClaimsEnum::Metadata->value] ?? null;
                if (!is_array($metadata)) {
                    return false;
                }

                foreach ($types as $type) {
                    if (isset($metadata[$type])) {
                        return true;
                    }
                }

                return false;
            });
        }

        // 2. trust_mark_type
        if (isset($criteria['trust_mark_type']) && $criteria['trust_mark_type'] !== []) {
            $criteriaTrustMarkTypes = $criteria['trust_mark_type'];
            $entities = array_filter($entities, function (array $payload) use ($criteriaTrustMarkTypes): bool {
                $entityTrustMarks = $payload[ClaimsEnum::TrustMarks->value] ?? null;
                if (!is_array($entityTrustMarks)) {
                    return false;
                }

                $entityTrustMarkTypes = [];
                foreach ($entityTrustMarks as $mark) {
                    if (is_array($mark) && isset($mark[ClaimsEnum::TrustMarkType->value])) {
                        $entityTrustMarkTypes[] = $mark[ClaimsEnum::TrustMarkType->value];
                    }
                }

                foreach ($criteriaTrustMarkTypes as $tmType) {
                    if (!in_array($tmType, $entityTrustMarkTypes, true)) {
                        return false;
                    }
                }

                return true;
            });
        }

        // 3. query
        if (isset($criteria['query']) && $criteria['query'] !== '') {
            $q = mb_strtolower($criteria['query']);
            $entities = array_filter($entities, function (array $payload) use ($q): bool {
                $sub = is_string($payload[ClaimsEnum::Sub->value] ?? null) ?
                mb_strtolower($payload[ClaimsEnum::Sub->value]) :
                '';
                if ($sub !== '' && str_contains($sub, $q)) {
                    return true;
                }

                $metadata = $payload[ClaimsEnum::Metadata->value] ?? null;
                if (!is_array($metadata)) {
                    return false;
                }

                // Check display_name or organization_name in any entity type
                foreach ($metadata as $typePayload) {
                    if (!is_array($typePayload)) {
                        continue;
                    }

                    $displayNameValue = $typePayload[ClaimsEnum::DisplayName->value] ?? '';
                    $displayName = mb_strtolower(is_string($displayNameValue) ? $displayNameValue : '');
                    if ($displayName !== '' && str_contains($displayName, $q)) {
                        return true;
                    }

                    $orgNameValue = $typePayload[ClaimsEnum::OrganizationName->value] ?? '';
                    $orgName = mb_strtolower(is_string($orgNameValue) ? $orgNameValue : '');
                    if ($orgName !== '' && str_contains($orgName, $q)) {
                        return true;
                    }
                }

                return false;
            });
        }

        return $entities;
    }
}
