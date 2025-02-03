<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Claims;

class GenericClaim implements ClaimInterface
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        protected readonly mixed $value,
        protected readonly string $name,
    ) {
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<non-empty-string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            $this->name => $this->value,
        ];
    }
}
