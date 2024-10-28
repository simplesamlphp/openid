<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum JwtTypesEnum: string
{
    case EntityStatementJwt = 'entity-statement+jwt';
    case TrustMarkJwt = 'trust-mark+jwt';
}
