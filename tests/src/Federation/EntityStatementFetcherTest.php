<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Federation\EntityStatement;
use SimpleSAML\OpenID\Federation\EntityStatementFetcher;
use SimpleSAML\OpenID\Federation\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Helpers;

#[CoversClass(EntityStatementFetcher::class)]
class EntityStatementFetcherTest extends TestCase
{
    protected MockObject $httpClientDecoratorMock;
    protected MockObject $entityStatementFactoryMock;
    protected MockObject $maxCacheDurationMock;
    protected MockObject $helpersMock;
    protected MockObject $cacheDecoratorMock;
    protected MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->httpClientDecoratorMock = $this->createMock(HttpClientDecorator::class);
        $this->entityStatementFactoryMock = $this->createMock(EntityStatementFactory::class);
        $this->maxCacheDurationMock = $this->createMock(DateIntervalDecorator::class);
        $this->helpersMock = $this->createMock(Helpers::class);
        $this->cacheDecoratorMock = $this->createMock(CacheDecorator::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
    }

    protected function sut(
        ?HttpClientDecorator $httpClientDecorator = null,
        ?EntityStatementFactory $entityStatementFactory = null,
        ?DateIntervalDecorator $maxCacheDuration = null,
        ?Helpers $helpers = null,
        ?CacheDecorator $cacheDecorator = null,
        ?LoggerInterface $logger = null,
    ): EntityStatementFetcher {
        $httpClientDecorator ??= $this->httpClientDecoratorMock;
        $entityStatementFactory ??= $this->entityStatementFactoryMock;
        $maxCacheDuration ??= $this->maxCacheDurationMock;
        $helpers ??= $this->helpersMock;
        $cacheDecorator ??= $this->cacheDecoratorMock;
        $logger ??= $this->loggerMock;

        return new EntityStatementFetcher(
            $httpClientDecorator,
            $entityStatementFactory,
            $maxCacheDuration,
            $helpers,
            $cacheDecorator,
            $logger,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(EntityStatementFetcher::class, $this->sut());
    }

    public function testCanFetchFromCache(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(null, 'uri')
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
