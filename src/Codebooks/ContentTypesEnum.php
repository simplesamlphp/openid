<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum ContentTypesEnum: string
{
    case ApplicationEntityStatementJwt = 'application/entity-statement+jwt';
}