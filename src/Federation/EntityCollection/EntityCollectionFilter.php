<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityCollection;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Federation\EntityCollection;
use SimpleSAML\OpenID\Helpers;

class EntityCollectionFilter
{
    public function __construct(
        protected readonly Helpers $helpers,
    ) {
    }


    /**
     * @param array{
     *   entity_type?: string[],
     *   trust_mark_type?: string,
     *   query?: string,
     *   trust_anchor?: string,
     * } $criteria
     * @return array<string, array<string, mixed>>  Filtered
     * entity payloads keyed by entity ID
     */
    public function filter(EntityCollection $entityCollection, array $criteria): array
    {
        $filtered = $entityCollection->all();

        // 1. entity_type
        if (isset($criteria['entity_type']) && $criteria['entity_type'] !== []) {
            $types = $criteria['entity_type'];
            $filtered = array_filter($filtered, function (array $payload) use ($types): bool {
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
        if (isset($criteria['trust_mark_type'])) {
            $tmType = $criteria['trust_mark_type'];
            $filtered = array_filter($filtered, function (array $payload) use ($tmType): bool {
                $marks = $payload[ClaimsEnum::TrustMarks->value] ?? null;
                if (is_array($marks)) {
                    foreach ($marks as $mark) {
                        if (is_array($mark) && ($mark[ClaimsEnum::TrustMarkType->value] ?? null) === $tmType) {
                            return true;
                        }
                    }
                }

                return false;
            });
        }

        // 3. query
        if (isset($criteria['query']) && $criteria['query'] !== '') {
            $q = mb_strtolower($criteria['query']);
            $filtered = array_filter($filtered, function (array $payload) use ($q): bool {
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

        // 4. trust_anchor (simple prefix match for now as per spec suggestion,
        // or more complex if needed). Historically, in some federation
        // implementations, subordination is indicated via id prefix or
        // specific claims. For this building block, we'll implement it as a
        // filter on the authority hint if possible.
        if (isset($criteria['trust_anchor'])) {
            $ta = $criteria['trust_anchor'];
            $filtered = array_filter($filtered, function (array $payload) use ($ta): bool {
                // In a top-down traversal, everything is subordinate to the TA we started with.
                // If the collection contains multiple TAs, we would check authority_hints.
                $hints = $this->helpers->arr()->getNestedValue(
                    $payload,
                    ClaimsEnum::AuthorityHints->value,
                );
                if (is_array($hints)) {
                    return in_array($ta, $hints, true);
                }

                return false;
            });
        }

        return $filtered;
    }
}
