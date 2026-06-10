<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\RequestObject;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\RequestObject\RequestUriFetcher;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

#[CoversClass(RequestUriFetcher::class)]
final class RequestUriFetcherTest extends TestCase
{
    protected MockObject $artifactFetcherMock;

    protected MockObject $responseMock;

    protected MockObject $responseBodyMock;


    protected function setUp(): void
    {
        $this->artifactFetcherMock = $this->createMock(ArtifactFetcher::class);
        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->responseBodyMock = $this->createMock(StreamInterface::class);
        $this->responseMock->method('getBody')->willReturn($this->responseBodyMock);
    }


    protected function sut(): RequestUriFetcher
    {
        return new RequestUriFetcher($this->artifactFetcherMock);
    }


    public function testThrowsIfUriNotHttps(): void
    {
        $this->expectException(FetchException::class);
        $this->expectExceptionMessage('The request_uri MUST use the https scheme.');

        $this->sut()->fetch('http://example.com/request.jwt');
    }


    public function testCanFetchFromNetworkSuccessfully(): void
    {
        $this->artifactFetcherMock->expects($this->once())
            ->method('fromNetwork')
            ->with('https://example.com/request.jwt', HttpMethodsEnum::GET, [
                'timeout' => 5,
                'stream' => true,
            ])
            ->willReturn($this->responseMock);

        $this->responseBodyMock->expects($this->exactly(2))
            ->method('eof')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->responseBodyMock->expects($this->once())
            ->method('read')
            ->with(8192)
            ->willReturn('jwt-token-content');

        $result = $this->sut()->fetch('https://example.com/request.jwt');
        $this->assertSame('jwt-token-content', $result);
    }


    public function testThrowsIfContentExceedsMaxSize(): void
    {
        $this->artifactFetcherMock->expects($this->once())
            ->method('fromNetwork')
            ->willReturn($this->responseMock);

        $this->responseBodyMock->expects($this->once())
            ->method('eof')
            ->willReturn(false);

        $this->responseBodyMock->expects($this->once())
            ->method('read')
            ->with(8192)
            ->willReturn(str_repeat('a', 11)); // larger than max size 10

        $this->expectException(FetchException::class);
        $this->expectExceptionMessage('exceeded the limit');

        $this->sut()->fetch('https://example.com/request.jwt', 5, 10);
    }


    public function testWrapsExceptionOnFetchFailure(): void
    {
        $this->artifactFetcherMock->expects($this->once())
            ->method('fromNetwork')
            ->willThrowException(new \Exception('Network error'));

        $this->expectException(FetchException::class);
        $this->expectExceptionMessage('Failed to fetch request_uri');

        $this->sut()->fetch('https://example.com/request.jwt');
    }
}
