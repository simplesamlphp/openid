<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum ApplicationTypeEnum: string
{
    case Web = 'web';
    case Native = 'native';
}
