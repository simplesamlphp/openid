<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityStatement\Factories;

use SimpleSAML\OpenID\Federation\EntityStatement\TrustMarkClaim;
use SimpleSAML\OpenID\Federation\EntityStatement\TrustMarkClaimBag;

class TrustMarkClaimBagFactory
{
    public function build(TrustMarkClaim ...$trustMarkClaims): TrustMarkClaimBag
    {
        return new TrustMarkClaimBag(...$trustMarkClaims);
    }
}
