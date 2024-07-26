<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Algorithms;

use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\Algorithm\SignatureAlgorithm;

enum SignatureAlgorithmEnum: string
{
    // TODO mivanci add other algos
    case RS256 = 'RS256';

    public function instance(): SignatureAlgorithm
    {
        return match($this) {
            self::RS256 => new RS256(),
        };
    }
}
