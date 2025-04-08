<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jwk;

use Jose\Component\Core\JWK;

class JwkDecorator
{
    public function __construct(
        protected readonly JWK $jwk,
    ) {
    }

    public function jwk(): JWK
    {
        return $this->jwk;
    }
}
