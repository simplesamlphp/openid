<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Factories;

use Jose\Component\Core\JWKSet;

class JwksFactory
{
    public function fromKeyData(array $jwks): JWKSet
    {
        return JWKSet::createFromKeyData($jwks);
    }
}
