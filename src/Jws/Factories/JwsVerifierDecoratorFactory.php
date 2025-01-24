<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws\Factories;

use Jose\Component\Signature\JWSVerifier;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;

class JwsVerifierDecoratorFactory
{
    public function build(AlgorithmManagerDecorator $algorithmManagerDecorator): JwsVerifierDecorator
    {
        return new JwsVerifierDecorator(
            new JWSVerifier(
                $algorithmManagerDecorator->algorithmManager(),
            ),
        );
    }
}
