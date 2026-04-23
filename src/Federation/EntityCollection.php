<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

class EntityCollection
{
    /**
     * @param array<string, \SimpleSAML\OpenID\Federation\EntityStatement> $entities  Keyed by entity ID
     */
    public function __construct(
        protected readonly array $entities,
    ) {
    }


    /**
     * @return array<string, \SimpleSAML\OpenID\Federation\EntityStatement>
     */
    public function all(): array
    {
        return $this->entities;
    }
}
