<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum ResponseModesEnum: string
{
    case Query = 'query';

    case Fragment = 'fragment';

    case FormPost = 'form_post';
}
