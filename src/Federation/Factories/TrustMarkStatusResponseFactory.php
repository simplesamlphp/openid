<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Factories;

use SimpleSAML\OpenID\Federation\TrustMarkStatusResponse;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;

class TrustMarkStatusResponseFactory extends ParsedJwsFactory
{
    public function fromToken(string $token): TrustMarkStatusResponse
    {
        return new TrustMarkStatusResponse(
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
