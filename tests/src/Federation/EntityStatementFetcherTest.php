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
use SimpleSAML\OpenID\Federation\EntityStatementFetcher;
use SimpleSAML\OpenID\Federation\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jws\AbstractJwsFetcher;
use SimpleSAML\OpenID\Jws\JwsFetcher;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

#[CoversClass(EntityStatementFetcher::class)]
#[UsesClass(AbstractJwsFetcher::class)]
#[UsesClass(JwsFetcher::class)]
#[UsesClass(WellKnownEnum::class)]
class EntityStatementFetcherTest extends TestCase
{
    protected MockObject $entityStatementFactoryMock;
    protected MockObject $artifactFetcherMock;
    protected MockObject $maxCacheDurationMock;
    protected MockObject $helpersMock;
    protected MockObject $loggerMock;
    protected MockObject $responseMock;
    protected MockObject $entityStatementMock;

    protected function setUp(): void
    {
        $this->entityStatementFactoryMock = $this->createMock(EntityStatementFactory::class);
        $this->artifactFetcherMock = $this->createMock(ArtifactFetcher::class);
        $this->maxCacheDurationMock = $this->createMock(DateIntervalDecorator::class);
        $this->helpersMock = $this->createMock(Helpers::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->artifactFetcherMock->method('fromNetwork')->willReturn($this->responseMock);

        $this->entityStatementMock = $this->createMock(EntityStatement::class);
    }

    protected function sut(
        ?EntityStatementFactory $entityStatementFactory = null,
        ?ArtifactFetcher $artifactFetcher = null,
        ?DateIntervalDecorator $maxCacheDuration = null,
        ?Helpers $helpers = null,
        ?LoggerInterface $logger = null,
    ): EntityStatementFetcher {
        $entityStatementFactory ??= $this->entityStatementFactoryMock;
        $artifactFetcher ??= $this->artifactFetcherMock;
        $maxCacheDuration ??= $this->maxCacheDurationMock;
        $helpers ??= $this->helpersMock;
        $logger ??= $this->loggerMock;

        return new EntityStatementFetcher(
            $entityStatementFactory,
            $artifactFetcher,
            $maxCacheDuration,
            $helpers,
            $logger,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(EntityStatementFetcher::class, $this->sut());
    }

    public function testHasRightExpectedContentTypeHttpHeader(): void
    {
        $this->assertSame(
            ContentTypesEnum::ApplicationEntityStatementJwt->value,
            $this->sut()->getExpectedContentTypeHttpHeader(),
        );
    }

    public function testCanFetchFromCacheOrWellKnownEndpoint(): void
    {
        $this->artifactFetcherMock->expects($this->once())->method('fromCacheAsString')
            ->willReturn(null);

        $this->responseMock->method('getStatusCode')->willReturn(200);
        $this->responseMock->method('getHeaderLine')
            ->willReturn(ContentTypesEnum::ApplicationEntityStatementJwt->value);

        $this->artifactFetcherMock->expects($this->once())->method('fromNetwork')
            ->willReturn($this->responseMock);

        $this->assertInstanceOf(
            EntityStatement::class,
            $this->sut()->fromCacheOrWellKnownEndpoint('entityId'),
        );
    }

    public function testCanFetchFromCacheOrFetchEndpoint(): void
    {
        $this->entityStatementMock->expects($this->once())
            ->method('getFederationFetchEndpoint')
            ->willReturn('fetch-uri');

        $this->artifactFetcherMock->expects($this->once())->method('fromCacheAsString')
            ->willReturn('token');

        $this->entityStatementFactoryMock->expects($this->once())->method('fromToken')
            ->with('token');

        $this->sut()->fromCacheOrFetchEndpoint('entityId', $this->entityStatementMock);
    }

    public function testFetchFromCacheOrFetchEndpointThrowsIfNoFetchEndpoint(): void
    {
        $this->entityStatementMock->expects($this->once())
            ->method('getFederationFetchEndpoint')
            ->willReturn(null);

        $this->expectException(EntityStatementException::class);
        $this->expectExceptionMessage('fetch');

        $this->sut()->fromCacheOrFetchEndpoint('entityId', $this->entityStatementMock);
    }

    public function testCanFetchFromCache(): void
    {
        $this->artifactFetcherMock->expects($this->once())->method('fromCacheAsString')
            ->with('uri')
            ->willReturn('token');

        $this->entityStatementFactoryMock->expects($this->once())->method('fromToken')
            ->with('token');

        $this->assertInstanceOf(EntityStatement::class, $this->sut()->fromCache('uri'));
    }
}
