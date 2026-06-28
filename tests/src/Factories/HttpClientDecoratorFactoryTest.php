<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Factories\HttpClientDecoratorFactory;

#[CoversClass(HttpClientDecoratorFactory::class)]
#[UsesClass(HttpClientDecorator::class)]
final class HttpClientDecoratorFactoryTest extends TestCase
{
    protected function setUp(): void
    {
    }


    protected function sut(): HttpClientDecoratorFactory
    {
        return new HttpClientDecoratorFactory();
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(HttpClientDecoratorFactory::class, $this->sut());
    }


    public function testBuildWithClient(): void
    {
        $client = new \GuzzleHttp\Client();
        $decorator = $this->sut()->build($client);
        $this->assertSame($client, $decorator->client);
    }


    public function testBuildWithoutClientAndConfig(): void
    {
        $decorator = $this->sut()->build();
        $this->assertInstanceOf(HttpClientDecorator::class, $decorator);
        $this->assertNotEmpty($decorator->client->getConfig('allow_redirects'));
    }


    public function testBuildWithConfig(): void
    {
        $config = ['timeout' => 10.0, 'connect_timeout' => 5.0];
        $decorator = $this->sut()->build(null, $config);

        $this->assertEqualsWithDelta(10.0, $decorator->client->getConfig('timeout'), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(5.0, $decorator->client->getConfig('connect_timeout'), PHP_FLOAT_EPSILON);
        $this->assertNotEmpty($decorator->client->getConfig('allow_redirects'));
    }


    public function testBuildWithConfigOverridingDefaults(): void
    {
        $decorator = $this->sut()->build(null, ['allow_redirects' => false]);
        $this->assertFalse($decorator->client->getConfig('allow_redirects'));
    }
}
