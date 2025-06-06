<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum JwtTypesEnum: string
{
    case EntityStatementJwt = 'entity-statement+jwt';
    case JwkSetJwt = 'jwk-set+jwt';
    case Jwt = 'JWT';
    case OpenId4VciProofJwt = 'openid4vci-proof+jwt';
    case TrustMarkJwt = 'trust-mark+jwt';
    case TrustMarkDelegationJwt = 'trust-mark-delegation+jwt';
}
