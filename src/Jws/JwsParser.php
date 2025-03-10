<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws;

use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use Throwable;

class JwsParser
{
    public function __construct(
        protected readonly JwsSerializerManagerDecorator $serializerManagerDecorator,
    ) {
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function parse(string $token): JwsDecorator
    {
        try {
            return $this->serializerManagerDecorator->unserialize($token);
        } catch (Throwable $throwable) {
            throw new JwsException('Unable to parse token.', (int)$throwable->getCode(), $throwable);
        }
    }
}
