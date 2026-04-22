<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use JsonSerializable;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

class EntityCollectionEntry implements JsonSerializable
{
    /**
     * @param non-empty-string        $entityId
     * @param non-empty-string[]      $entityTypes
     * @param array<string,mixed>|null $uiInfo   Logo, display name, etc.
     * @param array<array<mixed>>|null $trustMarks
     */
    public function __construct(
        public readonly string $entityId,
        public readonly array $entityTypes,
        public readonly ?array $uiInfo = null,
        public readonly ?array $trustMarks = null,
    ) {
    }


    /**
     * @return array{
     *     entity_id: non-empty-string,
     *     entity_types: non-empty-string[],
     *     ui_info?: array<string,mixed>,
     *     trust_marks?: array<array<mixed>>
     * }
     */
    public function jsonSerialize(): array
    {
        $data = [
            'entity_id' => $this->entityId,
            ClaimsEnum::EntityTypes->value => $this->entityTypes,
        ];

        if (!is_null($this->uiInfo)) {
            $data['ui_info'] = $this->uiInfo;
        }

        if (!is_null($this->trustMarks)) {
            $data[ClaimsEnum::TrustMarks->value] = $this->trustMarks;
        }

        return $data;
    }
}
