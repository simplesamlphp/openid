<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jwks;

use Jose\Component\Core\JWKSet;
use JsonSerializable;

class JwksDecorator implements JsonSerializable
{
    public function __construct(public readonly JWKSet $jwks)
    {
    }

    public function jsonSerialize(): array
    {
        return $this->jwks->jsonSerialize();
    }
}
