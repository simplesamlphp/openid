<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Factories;

use SimpleSAML\OpenID\Algorithms\AlgorithmManager;
use SimpleSAML\OpenID\SupportedAlgorithms;

class AlgorithmManagerFactory
{
    public function build(SupportedAlgorithms $supportedAlgorithms): AlgorithmManager
    {
        return new AlgorithmManager($supportedAlgorithms->getSignatureAlgorithmBag()->getAllInstances());
    }
}
