<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Decorators;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Exceptions\HttpException;
use SimpleSAML\OpenID\Utils\SizeLimitedStream;

#[CoversClass(HttpClientDecorator::class)]
#[UsesClass(SizeLimitedStream::class)]
final class HttpClientDecoratorTest extends TestCase
{
    protected MockObject $clientMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Psr\Http\Message\ResponseInterface
     */
    protected MockObject $responseInterfaceMock;

    protected MockObject $responseBodyMock;

    protected MockObject $responseMock;


    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(Client::class);
        $this->responseInterfaceMock = $this->createMock(ResponseInterface::class);
    }


    protected function sut(
        ?Client $client = null,
        ?int $maxFetchSizeBytes = null,
    ): HttpClientDecorator {
        $client ??= $this->clientMock;
        $maxFetchSizeBytes ??= HttpClientDecorator::DEFAULT_MAX_FETCH_SIZE_BYTES;

        return new HttpClientDecorator($client, $maxFetchSizeBytes);
    }


    /**
     * Stub the response body as a stream handing out the given chunks, one read() at a time.
     */
    protected function stubResponseBodyChunks(string ...$chunks): void
    {
        $this->responseBodyMock = $this->createMock(StreamInterface::class);
        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->responseMock->method('getBody')->willReturn($this->responseBodyMock);

        $eofReturns = array_fill(0, count($chunks), false);
        $eofReturns[] = true;

        $this->responseBodyMock->method('eof')->willReturnOnConsecutiveCalls(...$eofReturns);
        $this->responseBodyMock->method('read')->willReturnOnConsecutiveCalls(...$chunks);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(HttpClientDecorator::class, $this->sut());
    }


    public function testRequestThrowsForRequestError(): void
    {
        $this->clientMock->method('request')->willThrowException(new \Exception('error'));
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('HTTP request');

        $this->sut()->request(HttpMethodsEnum::GET, 'https://example.com');
    }


    public function testRequestThrowsForNon200Response(): void
    {
        $this->responseInterfaceMock->method('getStatusCode')->willReturn(500);
        $this->clientMock->method('request')->willReturn($this->responseInterfaceMock);
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Status code');

        $this->sut()->request(HttpMethodsEnum::GET, 'https://example.com');
    }


    public function testCanRequest(): void
    {
        $this->responseInterfaceMock->method('getStatusCode')->willReturn(200);
        $this->clientMock->method('request')->willReturn($this->responseInterfaceMock);

        $this->assertInstanceOf(
            ResponseInterface::class,
            $this->sut()->request(HttpMethodsEnum::GET, 'https://example.com'),
        );
    }


    public function testRequestInstallsSizeLimitedSink(): void
    {
        $this->responseInterfaceMock->method('getStatusCode')->willReturn(200);

        $this->clientMock->expects($this->once())->method('request')
            ->with(
                'GET',
                'https://example.com',
                $this->callback(function (array $options): bool {
                    $this->assertInstanceOf(SizeLimitedStream::class, $options[RequestOptions::SINK]);
                    $this->assertSame(10, $options[RequestOptions::SINK]->getMaxSizeBytes());
                    // Streaming mode would bypass the sink, so it has to be pinned off.
                    $this->assertFalse($options[RequestOptions::STREAM]);
                    return true;
                }),
            )
            ->willReturn($this->responseInterfaceMock);

        $this->sut(maxFetchSizeBytes: 10)->request(HttpMethodsEnum::GET, 'https://example.com');
    }


    public function testRequestKeepsCallerSuppliedSink(): void
    {
        $this->responseInterfaceMock->method('getStatusCode')->willReturn(200);
        $callerSink = $this->createStub(StreamInterface::class);

        $this->clientMock->expects($this->once())->method('request')
            ->with('GET', 'https://example.com', [RequestOptions::SINK => $callerSink])
            ->willReturn($this->responseInterfaceMock);

        $this->sut()->request(
            HttpMethodsEnum::GET,
            'https://example.com',
            [RequestOptions::SINK => $callerSink],
        );
    }


    public function testMaxSizeArgumentOverridesConfiguredMaxSizeForRequest(): void
    {
        $this->responseInterfaceMock->method('getStatusCode')->willReturn(200);

        $this->clientMock->expects($this->once())->method('request')
            ->with(
                'GET',
                'https://example.com',
                $this->callback(function (array $options): bool {
                    $this->assertSame(64, $options[RequestOptions::SINK]->getMaxSizeBytes());
                    return true;
                }),
            )
            ->willReturn($this->responseInterfaceMock);

        $this->sut(maxFetchSizeBytes: 102400)->request(
            HttpMethodsEnum::GET,
            'https://example.com',
            [],
            64,
        );
    }


    public function testRequestReportsExceededSizeAsCause(): void
    {
        // Fill the sink past its limit the way a handler would, then fail the transfer as a handler does.
        $this->clientMock->method('request')
            ->willReturnCallback(function (string $method, string $uri, array $options): never {
                try {
                    $options[RequestOptions::SINK]->write(str_repeat('a', 11));
                } catch (\OverflowException) {
                    // The handlers swallow the sink failure and report a generic transfer error.
                }

                throw new \RuntimeException('Unable to write to stream');
            });

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('exceeded the limit of 10 bytes');

        $this->sut(maxFetchSizeBytes: 10)->request(HttpMethodsEnum::GET, 'https://example.com');
    }


    public function testCanReadResponseBodyAsString(): void
    {
        $this->stubResponseBodyChunks('first-', 'second');

        $this->assertSame('first-second', $this->sut()->readResponseBodyAsString($this->responseMock));
    }


    public function testStopsReadingResponseBodyOnEmptyChunk(): void
    {
        // A spent stream that never flips eof() must not spin forever.
        $this->responseBodyMock = $this->createMock(StreamInterface::class);
        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->responseMock->method('getBody')->willReturn($this->responseBodyMock);

        $this->responseBodyMock->method('eof')->willReturn(false);
        $this->responseBodyMock->method('read')->willReturnOnConsecutiveCalls('partial', '');

        $this->assertSame('partial', $this->sut()->readResponseBodyAsString($this->responseMock));
    }


    public function testThrowsWhenResponseBodyExceedsConfiguredMaxSize(): void
    {
        $this->stubResponseBodyChunks(str_repeat('a', 11));

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('exceeded the limit of 10 bytes');

        $this->sut(maxFetchSizeBytes: 10)->readResponseBodyAsString($this->responseMock);
    }


    public function testThrowsWhenResponseBodyExceedsMaxSizeAcrossChunks(): void
    {
        // The cap has to be enforced during the read, not only on the first chunk.
        $this->stubResponseBodyChunks('aaaaa', 'bbbbb', 'ccccc');

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('exceeded the limit of 10 bytes');

        $this->sut(maxFetchSizeBytes: 10)->readResponseBodyAsString($this->responseMock);
    }


    public function testMaxSizeArgumentOverridesConfiguredMaxSize(): void
    {
        $this->stubResponseBodyChunks(str_repeat('a', 11));

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('exceeded the limit of 5 bytes');

        $this->sut(maxFetchSizeBytes: 102400)->readResponseBodyAsString($this->responseMock, 5);
    }


    public function testRequestMarksEachResponseViaOnHeaders(): void
    {
        $this->responseInterfaceMock->method('getStatusCode')->willReturn(200);
        $callerOnHeadersCalled = false;

        $this->clientMock->method('request')
            ->willReturnCallback(function (string $method, string $uri, array $options): ResponseInterface {
                // Mimic a redirect hop: announce a response, fill the sink, announce the next one.
                $options[RequestOptions::ON_HEADERS]($this->responseInterfaceMock);
                $options[RequestOptions::SINK]->write(str_repeat('a', 8));
                $options[RequestOptions::ON_HEADERS]($this->responseInterfaceMock);
                $options[RequestOptions::SINK]->write(str_repeat('b', 8));

                return $this->responseInterfaceMock;
            });

        // Two individually valid hops must not add up into a false limit breach.
        $this->sut(maxFetchSizeBytes: 10)->request(
            HttpMethodsEnum::GET,
            'https://example.com',
            [RequestOptions::ON_HEADERS => function () use (&$callerOnHeadersCalled): void {
                $callerOnHeadersCalled = true;
            }],
        );

        $this->assertTrue($callerOnHeadersCalled, 'A caller supplied on_headers callback must still run.');
    }


    public function testForwardsAllOnHeadersArgumentsToCallerCallback(): void
    {
        $this->responseInterfaceMock->method('getStatusCode')->willReturn(200);
        $request = $this->createStub(RequestInterface::class);
        $receivedArguments = [];

        $this->clientMock->method('request')
            ->willReturnCallback(function (
                string $method,
                string $uri,
                array $options,
            ) use (
                $request,
            ): ResponseInterface {
                // Guzzle's stream handler calls on_headers with both the response and the request.
                $options[RequestOptions::ON_HEADERS]($this->responseInterfaceMock, $request);

                return $this->responseInterfaceMock;
            });

        $this->sut()->request(
            HttpMethodsEnum::GET,
            'https://example.com',
            [RequestOptions::ON_HEADERS => function (
                ResponseInterface $response,
                RequestInterface $request,
            ) use (&$receivedArguments): void {
                $receivedArguments = [$response, $request];
            }],
        );

        $this->assertCount(2, $receivedArguments);
        $this->assertSame($request, $receivedArguments[1]);
    }


    public function testRewindsAlreadyConsumedResponseBody(): void
    {
        // Guzzle's MockHandler returns a response whose body cursor sits at the end once a sink is set.
        $body = Utils::streamFor('jwks-json');
        $body->getContents();

        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->responseMock->method('getBody')->willReturn($body);

        $this->assertSame('jwks-json', $this->sut()->readResponseBodyAsString($this->responseMock));
    }


    public function testWrapsResponseBodyReadFailure(): void
    {
        $this->responseBodyMock = $this->createMock(StreamInterface::class);
        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->responseMock->method('getBody')->willReturn($this->responseBodyMock);

        $this->responseBodyMock->method('eof')->willReturn(false);
        $this->responseBodyMock->method('read')
            ->willThrowException(new \RuntimeException('Connection reset by peer'));

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Connection reset by peer');

        $this->sut()->readResponseBodyAsString($this->responseMock);
    }


    public function testMaxFetchSizeBytesIsClamped(): void
    {
        $this->assertSame(1, $this->sut(maxFetchSizeBytes: 0)->getMaxFetchSizeBytes());
        $this->assertSame(1, $this->sut(maxFetchSizeBytes: -100)->getMaxFetchSizeBytes());
        $this->assertSame(
            HttpClientDecorator::DEFAULT_MAX_FETCH_SIZE_BYTES,
            $this->sut()->getMaxFetchSizeBytes(),
        );
    }
}
