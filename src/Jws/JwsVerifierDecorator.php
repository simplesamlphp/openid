<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws;

use Jose\Component\Signature\JWSVerifier;
use SimpleSAML\OpenID\Jwks\JwksDecorator;

/**
 * @see \SimpleSAML\Test\OpenID\Jws\JwsVerifierDecoratorTest
 */
class JwsVerifierDecorator
{
    public function __construct(
        protected readonly JWSVerifier $jwsVerifier,
    ) {
    }


    public function jwsVerifier(): JWSVerifier
    {
        return $this->jwsVerifier;
    }


    public function verifyWithKeySet(
        JwsDecorator $jwsDecorator,
        JwksDecorator $jwksDecorator,
        int $signatureIndex,
        ?string $detachedPayload = null,
    ): bool {
        return $this->jwsVerifier->verifyWithKeySet(
            $jwsDecorator->jws(),
            $jwksDecorator->jwks(),
            $signatureIndex,
            $detachedPayload,
        );
    }
}
