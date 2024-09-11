<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jwks;

use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use JsonSerializable;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

class JwksDecorator implements JsonSerializable
{
    public function __construct(public readonly JWKSet $jwks)
    {
    }

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
