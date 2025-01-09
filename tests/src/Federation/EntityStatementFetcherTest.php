<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
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
class EntityStatementFetcherTest extends TestCase
{
    protected MockObject $entityStatementFactoryMock;
    protected MockObject $artifactFetcherMock;
    protected MockObject $maxCacheDurationMock;
    protected MockObject $helpersMock;
    protected MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->entityStatementFactoryMock = $this->createMock(EntityStatementFactory::class);
        $this->artifactFetcherMock = $this->createMock(ArtifactFetcher::class);
        $this->maxCacheDurationMock = $this->createMock(DateIntervalDecorator::class);
        $this->helpersMock = $this->createMock(Helpers::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
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
