<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Factories;

use Psr\SimpleCache\CacheInterface;
use SimpleSAML\OpenID\Decorators\CacheDecorator;

/**
 * @see \SimpleSAML\Test\OpenID\Factories\CacheDecoratorFactoryTest
 */
class CacheDecoratorFactory
{
    public function build(CacheInterface $cache): CacheDecorator
    {
        return new CacheDecorator($cache);
    }
}
