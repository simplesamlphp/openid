<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum ClientAssertionTypesEnum: string
{
    case JwtBaerer = 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';
}
