<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Core\Factories;

use SimpleSAML\OpenID\Core\RequestObject;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Jws\JwsVerifier;

class RequestObjectFactory
{
    public function __construct(
        protected readonly JwsParser $jwsParser,
        protected readonly JwsVerifier $jwsVerifier,
        protected readonly JwksFactory $jwksFactory,
        protected readonly DateIntervalDecorator $timestampValidationLeeway,
    ) {
    }

    public function fromToken(string $token): RequestObject
    {
        return new RequestObject(
            $token,
            $this->jwsParser,
            $this->jwsVerifier,
            $this->jwksFactory,
        );
    }
}
