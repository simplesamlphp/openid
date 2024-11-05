<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jwks\Factories;

use SimpleSAML\OpenID\Jwks\SignedJwks;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;

class SignedJwksFactory extends ParsedJwsFactory
{
    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromToken(string $token): SignedJwks
    {
        return new SignedJwks(
            $this->jwsParser->parse($token),
            $this->jwsVerifier,
            $this->jwksFactory,
            $this->jwsSerializerManager,
            $this->timestampValidationLeeway,
            $this->helpers,
        );
    }
}
