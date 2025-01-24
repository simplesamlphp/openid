<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jws;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jws\AbstractJwsFetcher;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsFetcher;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

#[CoversClass(JwsFetcher::class)]
#[CoversClass(AbstractJwsFetcher::class)]
class JwsFetcherTest extends TestCase
{
    protected MockObject $parsedJwsFactoryMock;
    protected MockObject $artifactFetcherMock;
    protected MockObject $maxCacheDurationMock;
    protected MockObject $helpersMock;
    protected MockObject $loggerMock;
    protected MockObject $responseMock;
    protected MockObject $responseBodyMock;
    protected MockObject $parsedJwsMock;

    protected function setUp(): void
    {
        $this->parsedJwsFactoryMock = $this->createMock(ParsedJwsFactory::class);
        $this->artifactFetcherMock = $this->createMock(ArtifactFetcher::class);
        $this->maxCacheDurationMock = $this->createMock(DateIntervalDecorator::class);
        $this->helpersMock = $this->createMock(Helpers::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->responseBodyMock = $this->createMock(StreamInterface::class);
        $this->responseMock->method('getBody')->willReturn($this->responseBodyMock);
        $this->artifactFetcherMock->method('fromNetwork')->willReturn($this->responseMock);

        $this->parsedJwsMock = $this->createMock(ParsedJws::class);
    }

    protected function sut(
        ?ParsedJwsFactory $parsedJwsFactory = null,
        ?ArtifactFetcher $artifactFetcher = null,
        ?DateIntervalDecorator $maxCacheDuration = null,
        ?Helpers $helpers = null,
        ?LoggerInterface $logger = null,
    ): JwsFetcher {
        $parsedJwsFactory ??= $this->parsedJwsFactoryMock;
        $artifactFetcher ??= $this->artifactFetcherMock;
        $maxCacheDuration ??= $this->maxCacheDurationMock;
        $helpers ??= $this->helpersMock;
        $logger ??= $this->loggerMock;

        return new JwsFetcher(
            $parsedJwsFactory,
            $artifactFetcher,
            $maxCacheDuration,
            $helpers,
            $logger,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(JwsFetcher::class, $this->sut());
    }

    public function testCanFetchFromCache(): void
    {
        $this->artifactFetcherMock->expects($this->once())->method('fromCacheAsString')
            ->with('uri')
            ->willReturn('token');

        $this->parsedJwsFactoryMock->expects($this->once())->method('fromToken')
            ->with('token');

        $this->assertInstanceOf(ParsedJws::class, $this->sut()->fromCache('uri'));
    }

    public function testCanFetchFromCacheOrNetwork(): void
    {
        $this->artifactFetcherMock->expects($this->once())->method('fromCacheAsString')
            ->with('uri')
            ->willReturn(null);

        $this->responseMock->method('getStatusCode')->willReturn(200);

        $this->artifactFetcherMock->expects($this->once())->method('fromNetwork')
            ->with('uri')
            ->willReturn($this->responseMock);

        $this->assertInstanceOf(
            ParsedJws::class,
            $this->sut()->fromCacheOrNetwork('uri'),
        );
    }

    public function testFetchFromNetworkThrowsForInvalidResponseStatusCode(): void
    {
        $this->artifactFetcherMock->expects($this->once())->method('fromNetwork')
            ->with('uri');

        $this->responseMock->method('getStatusCode')->willReturn(500);

        $this->expectException(FetchException::class);
        $this->expectExceptionMessage('500');

        $this->loggerMock->expects($this->once())->method('error')
            ->with($this->stringContains('500'));

        $this->sut()->fromNetwork('uri');
    }

    public function testChecksForExpectedContentTypeHttpHeader(): void
    {
        $sut = new class (
            $this->parsedJwsFactoryMock,
            $this->artifactFetcherMock,
            $this->maxCacheDurationMock,
            $this->helpersMock,
            $this->loggerMock,
        ) extends JwsFetcher {
            public function getExpectedContentTypeHttpHeader(): ?string
            {
                return 'application/jwt';
            }
        };

        $this->responseMock->method('getStatusCode')->willReturn(200);

        $this->expectException(FetchException::class);
        $this->expectExceptionMessage('application/jwt');

        $sut->fromNetwork('uri');
    }

    public function testWillUseJwsExpirationTimeWhenConsideringTtlForCaching(): void
    {
        $expirationTime = time() + 60;
        $this->responseMock->method('getStatusCode')->willReturn(200);

        $this->parsedJwsMock->expects($this->once())->method('getExpirationTime')
            ->willReturn($expirationTime);

        $this->parsedJwsFactoryMock->expects($this->once())->method('fromToken')
            ->willReturn($this->parsedJwsMock);

        $this->maxCacheDurationMock->expects($this->once())->method('lowestInSecondsComparedToExpirationTime')
            ->with($expirationTime)
        ->willReturn(60);

        $this->artifactFetcherMock->expects($this->once())->method('cacheIt')
            ->with($this->isType('string'), 60, 'uri');


        $this->sut()->fromNetwork('uri');
    }
}
