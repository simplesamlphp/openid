<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks\ClaimValues;

enum PublicKeyUseEnum: string
{
    case Signature = 'sig';
    case Encryption = 'enc';
}
