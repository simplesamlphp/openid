<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum ApplicationTypesEnum: string
{
    case Web = 'web';
    case Native = 'native';
}
