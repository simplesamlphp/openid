<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityCollection;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Helpers;

class EntityCollectionPaginator
{
    public function __construct(
        protected readonly Helpers $helpers,
    ) {
    }


    /**
     * @param array<string, array<string, mixed>> $entities The list of entities
     * to be paginate, ordered (pre-sorted).
     * @param positive-int $limit Maximum number of entries to return
     * @param string|null  $from Opaque cursor (base64 encoded entity ID to start AFTER)
     * @return array{entities: array<string, array<string, mixed>>, next: ?string}
     */
    public function paginate(array $entities, int $limit, ?string $from = null): array
    {
        $keys = array_keys($entities);
        $offset = 0;

        if (!is_null($from)) {
            $fromId = $this->helpers->base64Url()->decode($from);
            $index = array_search($fromId, $keys, true);
            if ($index !== false) {
                $offset = $index + 1;
            }
        }

        $pageItems = array_slice($entities, $offset, $limit, true);
        $next = null;

        if ($offset + $limit < count($keys)) {
            $lastIdInPage = array_key_last($pageItems);
            if ($lastIdInPage !== null) {
                $next = $this->helpers->base64Url()->encode((string)$lastIdInPage);
            }
        }

        return [
            ClaimsEnum::Entities->value => $pageItems,
            ClaimsEnum::Next->value => $next,
        ];
    }
}
