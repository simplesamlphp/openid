<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Federation\EntityStatement;
use SimpleSAML\OpenID\Federation\EntityStatementFetcher;
use SimpleSAML\OpenID\Federation\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

#[CoversClass(EntityStatementFetcher::class)]
class EntityStatementFetcherTest extends TestCase
{
    protected MockObject $artifactFetcherMock;
    protected MockObject $entityStatementFactoryMock;
    protected MockObject $maxCacheDurationMock;
    protected MockObject $helpersMock;
    protected MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->artifactFetcherMock = $this->createMock(ArtifactFetcher::class);
        $this->entityStatementFactoryMock = $this->createMock(EntityStatementFactory::class);
        $this->maxCacheDurationMock = $this->createMock(DateIntervalDecorator::class);
        $this->helpersMock = $this->createMock(Helpers::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
    }

    protected function sut(
        ?ArtifactFetcher $artifactFetcher = null,
        ?EntityStatementFactory $entityStatementFactory = null,
        ?DateIntervalDecorator $maxCacheDuration = null,
        ?Helpers $helpers = null,
        ?LoggerInterface $logger = null,
    ): EntityStatementFetcher {
        $artifactFetcher ??= $this->artifactFetcherMock;
        $entityStatementFactory ??= $this->entityStatementFactoryMock;
        $maxCacheDuration ??= $this->maxCacheDurationMock;
        $helpers ??= $this->helpersMock;
        $logger ??= $this->loggerMock;

        return new EntityStatementFetcher(
            $artifactFetcher,
            $entityStatementFactory,
            $maxCacheDuration,
            $helpers,
            $logger,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(EntityStatementFetcher::class, $this->sut());
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

    public function testFetchFromCacheReturnsNull(): void
    {
        $this->markTestIncomplete('TODO mivanci');
    }
}
