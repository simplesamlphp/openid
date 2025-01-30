<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Claims;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

class JwksClaim
{
    /**
     * @param array{keys:non-empty-array<array<string,mixed>>} $value
     * @param non-empty-string $key
     */
    public function __construct(
        protected readonly array $value,
        protected readonly string $key = ClaimsEnum::Jwks->value,
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
    public function getKey(): string
    {
        return $this->key;
    }
}
