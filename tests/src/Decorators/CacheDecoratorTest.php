<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Decorators;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\OpenID\Decorators\CacheDecorator;

#[CoversClass(CacheDecorator::class)]
final class CacheDecoratorTest extends TestCase
{
    protected MockObject $cacheMock;


    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(CacheInterface::class);
    }


    protected function sut(
        ?CacheInterface $cache = null,
    ): CacheDecorator {
        $cache ??= $this->cacheMock;

        return new CacheDecorator($cache);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(CacheDecorator::class, $this->sut());
    }


    public function testGet(): void
    {
        $this->cacheMock->expects($this->once())->method('get')->willReturn('value');
        $this->assertEquals('value', $this->sut()->get(null, 'key'));
    }


    public function testSet(): void
    {
        $this->cacheMock->expects($this->once())->method('set');
        $this->sut()->set('value', 10, 'key');
    }


    public function testHas(): void
    {
        $this->cacheMock->expects($this->once())->method('has')->willReturn(true);
        $this->assertTrue($this->sut()->has('key'));
    }


    public function testDelete(): void
    {
        $this->cacheMock->expects($this->once())->method('delete');
        $this->sut()->delete('key');
    }
}
