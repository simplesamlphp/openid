<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum PkceCodeChallengeMethodEnum: string
{
    case Plain = 'plain';

    case S256 = 'S256';
}
