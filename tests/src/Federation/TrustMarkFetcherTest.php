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
use SimpleSAML\OpenID\Federation\Factories\TrustMarkFactory;
use SimpleSAML\OpenID\Federation\TrustMark;
use SimpleSAML\OpenID\Federation\TrustMarkFetcher;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jws\AbstractJwsFetcher;
use SimpleSAML\OpenID\Jws\JwsFetcher;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

#[CoversClass(TrustMarkFetcher::class)]
#[UsesClass(AbstractJwsFetcher::class)]
#[UsesClass(JwsFetcher::class)]
#[UsesClass(WellKnownEnum::class)]
final class TrustMarkFetcherTest extends TestCase
{
    protected MockObject $trustMarkFactoryMock;

    protected MockObject $artifactFetcherMock;

    protected MockObject $maxCacheDurationMock;

    protected MockObject $helpersMock;

    protected MockObject $loggerMock;

    protected MockObject $responseMock;

    protected MockObject $entityStatementMock;

    protected function setUp(): void
    {
        $this->trustMarkFactoryMock = $this->createMock(TrustMarkFactory::class);
        $this->artifactFetcherMock = $this->createMock(ArtifactFetcher::class);
        $this->maxCacheDurationMock = $this->createMock(DateIntervalDecorator::class);
        $this->helpersMock = $this->createMock(Helpers::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->artifactFetcherMock->method('fromNetwork')->willReturn($this->responseMock);

        $this->entityStatementMock = $this->createMock(EntityStatement::class);
    }

    protected function sut(
        ?TrustMarkFactory $trustMarkFactoryMock = null,
        ?ArtifactFetcher $artifactFetcher = null,
        ?DateIntervalDecorator $maxCacheDuration = null,
        ?Helpers $helpers = null,
        ?LoggerInterface $logger = null,
    ): TrustMarkFetcher {
        $trustMarkFactoryMock ??= $this->trustMarkFactoryMock;
        $artifactFetcher ??= $this->artifactFetcherMock;
        $maxCacheDuration ??= $this->maxCacheDurationMock;
        $helpers ??= $this->helpersMock;
        $logger ??= $this->loggerMock;

        return new TrustMarkFetcher(
            $trustMarkFactoryMock,
            $artifactFetcher,
            $maxCacheDuration,
            $helpers,
            $logger,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustMarkFetcher::class, $this->sut());
    }

    public function testHasRightExpectedContentTypeHttpHeader(): void
    {
        $this->assertSame(
            ContentTypesEnum::ApplicationTrustMarkJwt->value,
            $this->sut()->getExpectedContentTypeHttpHeader(),
        );
    }

    public function testCanFetchFromCacheOrTrustMarkEndpointWhenCached(): void
    {
        $this->entityStatementMock->expects($this->once())
            ->method('getFederationTrustMarkEndpoint')
            ->willReturn('trust-mark-uri');

        $this->artifactFetcherMock->expects($this->once())->method('fromCacheAsString')
            ->willReturn('token');

        $this->trustMarkFactoryMock->expects($this->once())->method('fromToken')
            ->with('token');

        $this->sut()->fromCacheOrFederationTrustMarkEndpoint(
            'trustMarkId',
            'entityId',
            $this->entityStatementMock,
        );
    }

    public function testCanFetchFromCacheOrTrustMarkEndpointWhenNotCached(): void
    {
        $this->entityStatementMock->expects($this->once())
            ->method('getFederationTrustMarkEndpoint')
            ->willReturn('trust-mark-uri');

        $this->artifactFetcherMock->expects($this->once())->method('fromCacheAsString')
            ->willReturn(null);
        $this->artifactFetcherMock->expects($this->once())->method('fromNetwork');

        $this->responseMock->method('getStatusCode')->willReturn(200);
        $this->responseMock->method('getHeaderLine')->willReturn('application/trust-mark+jwt');

        $this->trustMarkFactoryMock->expects($this->once())->method('fromToken');

        $this->sut()->fromCacheOrFederationTrustMarkEndpoint(
            'trustMarkId',
            'entityId',
            $this->entityStatementMock,
        );
    }

    public function testFetchFromCacheOrTrustMarkEndpointThrowsIfNoFetchEndpoint(): void
    {
        $this->entityStatementMock->expects($this->once())
            ->method('getFederationTrustMarkEndpoint')
            ->willReturn(null);

        $this->expectException(EntityStatementException::class);
        $this->expectExceptionMessage('endpoint');

        $this->sut()->fromCacheOrFederationTrustMarkEndpoint(
            'trustMarkId',
            'entityId',
            $this->entityStatementMock,
        );
    }

    public function testCanFetchFromCache(): void
    {
        $this->artifactFetcherMock->expects($this->once())->method('fromCacheAsString')
            ->with('uri')
            ->willReturn('token');

        $this->trustMarkFactoryMock->expects($this->once())->method('fromToken')
            ->with('token');

        $this->assertInstanceOf(TrustMark::class, $this->sut()->fromCache('uri'));
    }
}
