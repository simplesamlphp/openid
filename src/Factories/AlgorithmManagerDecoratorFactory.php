<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Factories;

use Jose\Component\Core\AlgorithmManager;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\SupportedAlgorithms;

class AlgorithmManagerDecoratorFactory
{
    public function build(SupportedAlgorithms $supportedAlgorithms): AlgorithmManagerDecorator
    {
        return new AlgorithmManagerDecorator(
            new AlgorithmManager(
                $supportedAlgorithms->getSignatureAlgorithmBag()->getAllInstances(),
            ),
        );
    }
}
