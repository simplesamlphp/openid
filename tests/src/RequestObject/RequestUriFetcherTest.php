<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\RequestObject;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Exceptions\DestinationPolicyException;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\RequestObject\RequestUriFetcher;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

#[CoversClass(RequestUriFetcher::class)]
final class RequestUriFetcherTest extends TestCase
{
    protected MockObject $artifactFetcherMock;

    protected MockObject $responseMock;


    protected function setUp(): void
    {
        $this->artifactFetcherMock = $this->createMock(ArtifactFetcher::class);
        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->responseMock->method('getBody')->willReturn($this->createStub(StreamInterface::class));
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
            ->with('https://example.com/request.jwt', HttpMethodsEnum::GET, ['timeout' => 5], null)
            ->willReturn($this->responseMock);

        $this->artifactFetcherMock->expects($this->once())
            ->method('readResponseBodyAsString')
            ->with($this->responseMock, null)
            ->willReturn('jwt-token-content');

        $result = $this->sut()->fetch('https://example.com/request.jwt');
        $this->assertSame('jwt-token-content', $result);
    }


    public function testPassesMaxSizeToTransferAndBodyRead(): void
    {
        // The limit has to reach the transfer itself, not only the read that follows it.
        $this->artifactFetcherMock->expects($this->once())
            ->method('fromNetwork')
            ->with('https://example.com/request.jwt', HttpMethodsEnum::GET, ['timeout' => 5], 10)
            ->willReturn($this->responseMock);

        $this->artifactFetcherMock->expects($this->once())
            ->method('readResponseBodyAsString')
            ->with($this->responseMock, 10)
            ->willThrowException(new FetchException('Response body size exceeded the limit of 10 bytes.'));

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


    /**
     * A request_uri pointing at an address the deployment does not permit is a problem with what the client
     * asked for, so it stays apart from the endpoint simply being unreachable.
     */
    public function testKeepsARefusedDestinationRecognizable(): void
    {
        $this->artifactFetcherMock->expects($this->once())
            ->method('fromNetwork')
            ->willThrowException(new DestinationPolicyException('destination refused'));

        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('destination refused');

        $this->sut()->fetch('https://example.com/request.jwt');
    }
}
