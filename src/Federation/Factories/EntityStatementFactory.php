<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Factories;

use SimpleSAML\OpenID\Federation\EntityStatement;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;

class EntityStatementFactory extends ParsedJwsFactory
{
    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromToken(string $token): EntityStatement
    {
        return new EntityStatement(
            $token,
            $this->jwsParser,
            $this->jwsVerifier,
            $this->jwksFactory,
            $this->timestampValidationLeeway,
        );
    }
}