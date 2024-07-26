<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Factories;

use SimpleSAML\OpenID\Federation\TrustChain;

class TrustChainFactory
{
    public function build(): TrustChain
    {
        return new TrustChain();
    }
}