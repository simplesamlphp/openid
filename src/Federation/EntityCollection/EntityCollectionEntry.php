<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityCollection;

use JsonSerializable;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

class EntityCollectionEntry implements JsonSerializable
{
    /**
     * @param non-empty-string $entityId
     * @param non-empty-string[] $entityTypes
     * @param array<string,mixed>|null $uiInfos   Logo, display name, etc.
     * @param array<array<mixed>>|null $trustMarks
     */
    public function __construct(
        public readonly string $entityId,
        public readonly array $entityTypes,
        public readonly ?array $uiInfos = null,
        public readonly ?array $trustMarks = null,
    ) {
    }


    /**
     * @return array{
     *     entity_id: non-empty-string,
     *     entity_types: non-empty-string[],
     *     ui_infos?: array<string,mixed>,
     *     trust_marks?: array<array<mixed>>
     * }
     */
    public function jsonSerialize(): array
    {
        $data = [
            ClaimsEnum::EntityId->value => $this->entityId,
            ClaimsEnum::EntityTypes->value => $this->entityTypes,
        ];

        if (!is_null($this->uiInfos)) {
            $data[ClaimsEnum::UiInfos->value] = $this->uiInfos;
        }

        if (!is_null($this->trustMarks)) {
            $data[ClaimsEnum::TrustMarks->value] = $this->trustMarks;
        }

        return $data;
    }
}
