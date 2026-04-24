<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

class EntityCollection
{
    /**
     * @param array<string, array<string, mixed>> $entities  Keyed by entity ID, value is JWT payload
     */
    public function __construct(
        protected readonly array $entities,
    ) {
    }


    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->entities;
    }
}
