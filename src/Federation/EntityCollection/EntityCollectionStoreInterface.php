<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityCollection;

interface EntityCollectionStoreInterface
{
    /**
     * Persist the discovered entity IDs for a given Trust Anchor.
     *
     * @param non-empty-string   $trustAnchorId
     * @param non-empty-string[] $entityIds
     */
    public function storeEntityIds(string $trustAnchorId, array $entityIds, int $ttl): void;


    /**
     * Retrieve previously discovered entity IDs for a Trust Anchor.
     *
     * @param non-empty-string $trustAnchorId
     * @return non-empty-string[]|null  null when not found / expired
     */
    public function getEntityIds(string $trustAnchorId): ?array;


    /**
     * Remove all stored entity IDs for a Trust Anchor (force re-discovery).
     *
     * @param non-empty-string $trustAnchorId
     */
    public function clearEntityIds(string $trustAnchorId): void;
}
