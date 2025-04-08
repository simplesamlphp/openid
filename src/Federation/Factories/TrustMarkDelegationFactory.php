<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Factories;

use SimpleSAML\OpenID\Federation\TrustMarkDelegation;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;

class TrustMarkDelegationFactory extends ParsedJwsFactory
{
    public function fromToken(string $token): TrustMarkDelegation
    {
        return new TrustMarkDelegation(
            $this->jwsDecoratorBuilder->fromToken($token),
            $this->jwsVerifierDecorator,
            $this->jwksFactory,
            $this->jwsSerializerManagerDecorator,
            $this->timestampValidationLeeway,
            $this->helpers,
            $this->claimFactory,
        );
    }
}
