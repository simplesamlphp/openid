<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jwks;

use Jose\Component\Core\JWKSet;
use JsonSerializable;

readonly class JwksDecorator implements JsonSerializable
{
    public function __construct(public JWKSet $jwks)
    {
    }

    public function jsonSerialize(): array
    {
        return $this->jwks->jsonSerialize();
    }
}
