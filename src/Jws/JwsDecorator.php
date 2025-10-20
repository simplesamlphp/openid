<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws;

use Jose\Component\Signature\JWS;

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
