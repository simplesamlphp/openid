<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\ContentTypesEnum;
use SimpleSAML\OpenID\Codebooks\WellKnownEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\EntityStatementException;
use SimpleSAML\OpenID\Federation\EntityStatement;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkStatusResponseFactory;
use SimpleSAML\OpenID\Federation\TrustMark;
use SimpleSAML\OpenID\Federation\TrustMarkStatusResponseFetcher;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jws\AbstractJwsFetcher;
use SimpleSAML\OpenID\Jws\JwsFetcher;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

#[CoversClass(TrustMarkStatusResponseFetcher::class)]
#[UsesClass(AbstractJwsFetcher::class)]
#[UsesClass(JwsFetcher::class)]
#[UsesClass(WellKnownEnum::class)]
final class TrustMarkStatusResponseFetcherTest extends TestCase
{
    protected MockObject $trustMarkStatusResponseFactoryMock;

    protected MockObject $artifactFetcherMock;

    protected MockObject $maxCacheDurationMock;

    protected MockObject $helpersMock;

    protected MockObject $loggerMock;

    protected MockObject $responseMock;

    protected MockObject $entityStatementMock;

    protected MockObject $trustMarkMock;


    protected function setUp(): void
    {
        $this->trustMarkStatusResponseFactoryMock = $this->createMock(TrustMarkStatusResponseFactory::class);
        $this->artifactFetcherMock = $this->createMock(ArtifactFetcher::class);
        $this->maxCacheDurationMock = $this->createMock(DateIntervalDecorator::class);
        $this->helpersMock = $this->createMock(Helpers::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->artifactFetcherMock->method('fromNetwork')->willReturn($this->responseMock);

        $this->entityStatementMock = $this->createMock(EntityStatement::class);
        $this->trustMarkMock = $this->createMock(TrustMark::class);
    }


    protected function sut(
        ?TrustMarkStatusResponseFactory $trustMarkStatusResponseFactory = null,
        ?ArtifactFetcher $artifactFetcher = null,
        ?DateIntervalDecorator $maxCacheDuration = null,
        ?Helpers $helpers = null,
        ?LoggerInterface $logger = null,
    ): TrustMarkStatusResponseFetcher {
        $trustMarkStatusResponseFactory ??= $this->trustMarkStatusResponseFactoryMock;
        $artifactFetcher ??= $this->artifactFetcherMock;
        $maxCacheDuration ??= $this->maxCacheDurationMock;
        $helpers ??= $this->helpersMock;
        $logger ??= $this->loggerMock;

        return new TrustMarkStatusResponseFetcher(
            $trustMarkStatusResponseFactory,
            $artifactFetcher,
            $maxCacheDuration,
            $helpers,
            $logger,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustMarkStatusResponseFetcher::class, $this->sut());
    }


    public function testHasRightExpectedContentTypeHttpHeader(): void
    {
        $this->assertSame(
            ContentTypesEnum::ApplicationTrustMarkStatusResponseJwt->value,
            $this->sut()->getExpectedContentTypeHttpHeader(),
        );
    }


    public function testCanFetchFromTrustMarkStatusEndpoint(): void
    {
        $this->entityStatementMock->expects($this->once())
            ->method('getFederationTrustMarkStatusEndpoint')
            ->willReturn('trust-mark-status-uri');

        $this->artifactFetcherMock->expects($this->never())->method('fromCacheAsString');
        $this->artifactFetcherMock->expects($this->once())->method('fromNetwork');

        $this->responseMock->method('getStatusCode')->willReturn(200);
        $this->responseMock->method('getHeaderLine')
            ->willReturn('application/trust-mark-status-response+jwt');

        $this->trustMarkStatusResponseFactoryMock->expects($this->once())->method('fromToken');

        $this->sut()->fromFederationTrustMarkStatusEndpoint(
            $this->trustMarkMock,
            $this->entityStatementMock,
        );
    }


    public function testFetchFromTrustMarkStatusEndpointThrowsIfNoFetchEndpoint(): void
    {
        $this->entityStatementMock->expects($this->once())
            ->method('getFederationTrustMarkStatusEndpoint')
            ->willReturn(null);

        $this->expectException(EntityStatementException::class);
        $this->expectExceptionMessage('endpoint');

        $this->sut()->fromFederationTrustMarkStatusEndpoint(
            $this->trustMarkMock,
            $this->entityStatementMock,
        );
    }
}
