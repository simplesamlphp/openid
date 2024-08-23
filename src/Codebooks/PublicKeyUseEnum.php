<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum PublicKeyUseEnum: string
{
    case Signature = 'sig';
    case Encryption = 'enc';
}
