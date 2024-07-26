<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Factories;

use Jose\Component\Signature\JWS;
use SimpleSAML\OpenID\Federation\EntityStatement;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Jws\JwsVerifier;

class EntityStatementFactory
{
    public function __construct(
        protected readonly JwsParser $jwsParser,
        protected readonly JwsVerifier $jwsVerifier,
        protected readonly JwksFactory $jwksFactory,
    ) {
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromToken(string $token): EntityStatement
    {
        return new EntityStatement(
            $token,
            $this->jwsParser,
            $this->jwsVerifier,
            $this->jwksFactory,
        );
    }
}
