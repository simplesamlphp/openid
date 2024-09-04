<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Factories;

use SimpleSAML\OpenID\Federation\RequestObject;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;

class RequestObjectFactory extends ParsedJwsFactory
{
    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromToken(string $token): RequestObject
    {
        return new RequestObject(
            $this->jwsParser->parse($token),
            $this->jwsVerifier,
            $this->jwksFactory,
            $this->jwsSerializerManager,
            $this->timestampValidationLeeway,
        );
    }
}
