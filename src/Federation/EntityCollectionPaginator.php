<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

class EntityCollectionPaginator
{
    /**
     * @template T
     * @param array<string, T> $entities  Full ordered result set (pre-sorted)
     * @param positive-int $limit  Maximum number of entries to return
     * @param string|null  $from   Opaque cursor (base64 encoded entity ID to start AFTER)
     * @return array{entities: array<string, T>, next: ?string}
     */
    public function paginate(array $entities, int $limit, ?string $from = null): array
    {
        $keys = array_keys($entities);
        $offset = 0;

        if (!is_null($from)) {
            $fromId = base64_decode($from, true);
            if ($fromId !== false) {
                $index = array_search($fromId, $keys, true);
                if ($index !== false) {
                    $offset = $index + 1;
                }
            }
        }

        $pageItems = array_slice($entities, $offset, $limit, true);
        $next = null;

        if ($offset + $limit < count($keys)) {
            $lastIdInPage = array_key_last($pageItems);
            $next = base64_encode((string)$lastIdInPage);
        }

        return [
            'entities' => $pageItems,
            'next' => $next,
        ];
    }
}
