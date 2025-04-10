<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Jwk\Factories\JwkDecoratorFactory;

class Jwk
{
    protected ?JwkDecoratorFactory $jwkDecoratorFactory = null;

    public function __construct(
        protected readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function jwkDecoratorFactory(): JwkDecoratorFactory
    {
        return $this->jwkDecoratorFactory ??= new JwkDecoratorFactory();
    }
}
