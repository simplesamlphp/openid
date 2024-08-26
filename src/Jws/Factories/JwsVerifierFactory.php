<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws\Factories;

use SimpleSAML\OpenID\Algorithms\AlgorithmManager;
use SimpleSAML\OpenID\Jws\JwsVerifier;

class JwsVerifierFactory
{
    public function build(AlgorithmManager $algorithmManager): JwsVerifier
    {
        return new JwsVerifier($algorithmManager);
    }
}
