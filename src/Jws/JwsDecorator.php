<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws;

use Jose\Component\Signature\JWS;

/**
 * @see \SimpleSAML\Test\OpenID\Jws\JwsDecoratorTest
 */
class JwsDecorator
{
    public function __construct(protected readonly JWS $jws)
    {
    }


    public function jws(): JWS
    {
        return $this->jws;
    }
}
