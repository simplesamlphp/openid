<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Factories;

use SimpleSAML\OpenID\Federation\TrustChain;
use SimpleSAML\OpenID\Federation\TrustChainBag;

class TrustChainBagFactory
{
    public function build(TrustChain $trustChain, TrustChain ...$trustChains): TrustChainBag
    {
        return new TrustChainBag($trustChain, ...$trustChains);
    }
}
