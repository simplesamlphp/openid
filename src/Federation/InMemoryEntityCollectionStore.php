<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

class InMemoryEntityCollectionStore implements EntityCollectionStoreInterface
{
    /** @var array<string, array{ids: non-empty-string[], expires: int}> */
    private array $store = [];


    public function storeEntityIds(string $trustAnchorId, array $entityIds, int $ttl): void
    {
        $this->store[$trustAnchorId] = [
            'ids' => $entityIds,
            'expires' => time() + $ttl,
        ];
    }


    public function getEntityIds(string $trustAnchorId): ?array
    {
        if (!isset($this->store[$trustAnchorId])) {
            return null;
        }

        if ($this->store[$trustAnchorId]['expires'] < time()) {
            unset($this->store[$trustAnchorId]);
            return null;
        }

        return $this->store[$trustAnchorId]['ids'];
    }


    public function clearEntityIds(string $trustAnchorId): void
    {
        unset($this->store[$trustAnchorId]);
    }
}
