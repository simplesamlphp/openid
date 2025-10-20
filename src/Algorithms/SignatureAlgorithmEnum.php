<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Algorithms;

use Jose\Component\Signature\Algorithm\EdDSA;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\Algorithm\ES384;
use Jose\Component\Signature\Algorithm\ES512;
use Jose\Component\Signature\Algorithm\None;
use Jose\Component\Signature\Algorithm\PS256;
use Jose\Component\Signature\Algorithm\PS384;
use Jose\Component\Signature\Algorithm\PS512;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\Algorithm\RS384;
use Jose\Component\Signature\Algorithm\RS512;
use Jose\Component\Signature\Algorithm\SignatureAlgorithm;

enum SignatureAlgorithmEnum: string
{
    case EdDSA = 'EdDSA';
    case ES256 = 'ES256';
    case ES384 = 'ES384';
    case ES512 = 'ES512';
    case none = 'none';
    case PS256 = 'PS256';
    case PS384 = 'PS384';
    case PS512 = 'PS512';
    case RS256 = 'RS256';
    case RS384 = 'RS384';
    case RS512 = 'RS512';


    public function instance(): SignatureAlgorithm
    {
        return match ($this) {
            self::EdDSA => new EdDSA(),
            self::ES256 => new ES256(),
            self::ES384 => new ES384(),
            self::ES512 => new ES512(),
            self::none => new None(),
            self::PS256 => new PS256(),
            self::PS384 => new PS384(),
            self::PS512 => new PS512(),
            self::RS256 => new RS256(),
            self::RS384 => new RS384(),
            self::RS512 => new RS512(),
        };
    }
}
