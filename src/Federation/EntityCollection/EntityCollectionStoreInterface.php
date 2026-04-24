<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityCollection;

interface EntityCollectionStoreInterface
{
    /**
     * Persist discovered entities (ID → payload) for a given Trust Anchor.
     *
     * @param non-empty-string $trustAnchorId
     * @param array<string, array<string, mixed>> $entities  Keyed by entity ID, value is JWT payload
     */
    public function store(string $trustAnchorId, array $entities, int $ttl): void;


    /**
     * Retrieve previously discovered entities.
     *
     * @param non-empty-string $trustAnchorId
     * @return array<string, array<string, mixed>>|null  null when not found / expired
     */
    public function get(string $trustAnchorId): ?array;


    /**
     * Remove stored entities (force re-discovery).
     *
     * @param non-empty-string $trustAnchorId
     */
    public function clear(string $trustAnchorId): void;
}
