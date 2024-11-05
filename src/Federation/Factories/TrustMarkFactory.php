<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Factories;

use SimpleSAML\OpenID\Federation\TrustMark;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;

class TrustMarkFactory extends ParsedJwsFactory
{
    public function fromToken(string $token): TrustMark
    {
        return new TrustMark(
            $this->jwsParser->parse($token),
            $this->jwsVerifier,
            $this->jwksFactory,
            $this->jwsSerializerManager,
            $this->timestampValidationLeeway,
            $this->helpers,
        );
    }
}
