<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum ResponseTypesEnum: string
{
    case Code = 'code';

    case IdToken = 'id_token';

    case IdTokenToken = 'id_token token';
}
