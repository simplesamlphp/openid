<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws;

use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Serializers\JwsSerializerManager;
use Throwable;

class JwsParser
{
    public function __construct(
        protected readonly JwsSerializerManager $serializerManager,
    ) {
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function parse(string $token): JwsDecorator
    {
        try {
            return new JwsDecorator($this->serializerManager->unserialize($token));
        } catch (Throwable $exception) {
            throw new JwsException('Unable to parse token.', (int)$exception->getCode(), $exception);
        }
    }
}
