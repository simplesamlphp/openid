<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Decorators;

use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Exceptions\HttpException;

#[CoversClass(HttpClientDecorator::class)]
class HttpClientDecoratorTest extends TestCase
{
    protected MockObject $clientMock;
    protected MockObject $responseInterfaceMock;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(Client::class);
        $this->responseInterfaceMock = $this->createMock(ResponseInterface::class);
    }

    protected function sut(
        ?Client $client = null,
    ): HttpClientDecorator {
        $client ??= $this->clientMock;

        return new HttpClientDecorator($client);
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
}
