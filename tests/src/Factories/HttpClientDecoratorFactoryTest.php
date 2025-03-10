<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Factories;

use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Factories\HttpClientDecoratorFactory;

#[CoversClass(HttpClientDecoratorFactory::class)]
#[UsesClass(HttpClientDecorator::class)]
final class HttpClientDecoratorFactoryTest extends TestCase
{
    protected MockObject $clientMock;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(Client::class);
    }

    protected function sut(): HttpClientDecoratorFactory
    {
        return new HttpClientDecoratorFactory();
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(HttpClientDecoratorFactory::class, $this->sut());
    }

    public function testCanBuild(): void
    {
        $this->assertInstanceOf(
            HttpClientDecorator::class,
            $this->sut()->build($this->clientMock),
        );
    }
}
