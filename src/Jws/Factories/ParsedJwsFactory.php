<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws\Factories;

use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

class ParsedJwsFactory
{
    public function __construct(
        protected readonly JwsParser $jwsParser,
        protected readonly JwsVerifierDecorator $jwsVerifierDecorator,
        protected readonly JwksFactory $jwksFactory,
        protected readonly JwsSerializerManagerDecorator $jwsSerializerManagerDecorator,
        protected readonly DateIntervalDecorator $timestampValidationLeeway,
        protected readonly Helpers $helpers,
    ) {
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromToken(string $token): ParsedJws
    {
        return new ParsedJws(
            $this->jwsParser->parse($token),
            $this->jwsVerifierDecorator,
            $this->jwksFactory,
            $this->jwsSerializerManagerDecorator,
            $this->timestampValidationLeeway,
            $this->helpers,
        );
    }
}
