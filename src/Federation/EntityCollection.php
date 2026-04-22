<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

class EntityCollection
{
    /**
     * @param array<string, \SimpleSAML\OpenID\Federation\EntityStatement> $entities  Keyed by entity ID
     */
    public function __construct(
        public readonly array $entities,
    ) {
    }
}
