<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jwks;

use Jose\Component\Core\JWKSet;

class JwksDecorator
{
    public function __construct(public JWKSet $jwks)
    {
    }
}
