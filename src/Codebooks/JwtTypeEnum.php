<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum JwtTypeEnum: string
{
    case EntityStatementJwt = 'entity-statement+jwt';
}
