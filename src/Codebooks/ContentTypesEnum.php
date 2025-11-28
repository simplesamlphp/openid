<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum ContentTypesEnum: string
{
    case ApplicationDcSdJwt = 'application/dc+sd-jwt';

    case ApplicationJwt = 'application/jwt';

    case ApplicationEntityStatementJwt = 'application/entity-statement+jwt';

    case ApplicationTrustMarkJwt = 'application/trust-mark+jwt';

    case ApplicationTrustMarkStatusResponseJwt = 'application/trust-mark-status-response+jwt';
}
