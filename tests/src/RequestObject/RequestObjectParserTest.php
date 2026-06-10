<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\RequestObject;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Core\Factories\RequestObjectFactory as ConnectRequestObjectFactory;
use SimpleSAML\OpenID\Core\RequestObject as ConnectRequestObject;
use SimpleSAML\OpenID\Exceptions\RequestObjectException;
use SimpleSAML\OpenID\Federation\Factories\RequestObjectFactory as FederationRequestObjectFactory;
use SimpleSAML\OpenID\Jar\Factories\RequestObjectFactory as JarRequestObjectFactory;
use SimpleSAML\OpenID\Jar\RequestObject as JarRequestObject;
use SimpleSAML\OpenID\RequestObject\RequestObjectBag;
use SimpleSAML\OpenID\RequestObject\RequestObjectFactories;
use SimpleSAML\OpenID\RequestObject\RequestObjectParser;
use SimpleSAML\OpenID\RequestObject\RequestUriFetcher;

#[CoversClass(RequestObjectParser::class)]
#[UsesClass(RequestObjectBag::class)]
final class RequestObjectParserTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject&\SimpleSAML\OpenID\RequestObject\RequestObjectFactories */
    protected MockObject $requestObjectFactoriesMock;

    /** @var \PHPUnit\Framework\MockObject\MockObject&\SimpleSAML\OpenID\RequestObject\RequestUriFetcher */
    protected MockObject $requestUriFetcherMock;

    /** @var \PHPUnit\Framework\MockObject\MockObject&\Psr\Log\LoggerInterface */
    protected MockObject $loggerMock;

    /** @var \PHPUnit\Framework\MockObject\MockObject&\SimpleSAML\OpenID\Core\Factories\RequestObjectFactory */
    protected MockObject $connectFactoryMock;

    /** @var \PHPUnit\Framework\MockObject\MockObject&\SimpleSAML\OpenID\Jar\Factories\RequestObjectFactory */
    protected MockObject $jarFactoryMock;

    /** @var \PHPUnit\Framework\MockObject\MockObject&\SimpleSAML\OpenID\Federation\Factories\RequestObjectFactory */
    protected MockObject $federationFactoryMock;


    protected function setUp(): void
    {
        $this->requestObjectFactoriesMock = $this->createMock(RequestObjectFactories::class);
        $this->requestUriFetcherMock = $this->createMock(RequestUriFetcher::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->connectFactoryMock = $this->createMock(ConnectRequestObjectFactory::class);
        $this->jarFactoryMock = $this->createMock(JarRequestObjectFactory::class);
        $this->federationFactoryMock = $this->createMock(FederationRequestObjectFactory::class);

        $this->requestObjectFactoriesMock->method('getAll')->willReturn([
            $this->connectFactoryMock,
            $this->jarFactoryMock,
            $this->federationFactoryMock,
        ]);
    }


    protected function sut(?LoggerInterface $logger = null): RequestObjectParser
    {
        return new RequestObjectParser(
            $this->requestObjectFactoriesMock,
            $this->requestUriFetcherMock,
            $logger,
        );
    }


    public function testFromTokenParsesSuccessfully(): void
    {
        $token = 'test-token';
        $connectMock = $this->createStub(ConnectRequestObject::class);
        $jarMock = $this->createStub(JarRequestObject::class);

        $this->connectFactoryMock->expects($this->once())
            ->method('fromToken')
            ->with($token)
            ->willReturn($connectMock);

        $this->jarFactoryMock->expects($this->once())
            ->method('fromToken')
            ->with($token)
            ->willReturn($jarMock);

        // Federation factory throws RequestObjectException to verify it skips and logs
        $this->federationFactoryMock->expects($this->once())
            ->method('fromToken')
            ->with($token)
            ->willThrowException(new class ('Parsing failed') extends RequestObjectException {
            });

        $this->loggerMock->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('Failed to parse request object using factory'));

        $bag = $this->sut($this->loggerMock)->fromToken($token);

        $this->assertInstanceOf(RequestObjectBag::class, $bag);
        $this->assertSame($connectMock, $bag->get($connectMock::class));
        $this->assertSame($jarMock, $bag->get($jarMock::class));
        $this->assertNull($bag->get(\SimpleSAML\OpenID\Federation\RequestObject::class));
    }


    public function testFromRequestUriFetchesAndParses(): void
    {
        $uri = 'https://example.com/request.jwt';
        $token = 'fetched-token';

        $this->requestUriFetcherMock->expects($this->once())
            ->method('fetch')
            ->with($uri, 10, 2048)
            ->willReturn($token);

        $connectMock = $this->createStub(ConnectRequestObject::class);
        $this->connectFactoryMock->expects($this->once())
            ->method('fromToken')
            ->with($token)
            ->willReturn($connectMock);

        $this->jarFactoryMock->expects($this->once())
            ->method('fromToken')
            ->willThrowException(new class ('Parsing failed') extends RequestObjectException {
            });

        $this->federationFactoryMock->expects($this->once())
            ->method('fromToken')
            ->willThrowException(new class ('Parsing failed') extends RequestObjectException {
            });

        $bag = $this->sut()->fromRequestUri($uri, 10, 2048);

        $this->assertInstanceOf(RequestObjectBag::class, $bag);
        $this->assertSame($connectMock, $bag->get($connectMock::class));
    }
}
