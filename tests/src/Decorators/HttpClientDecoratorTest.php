<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Decorators;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
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
use SimpleSAML\OpenID\Exceptions\DestinationPolicyException;
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


    /**
     * Guzzle describes a non-2xx response by appending a summary of the response body, and that body is
     * chosen by whoever the request was sent to. For a destination somebody else named, repeating it puts
     * remote-chosen text into this deployment's log by way of the exception message.
     *
     * The real Guzzle exception is built here rather than a stub, so that this notices if Guzzle ever
     * changes how it composes that message.
     */
    public function testRequestDoesNotRepeatTheResponseBodyOfAFailedRequest(): void
    {
        $marker = 'REMOTE-CHOSEN-BODY-TEXT';

        $requestException = RequestException::create(
            new Request('GET', 'https://example.com'),
            new Response(404, [], $marker),
        );

        // Guzzle really does put it there; if this stops holding the test below proves nothing.
        $this->assertStringContainsString($marker, $requestException->getMessage());

        $this->clientMock->method('request')->willThrowException($requestException);

        try {
            $this->sut()->request(HttpMethodsEnum::GET, 'https://example.com');
            $this->fail('Expected an HttpException.');
        } catch (HttpException $httpException) {
            $this->assertStringNotContainsString($marker, $httpException->getMessage());
            $this->assertStringContainsString('Status code: 404', $httpException->getMessage());
        }
    }


    /**
     * Guzzle adds the body summary in one place only: the http_errors middleware, which raises a
     * ClientException or a ServerException. Every OTHER response bearing exception writes its own message
     * and must keep it, or the wrong cause gets reported. TooManyRedirectsException is the clearest case -
     * it names the redirect limit, which a status line would replace with a meaningless 302.
     */
    public function testRequestKeepsTheMessageOfAResponseBearingFailureThatIsNotABadResponse(): void
    {
        $this->clientMock->method('request')->willThrowException(
            new TooManyRedirectsException(
                'Will not follow more than 3 redirects',
                new Request('GET', 'https://example.com'),
                new Response(302, [], 'body'),
            ),
        );

        try {
            $this->sut()->request(HttpMethodsEnum::GET, 'https://example.com');
            $this->fail('Expected an HttpException.');
        } catch (HttpException $httpException) {
            $this->assertStringContainsString('more than 3 redirects', $httpException->getMessage());
            $this->assertStringNotContainsString('Status code', $httpException->getMessage());
        }
    }


    /**
     * RFC 9110 lets a reason phrase carry HTAB and obs-text - any byte from 0x80 to 0xFF - so it can
     * arrive with a tab in it or as invalid UTF-8. Guzzle escapes it inside its own message; this branch
     * builds its own message and has to do the same, or a UTF-8 log formatter is handed malformed bytes.
     */
    public function testRequestEscapesARemoteSuppliedReasonPhrase(): void
    {
        $this->clientMock->method('request')->willThrowException(
            new ClientException(
                'Client error',
                new Request('GET', 'https://example.com'),
                new Response(404, [], null, '1.1', "Not\t\x80\xC3Found"),
            ),
        );

        try {
            $this->sut()->request(HttpMethodsEnum::GET, 'https://example.com');
            $this->fail('Expected an HttpException.');
        } catch (HttpException $httpException) {
            $message = $httpException->getMessage();

            $this->assertSame(
                0,
                preg_match('/[^\x20-\x7E]/', $message),
                'The message carries bytes outside printable ASCII.',
            );
            $this->assertStringContainsString('Status code: 404', $message);
        }
    }


    /**
     * The redirect middleware throws a BadResponseException directly, with the 3xx response and a message
     * naming why the redirect was refused. Since this library allows redirects over https alone, a
     * redirect to plain http produces exactly this, and it is the only thing that says so - rewriting it
     * from the status line would report a bare 302 and lose the refusal.
     */
    public function testRequestKeepsTheMessageOfARefusedRedirect(): void
    {
        $this->clientMock->method('request')->willThrowException(
            new BadResponseException(
                'Redirect URI, http://example.com, does not use one of the allowed redirect protocols: https',
                new Request('GET', 'https://example.com'),
                new Response(302, [], 'body'),
            ),
        );

        try {
            $this->sut()->request(HttpMethodsEnum::GET, 'https://example.com');
            $this->fail('Expected an HttpException.');
        } catch (HttpException $httpException) {
            $this->assertStringContainsString(
                'allowed redirect protocols',
                $httpException->getMessage(),
            );
            $this->assertStringNotContainsString('Status code', $httpException->getMessage());
        }
    }


    /**
     * A failure carrying no response - a refused connection, a timeout, a name that does not resolve -
     * keeps the message it came with. There is no body involved, and the reason is the whole diagnostic
     * value.
     */
    public function testRequestKeepsTheMessageOfAFailureWithoutAResponse(): void
    {
        $this->clientMock->method('request')->willThrowException(
            RequestException::create(new Request('GET', 'https://example.com')),
        );

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Error sending HTTP request');

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


    public function testRequestHardensPartialRedirectOptions(): void
    {
        $this->responseInterfaceMock->method('getStatusCode')->willReturn(200);

        $this->clientMock->expects($this->once())->method('request')
            ->with(
                'GET',
                'https://example.com',
                $this->callback(function (array $options): bool {
                    $allowRedirects = $options[RequestOptions::ALLOW_REDIRECTS];

                    $this->assertTrue($allowRedirects['track_redirects']);
                    // Per request options replace the client config, so the hardening has to be re-applied.
                    $this->assertSame(3, $allowRedirects['max']);
                    $this->assertSame(['https'], $allowRedirects['protocols']);
                    return true;
                }),
            )
            ->willReturn($this->responseInterfaceMock);

        $this->sut()->request(
            HttpMethodsEnum::GET,
            'https://example.com',
            [RequestOptions::ALLOW_REDIRECTS => ['track_redirects' => true]],
        );
    }


    public function testRequestKeepsEmptyRedirectArray(): void
    {
        $this->responseInterfaceMock->method('getStatusCode')->willReturn(200);

        $this->clientMock->expects($this->once())->method('request')
            ->with(
                'GET',
                'https://example.com',
                $this->callback(function (array $options): bool {
                    // An empty array disables redirects in Guzzle, which is stricter than the default.
                    $this->assertSame([], $options[RequestOptions::ALLOW_REDIRECTS]);
                    return true;
                }),
            )
            ->willReturn($this->responseInterfaceMock);

        $this->sut()->request(
            HttpMethodsEnum::GET,
            'https://example.com',
            [RequestOptions::ALLOW_REDIRECTS => []],
        );
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


    public function testTimeoutCeilingUsesKnownClientTimeoutWhenSmaller(): void
    {
        $sut = new HttpClientDecorator($this->clientMock, 102400, 10.0);

        $this->assertEqualsWithDelta(
            10.0,
            $sut->timeoutCeilingOptions(microtime(true) + 28.0)[RequestOptions::TIMEOUT],
            PHP_FLOAT_EPSILON,
        );
    }


    public function testTimeoutCeilingUsesCeilingWhenSmaller(): void
    {
        $sut = new HttpClientDecorator($this->clientMock, 102400, 10.0);

        $this->assertEqualsWithDelta(
            3.5,
            $sut->timeoutCeilingOptions(microtime(true) + 3.5)[RequestOptions::TIMEOUT],
            0.5,
        );
    }


    public function testTimeoutCeilingAppliesWhenClientTimeoutIsUnknown(): void
    {
        // A pre-built client may carry no timeout at all, so the ceiling is the only bound available.
        $sut = new HttpClientDecorator($this->clientMock, 102400);

        $this->assertNull($sut->getRequestTimeout());
        $this->assertEqualsWithDelta(
            28.0,
            $sut->timeoutCeilingOptions(microtime(true) + 28.0)[RequestOptions::TIMEOUT],
            0.5,
        );
    }


    public function testTimeoutCeilingCutsAnOverrunningRedirectChain(): void
    {
        $sut = new HttpClientDecorator($this->clientMock, 102400);

        // "timeout" only bounds a single hop, so the chain is cut here once the ceiling has really passed.
        $deadlineCallback = $sut->timeoutCeilingOptions(microtime(true) + 0.01)[RequestOptions::PROGRESS];

        usleep(20000);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('exceeded the maximum allowed duration');

        $deadlineCallback($this->responseInterfaceMock);
    }


    public function testTimeoutCeilingBoundsTheFetchNotEachRedirectHop(): void
    {
        // Client timeout well below what is left of the caller's budget, so the client timeout is the
        // allowance. A redirect chain must not be able to spend that allowance once per hop.
        $sut = new HttpClientDecorator($this->clientMock, 102400, 0.05);
        $deadlineCallback = $sut->timeoutCeilingOptions(microtime(true) + 3600.0)[RequestOptions::PROGRESS];

        usleep(100000);

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('exceeded the maximum allowed duration');

        $deadlineCallback($this->responseInterfaceMock);
    }


    public function testTimeoutCeilingLetsATimelyRedirectChainThrough(): void
    {
        $sut = new HttpClientDecorator($this->clientMock, 102400);

        $deadlineCallback = $sut->timeoutCeilingOptions(microtime(true) + 30.0)[RequestOptions::PROGRESS];
        $deadlineCallback($this->responseInterfaceMock);

        $this->expectNotToPerformAssertions();
    }


    public function testTimeoutCeilingNeverBecomesUnbounded(): void
    {
        $sut = new HttpClientDecorator($this->clientMock, 102400);

        // Guzzle reads 0 as "no timeout", so a spent budget must not turn into an unbounded request.
        $timeout = $sut->timeoutCeilingOptions(microtime(true))[RequestOptions::TIMEOUT];
        $this->assertGreaterThan(0, $timeout);

        $timeout = $sut->timeoutCeilingOptions(microtime(true) - 5.0)[RequestOptions::TIMEOUT];
        $this->assertGreaterThan(0, $timeout);
    }


    public function testDisabledClientTimeoutIsTreatedAsUnknown(): void
    {
        $sut = new HttpClientDecorator($this->clientMock, 102400, 0.0);

        $this->assertNull($sut->getRequestTimeout());
        $this->assertEqualsWithDelta(
            28.0,
            $sut->timeoutCeilingOptions(microtime(true) + 28.0)[RequestOptions::TIMEOUT],
            0.5,
        );
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


    /**
     * A refused destination has to stay recognizable rather than be folded into the generic HTTP failure
     * everything else caught here becomes.
     */
    public function testKeepsARefusedDestinationRecognizable(): void
    {
        $this->clientMock->method('request')
            ->willThrowException(new DestinationPolicyException('destination refused'));

        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('destination refused');

        $this->sut()->request(HttpMethodsEnum::GET, 'https://example.com');
    }


    public function testFindsARefusedDestinationInsideAWrappedFailure(): void
    {
        $this->clientMock->method('request')->willThrowException(
            new \RuntimeException(
                'Error completing request',
                0,
                new DestinationPolicyException('destination refused'),
            ),
        );

        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('destination refused');

        $this->sut()->request(HttpMethodsEnum::GET, 'https://example.com');
    }
}
