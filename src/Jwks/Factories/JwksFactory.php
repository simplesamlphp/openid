<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jwks\Factories;

use Jose\Component\Core\JWKSet;
use SimpleSAML\OpenID\Jwks\JwksDecorator;

class JwksFactory
{
    public function fromKeyData(array $jwks): JwksDecorator
    {
        return new JwksDecorator(JWKSet::createFromKeyData($jwks));
    }
}
