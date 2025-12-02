<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum UriPattern: string
{
    case HttpNoQueryNoFragment = '/^http(s?):\/\/[^\s\/$.?#][^\s?#]*$/i';

    case Uri = '/^[a-zA-Z][a-zA-Z0-9+.-]*:\S*$/i';
}
