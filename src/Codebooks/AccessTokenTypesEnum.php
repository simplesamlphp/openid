<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum AccessTokenTypesEnum: string
{
    // https://www.iana.org/assignments/oauth-parameters/oauth-parameters.xhtml#token-types
    case Bearer = 'Bearer';
    case N_A = 'N_A';
    case PoP = 'PoP';
    case DPoP = 'DPoP';
}
