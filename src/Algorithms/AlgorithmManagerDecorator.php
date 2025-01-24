<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Algorithms;

use Jose\Component\Core\AlgorithmManager;

class AlgorithmManagerDecorator
{
    public function __construct(
        protected readonly AlgorithmManager $algorithmManager,
    ) {
    }

    public function algorithmManager(): AlgorithmManager
    {
        return $this->algorithmManager;
    }
}
