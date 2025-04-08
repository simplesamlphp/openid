<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws;

use Jose\Component\Signature\JWSBuilder;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use Throwable;

class JwsDecoratorBuilder
{
    public function __construct(
        protected readonly JwsSerializerManagerDecorator $serializerManagerDecorator,
        protected readonly JWSBuilder $jwsBuilder,
        protected readonly Helpers $helpers,
    ) {
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromToken(string $token): JwsDecorator
    {
        try {
            return $this->serializerManagerDecorator->unserialize($token);
        } catch (Throwable $throwable) {
            throw new JwsException('Unable to parse token.', (int)$throwable->getCode(), $throwable);
        }
    }
}
