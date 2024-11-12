<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Factories\CacheDecoratorFactory;

#[CoversClass(CacheDecoratorFactory::class)]
#[UsesClass(CacheDecorator::class)]
class CacheDecoratorFactoryTest extends TestCase
{
    protected MockObject $cacheInterfaceMock;

    protected function setUp(): void
    {
        $this->cacheInterfaceMock = $this->createMock(CacheInterface::class);
    }

    protected function sut(): CacheDecoratorFactory
    {
        return new CacheDecoratorFactory();
    }

    public function testCacnCreateInstance(): void
    {
        $this->assertInstanceOf(
            CacheDecoratorFactory::class,
            $this->sut(),
        );
    }

    public function testCanBuild(): void
    {
        $this->assertInstanceOf(
            CacheDecorator::class,
            $this->sut()->build($this->cacheInterfaceMock),
        );
    }
}
