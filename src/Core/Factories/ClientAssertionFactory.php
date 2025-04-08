<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Core\Factories;

use SimpleSAML\OpenID\Core\ClientAssertion;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;

class ClientAssertionFactory extends ParsedJwsFactory
{
    public function fromToken(string $token): ClientAssertion
    {
        return new ClientAssertion(
            $this->jwsDecoratorBuilder->fromToken($token),
            $this->jwsVerifierDecorator,
            $this->jwksDecoratorFactory,
            $this->jwsSerializerManagerDecorator,
            $this->timestampValidationLeeway,
            $this->helpers,
            $this->claimFactory,
        );
    }
}
