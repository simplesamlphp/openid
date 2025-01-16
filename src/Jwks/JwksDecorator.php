<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jwks;

use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use JsonSerializable;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

class JwksDecorator implements JsonSerializable
{
    public function __construct(protected readonly JWKSet $jwks)
    {
    }

    public function jwks(): JWKSet
    {
        return $this->jwks;
    }

    /**
     * @return array{keys:array<array<string,mixed>>}
     * @psalm-suppress MixedReturnTypeCoercion, MixedReturnTypeCoercion
     */
    public function jsonSerialize(): array
    {
        return [
            ClaimsEnum::Keys->value => array_map(
                fn(JWK $jwk): array => $jwk->jsonSerialize(),
                $this->jwks->all(),
            ),
        ];
    }
}
