<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws;

use Jose\Component\Signature\JWS;

readonly class JwsDecorator
{
    public function __construct(public JWS $jws)
    {
    }
}
