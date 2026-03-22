<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum ContentTypesEnum: string
{
    case ApplicationDcSdJwt = 'application/dc+sd-jwt';

    case ApplicationEntityStatementJwt = 'application/entity-statement+jwt';

    case ApplicationJwt = 'application/jwt';

    case ApplicationTrustMarkJwt = 'application/trust-mark+jwt';

    case ApplicationTrustMarkStatusResponseJwt = 'application/trust-mark-status-response+jwt';

    case ApplicationVc = 'application/vc';

    case ApplicationVcCose = 'application/vc+cose';

    case ApplicationVcJwt = 'application/vc+jwt';

    case ApplicationVcSdJwt = 'application/vc+sd-jwt';

    case ApplicationVp = 'application/vp';

    case ApplicationVpCose = 'application/vp+cose';

    case ApplicationVpJwt = 'application/vp+jwt';

    case ApplicationVpSdJwt = 'application/vp+sd-jwt';
}
