<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws;

use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use SimpleSAML\OpenID\Exceptions\JwsException;
use Throwable;

class JwsParser
{
    public function __construct(
        protected readonly JWSSerializerManager $serializerManager,
    ) {
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function parse(string $token): JWS
    {
        try {
            return $this->serializerManager->unserialize($token);
        } catch (Throwable $exception) {
            throw new JwsException('Unable to parse token.', (int)$exception->getCode(), $exception);
        }
    }
}
