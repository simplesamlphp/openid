<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityCollection;

use JsonSerializable;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

class EntityCollectionResponse implements JsonSerializable
{
    /** @param \SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionEntry[] $entities */
    public function __construct(
        public readonly array $entities,
        public readonly ?string $next = null,
        public readonly ?int $lastUpdated = null,
    ) {
    }


    /**
     * @return array{
     *     entities: \SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionEntry[],
     *     next?: string,
     *     last_updated?: int
     * }
     */
    public function jsonSerialize(): array
    {
        $data = [
            ClaimsEnum::Entities->value => $this->entities,
        ];

        if (!is_null($this->next)) {
            $data[ClaimsEnum::Next->value] = $this->next;
        }

        if (!is_null($this->lastUpdated)) {
            $data[ClaimsEnum::LastUpdated->value] = $this->lastUpdated;
        }

        return $data;
    }
}
