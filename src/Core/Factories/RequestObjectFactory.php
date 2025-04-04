<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Core\Factories;

use SimpleSAML\OpenID\Core\RequestObject;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;

class RequestObjectFactory extends ParsedJwsFactory
{
    public function fromToken(string $token): RequestObject
    {
        return new RequestObject(
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
