<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Factories;

use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Federation\EntityStatement;
use SimpleSAML\OpenID\Federation\EntityStatement\Factories\TrustMarkClaimBagFactory;
use SimpleSAML\OpenID\Federation\EntityStatement\Factories\TrustMarkClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Jws\JwsVerifier;
use SimpleSAML\OpenID\Serializers\JwsSerializerManager;

class EntityStatementFactory extends ParsedJwsFactory
{
    public function __construct(
        JwsParser $jwsParser,
        JwsVerifier $jwsVerifier,
        JwksFactory $jwksFactory,
        JwsSerializerManager $jwsSerializerManager,
        DateIntervalDecorator $timestampValidationLeeway,
        Helpers $helpers,
        protected readonly TrustMarkClaimFactory $trustMarkClaimFactory,
        protected readonly TrustMarkClaimBagFactory $trustMarkClaimBagFactory,
    ) {
        parent::__construct(
            $jwsParser,
            $jwsVerifier,
            $jwksFactory,
            $jwsSerializerManager,
            $timestampValidationLeeway,
            $helpers,
        );
    }
    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromToken(string $token): EntityStatement
    {
        return new EntityStatement(
            $this->jwsParser->parse($token),
            $this->jwsVerifier,
            $this->jwksFactory,
            $this->jwsSerializerManager,
            $this->timestampValidationLeeway,
            $this->helpers,
            $this->trustMarkClaimFactory,
            $this->trustMarkClaimBagFactory,
        );
    }
}
