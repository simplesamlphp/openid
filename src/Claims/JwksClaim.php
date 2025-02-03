<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Claims;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

class JwksClaim implements ClaimInterface
{
    /**
     * @param array{keys:non-empty-array<array<string,mixed>>} $value
     * @param non-empty-string $name
     */
    public function __construct(
        protected readonly array $value,
        protected readonly string $name = ClaimsEnum::Jwks->value,
    ) {
    }

    /**
     * @return array{keys:non-empty-array<array<string,mixed>>}
     */
    public function getValue(): array
    {
        return $this->value;
    }

    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<non-empty-string,array{keys:non-empty-array<array<string,mixed>>}>
     */
    public function jsonSerialize(): array
    {
        return [
            $this->name => $this->value,
        ];
    }
}
