<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum GrantTypesEnum: string
{
    case AuthorizationCode = 'authorization_code';
    case Implicit = 'implicit';
    case RefreshToken = 'refresh_token';
}
