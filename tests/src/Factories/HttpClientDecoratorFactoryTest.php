<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Factories\HttpClientDecoratorFactory;

#[CoversClass(HttpClientDecoratorFactory::class)]
#[UsesClass(HttpClientDecorator::class)]
final class HttpClientDecoratorFactoryTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Psr\Log\LoggerInterface
     */
    protected MockObject $loggerMock;


    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
    }


    protected function sut(?LoggerInterface $logger = null): HttpClientDecoratorFactory
    {
        return new HttpClientDecoratorFactory($logger);
    }


    /**
     * Factory that reports weakened configuration to the logger mock.
     */
    protected function sutWithLogger(): HttpClientDecoratorFactory
    {
        return $this->sut($this->loggerMock);
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


    public function testRedirectDefaultsAreRestrictive(): void
    {
        $allowRedirects = $this->sut()->build()->client->getConfig('allow_redirects');

        $this->assertSame(3, $allowRedirects['max']);
        // Federation endpoints are https only, so a redirect must not be able to downgrade the connection.
        $this->assertSame(['https'], $allowRedirects['protocols']);
        $this->assertTrue($allowRedirects['strict']);
        $this->assertFalse($allowRedirects['referer']);
    }


    public function testWarnsWhenTimeoutIsDisabled(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('"timeout" is set to 0, which disables the timeout'));

        $this->sutWithLogger()->build(null, ['timeout' => 0]);
    }


    public function testWarnsWhenConnectTimeoutIsDisabled(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('"connect_timeout"'));

        $this->sutWithLogger()->build(null, ['connect_timeout' => 0]);
    }


    public function testRespectsDisabledTimeoutDespiteWarning(): void
    {
        // The caller's intent is honoured, the configuration is only reported.
        $decorator = $this->sut()->build(null, ['timeout' => 0]);

        $this->assertSame(0, $decorator->client->getConfig('timeout'));
    }


    public function testDoesNotWarnForValidTimeouts(): void
    {
        $this->loggerMock->expects($this->never())->method('warning');

        $this->sutWithLogger()->build(null, ['timeout' => 30, 'connect_timeout' => 5.0]);
    }


    public function testWarnsWhenRedirectHardeningIsUndone(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('"allow_redirects" is set to true'));

        $this->sutWithLogger()->build(null, ['allow_redirects' => true]);
    }


    public function testDoesNotWarnWhenRedirectsAreDisabledEntirely(): void
    {
        $this->loggerMock->expects($this->never())->method('warning');

        $this->sutWithLogger()->build(null, ['allow_redirects' => false]);
    }


    public function testPartialRedirectConfigKeepsHardenedDefaults(): void
    {
        // A shallow merge would drop protocols/strict/referer here, and Guzzle would then backfill them from
        // its own permissive defaults rather than from ours.
        $decorator = $this->sut()->build(null, ['allow_redirects' => ['track_redirects' => true]]);
        $allowRedirects = $decorator->client->getConfig('allow_redirects');

        $this->assertTrue($allowRedirects['track_redirects']);
        $this->assertSame(3, $allowRedirects['max']);
        $this->assertSame(['https'], $allowRedirects['protocols']);
        $this->assertTrue($allowRedirects['strict']);
    }


    public function testPartialRedirectConfigStillLetsCallerWin(): void
    {
        $decorator = $this->sut()->build(null, ['allow_redirects' => ['max' => 7]]);
        $allowRedirects = $decorator->client->getConfig('allow_redirects');

        $this->assertSame(7, $allowRedirects['max']);
        $this->assertSame(['https'], $allowRedirects['protocols']);
    }


    public function testEmptyRedirectArrayStillDisablesRedirects(): void
    {
        // Guzzle reads an empty array as "do not follow redirects", so it must not be backfilled.
        $decorator = $this->sut()->build(null, ['allow_redirects' => []]);

        $this->assertSame([], $decorator->client->getConfig('allow_redirects'));
    }


    public function testWarnsWhenRedirectProtocolsPermitHttp(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('protocols other than https'));

        $this->sutWithLogger()->build(null, ['allow_redirects' => ['protocols' => ['http', 'https']]]);
    }


    public function testWarnsWhenRedirectHopLimitIsRaised(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('permits 7 redirect hops'));

        $this->sutWithLogger()->build(null, ['allow_redirects' => ['max' => 7]]);
    }


    public function testDoesNotWarnWhenRedirectHopLimitIsLowered(): void
    {
        $this->loggerMock->expects($this->never())->method('warning');

        $this->sutWithLogger()->build(null, ['allow_redirects' => ['max' => 1]]);
    }


    public function testReportsThatPreBuiltClientSkipsDefaults(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('pre-built HTTP client'));

        $this->sutWithLogger()->build(new \GuzzleHttp\Client());
    }
}
