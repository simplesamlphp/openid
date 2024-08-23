<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum ScopesEnum: string
{
    case OpenId = 'openid';
    case OfflineAccess = 'offline_access';
    case Profile = 'profile';
    case Email = 'email';
    case Address = 'address';
    case Phone = 'phone';
}
