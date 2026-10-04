<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum HttpHeadersEnum: string
{
    case Accept = 'Accept';

    case ContentType = 'Content-Type';

    case AccessControlAllowOrigin = 'Access-Control-Allow-Origin';

    // RFC 9449 section 4.1
    case DPoP = 'DPoP';

    // RFC 9449 section 8
    case DPoPNonce = 'DPoP-Nonce';
}
