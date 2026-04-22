<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use JsonSerializable;

class EntityCollectionResponse implements JsonSerializable
{
    /** @param \SimpleSAML\OpenID\Federation\EntityCollectionEntry[] $entities */
    public function __construct(
        public readonly array $entities,
        public readonly ?string $next = null,
        public readonly ?int $lastUpdated = null,
    ) {
    }


    /**
     * @return array{
     *     entities: \SimpleSAML\OpenID\Federation\EntityCollectionEntry[],
     *     next?: string,
     *     last_updated?: int
     * }
     */
    public function jsonSerialize(): array
    {
        $data = [
            'entities' => $this->entities,
        ];

        if (!is_null($this->next)) {
            $data['next'] = $this->next;
        }

        if (!is_null($this->lastUpdated)) {
            $data['last_updated'] = $this->lastUpdated;
        }

        return $data;
    }
}
