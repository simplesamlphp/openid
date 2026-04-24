<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityCollection;

class InMemoryEntityCollectionStore implements EntityCollectionStoreInterface
{
    /** @var array<string, array{entities: array<string, array<string, mixed>>, expires: int}> */
    private array $store = [];


    public function store(string $trustAnchorId, array $entities, int $ttl): void
    {
        $this->store[$trustAnchorId] = [
            'entities' => $entities,
            'expires' => time() + $ttl,
        ];
    }


    public function get(string $trustAnchorId): ?array
    {
        if (!isset($this->store[$trustAnchorId])) {
            return null;
        }

        if ($this->store[$trustAnchorId]['expires'] < time()) {
            unset($this->store[$trustAnchorId]);
            return null;
        }

        return $this->store[$trustAnchorId]['entities'];
    }


    public function clear(string $trustAnchorId): void
    {
        unset($this->store[$trustAnchorId]);
    }
}
