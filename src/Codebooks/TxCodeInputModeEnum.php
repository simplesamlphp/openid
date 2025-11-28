<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum TxCodeInputModeEnum: string
{
    case Numeric = 'numeric';

    case Text = 'text';
}
