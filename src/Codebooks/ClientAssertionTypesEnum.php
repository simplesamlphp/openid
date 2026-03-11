<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum ClientAssertionTypesEnum: string
{
    // TODO mivanci rename to JwtBearer in v1
    case JwtBaerer = 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';
}
