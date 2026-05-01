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


    /**
     * Set the last update timestamp for a given trust anchor.
     *
     * @param non-empty-string $trustAnchorId
     */
    public function storeLastUpdated(string $trustAnchorId, int $timestamp, int $ttl): void;


    /**
     * Get the last update timestamp for a given trust anchor.
     * @param non-empty-string $trustAnchorId
     */
    public function getLastUpdated(string $trustAnchorId): ?int;


    /**
     * Clear the last update timestamp for a given trust anchor.
     */
    public function clearLastUpdated(string $trustAnchorId): void;
}
