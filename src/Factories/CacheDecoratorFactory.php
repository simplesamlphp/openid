<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Factories;

use Psr\SimpleCache\CacheInterface;
use SimpleSAML\OpenID\Decorators\CacheDecorator;

class CacheDecoratorFactory
{
    public function build(CacheInterface $cache): CacheDecorator
    {
        return new CacheDecorator($cache);
    }
}
