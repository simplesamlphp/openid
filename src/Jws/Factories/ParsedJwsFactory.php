<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws\Factories;

use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Jws\JwsVerifier;
use SimpleSAML\OpenID\Jws\ParsedJws;

class ParsedJwsFactory
{
    public function __construct(
        protected readonly JwsParser $jwsParser,
        protected readonly JwsVerifier $jwsVerifier,
        protected readonly JwksFactory $jwksFactory,
        protected readonly DateIntervalDecorator $timestampValidationLeeway,
    ) {
    }

    public function fromToken(string $token): ParsedJws
    {
        return new ParsedJws(
            $token,
            $this->jwsParser,
            $this->jwsVerifier,
            $this->jwksFactory,
            $this->timestampValidationLeeway,
        );
    }
}
