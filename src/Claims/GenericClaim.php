<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Claims;

class GenericClaim
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        protected readonly string $name,
        protected readonly mixed $value,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
