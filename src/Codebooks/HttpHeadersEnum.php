<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum HttpHeadersEnum: string
{
    case Accept = 'Accept';

    case ContentType = 'Content-Type';

    case AccessControlAllowOrigin = 'Access-Control-Allow-Origin';
}
