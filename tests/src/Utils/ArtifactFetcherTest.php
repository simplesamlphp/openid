<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Utils;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

#[CoversClass(ArtifactFetcher::class)]
final class ArtifactFetcherTest extends TestCase
{
    protected MockObject $httpClientDecoratorMock;

    protected MockObject $cacheDecoratorMock;

    protected MockObject $loggerMock;

    protected MockObject $responseMock;

    protected MockObject $responseBodyMock;


    protected function setUp(): void
    {
        $this->httpClientDecoratorMock = $this->createMock(HttpClientDecorator::class);
        $this->cacheDecoratorMock = $this->createMock(CacheDecorator::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->responseBodyMock = $this->createMock(StreamInterface::class);
        $this->responseMock->method('getBody')->willReturn($this->responseBodyMock);
    }


    protected function sut(
        ?HttpClientDecorator $httpClientDecorator = null,
        ?CacheDecorator $cacheDecorator = null,
        ?LoggerInterface $logger = null,
    ): ArtifactFetcher {
        $httpClientDecorator ??= $this->httpClientDecoratorMock;
        $cacheDecorator ??= $this->cacheDecoratorMock;
        $logger ??= $this->loggerMock;

        return new ArtifactFetcher(
            $httpClientDecorator,
            $cacheDecorator,
            $logger,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(ArtifactFetcher::class, $this->sut());
    }


    public function testReturnsNullIfCacheNotAvailable(): void
    {
        $sut = new ArtifactFetcher($this->httpClientDecoratorMock, null, $this->loggerMock);

        $this->loggerMock->expects($this->once())->method('debug')
        ->with($this->stringContains('skipping'));

        $this->assertNull($sut->fromCacheAsString('key'));
    }


    public function testReturnsNullIfNotInCache(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(null, 'key')
            ->willReturn(null);

        $this->assertNull($this->sut()->fromCacheAsString('key'));
    }


    public function testReturnsArtifactIfString(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(null, 'key')
            ->willReturn('artifact');

        $this->assertSame('artifact', $this->sut()->fromCacheAsString('key'));
    }


    public function testReturnsNullIfNotString(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(null, 'key')
            ->willReturn(['artifact-in-array']);

        $this->loggerMock->expects($this->once())->method('warning')
            ->with($this->stringContains('unexpected'));

        $this->assertNull($this->sut()->fromCacheAsString('key'));
    }


    public function testReturnsNullOnCacheFailure(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(null, 'key')
            ->willThrowException(new \Exception('Error'));

        $this->loggerMock->expects($this->once())->method('error')
            ->with($this->stringContains('error'));

        $this->assertNull($this->sut()->fromCacheAsString('key'));
    }


    public function testCanFetchFromNetwork(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())->method('request')
            ->with(HttpMethodsEnum::GET, 'uri');

        $this->assertInstanceOf(ResponseInterface::class, $this->sut()->fromNetwork('uri'));
    }


    public function testFromNetworkThrowsOnNetworkError(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())->method('request')
            ->willThrowException(new \Exception('Error'));

        $this->expectException(FetchException::class);
        $this->expectExceptionMessage('HTTP');

        $this->loggerMock->expects($this->once())->method('error')
            ->with($this->stringContains('error'));

        $this->sut()->fromNetwork('uri');
    }


    public function testCanFetchFromNetworkAsString(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())->method('request')
            ->willReturn($this->responseMock);
        $this->responseBodyMock->method('getContents')->willReturn('artifact');

        $this->assertSame('artifact', $this->sut()->fromNetworkAsString('uri'));
    }


    public function testCanCacheArtifact(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('set')
            ->with('artifact', 60, 'key');

        $this->loggerMock->expects($this->once())->method('debug')
            ->with($this->stringContains('saved'));

        $this->sut()->cacheIt('artifact', 60, 'key');
    }


    public function testSkipsCachingIfCacheNotAvailable(): void
    {
        $this->loggerMock->expects($this->once())->method('debug')
            ->with($this->stringContains('skipping'));

        $sut = new ArtifactFetcher($this->httpClientDecoratorMock, null, $this->loggerMock);

        $sut->cacheIt('artifact', 60, 'key');
    }


    public function testCanLogCacheError(): void
    {
        $this->loggerMock->expects($this->once())->method('error')
            ->with($this->stringContains('error'));

        $this->cacheDecoratorMock->expects($this->once())->method('set')
            ->willThrowException(new \Exception('Error'));

        $this->sut()->cacheIt('artifact', 60, 'key');
    }
}
