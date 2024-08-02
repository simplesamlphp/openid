<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks\ClaimValues;

enum TypeEnum: string
{
    case EntityStatementJwt = 'entity-statement+jwt';
}
