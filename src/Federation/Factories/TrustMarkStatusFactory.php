<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Factories;

use SimpleSAML\OpenID\Federation\TrustMarkStatus;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;

class TrustMarkStatusFactory extends ParsedJwsFactory
{
    public function fromToken(string $token): TrustMarkStatus
    {
        return new TrustMarkStatus(
            $this->jwsParser->parse($token),
            $this->jwsVerifierDecorator,
            $this->jwksFactory,
            $this->jwsSerializerManagerDecorator,
            $this->timestampValidationLeeway,
            $this->helpers,
            $this->claimFactory,
        );
    }
}
