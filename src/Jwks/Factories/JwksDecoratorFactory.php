<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jwks\Factories;

use Jose\Component\Core\JWKSet;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\Jwks\JwksDecorator;

class JwksDecoratorFactory
{
    /**
     * @param mixed[] $jwks JWKS array (validated later)
     */
    public function fromKeySetData(array $jwks): JwksDecorator
    {
        return new JwksDecorator(JWKSet::createFromKeyData($jwks));
    }


    public function fromJwkDecorators(JwkDecorator ...$jwkDecorators): JwksDecorator
    {
        $jwks = [
            'keys' => array_map(
                fn (JwkDecorator $jwkDecorator): array => $jwkDecorator->jsonSerialize(),
                $jwkDecorators,
            ),
        ];

        return $this->fromKeySetData($jwks);
    }
}
