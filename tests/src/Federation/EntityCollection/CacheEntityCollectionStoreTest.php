<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\EntityCollection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Federation\EntityCollection\CacheEntityCollectionStore;
use SimpleSAML\OpenID\Helpers;

#[CoversClass(CacheEntityCollectionStore::class)]
final class CacheEntityCollectionStoreTest extends TestCase
{
    private CacheDecorator&MockObject $cacheDecorator;

    private LoggerInterface&MockObject $logger;

    private CacheEntityCollectionStore $store;


    protected function setUp(): void
    {
        $this->cacheDecorator = $this->createMock(CacheDecorator::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->store = new CacheEntityCollectionStore(
            $this->cacheDecorator,
            $this->createStub(Helpers::class),
            $this->logger,
        );
    }


    public function testStore(): void
    {
        $entities = ['id1' => ['sub' => 'id1']];
        $this->cacheDecorator->expects($this->once())
            ->method('set')
            ->with($entities, 3600, 'federation_entities', 'anchor');

        $this->store->store('anchor', $entities, 3600);
    }


    public function testStoreFailureLogsError(): void
    {
        $this->cacheDecorator->method('set')->willThrowException(new \Exception('error'));
        $this->logger->expects($this->once())->method('error');

        $this->store->store('anchor', [], 3600);
    }


    public function testGet(): void
    {
        $entities = ['id1' => ['sub' => 'id1']];
        $this->cacheDecorator->expects($this->once())
            ->method('get')
            ->with(null, 'federation_entities', 'anchor')
            ->willReturn($entities);

        $this->assertSame($entities, $this->store->get('anchor'));
    }


    public function testGetReturnsNullIfNotArray(): void
    {
        $this->cacheDecorator->method('get')->willReturn('not-an-array');
        $this->assertNull($this->store->get('anchor'));
    }


    public function testGetFailureLogsError(): void
    {
        $this->cacheDecorator->method('get')->willThrowException(new \Exception('error'));
        $this->logger->expects($this->once())->method('error');

        $this->assertNull($this->store->get('anchor'));
    }


    public function testClear(): void
    {
        $this->cacheDecorator->expects($this->once())
            ->method('delete')
            ->with('federation_entities', 'anchor');

        $this->store->clear('anchor');
    }


    public function testClearFailureLogsError(): void
    {
        $this->cacheDecorator->method('delete')->willThrowException(new \Exception('error'));
        $this->logger->expects($this->once())->method('error');

        $this->store->clear('anchor');
    }


    public function testStoreLastUpdated(): void
    {
        $this->cacheDecorator->expects($this->once())
            ->method('set')
            ->with('123456789', 3600, 'last_updated', 'anchor');

        $this->store->storeLastUpdated('anchor', 123456789, 3600);
    }


    public function testGetLastUpdated(): void
    {
        $this->cacheDecorator->expects($this->once())
            ->method('get')
            ->with(null, 'last_updated', 'anchor')
            ->willReturn(123456789);

        $this->assertSame(123456789, $this->store->getLastUpdated('anchor'));
    }


    public function testGetLastUpdatedReturnsNullIfNotInt(): void
    {
        $this->cacheDecorator->method('get')->willReturn('string');
        $this->assertNull($this->store->getLastUpdated('anchor'));
    }


    public function testClearLastUpdated(): void
    {
        $this->cacheDecorator->expects($this->once())
            ->method('delete')
            ->with('last_updated', 'anchor');

        $this->store->clearLastUpdated('anchor');
    }


    public function testStoreLastUpdatedFailureLogsError(): void
    {
        $this->cacheDecorator->method('set')->willThrowException(new \Exception('error'));
        $this->logger->expects($this->once())->method('error');

        $this->store->storeLastUpdated('anchor', 123456789, 3600);
    }


    public function testClearLastUpdatedFailureLogsError(): void
    {
        $this->cacheDecorator->method('delete')->willThrowException(new \Exception('error'));
        $this->logger->expects($this->once())->method('error');

        $this->store->clearLastUpdated('anchor');
    }


    public function testGetLastUpdatedFailureLogsError(): void
    {
        $this->cacheDecorator->method('get')->willThrowException(new \Exception('error'));
        $this->logger->expects($this->once())->method('error');

        $this->assertNull($this->store->getLastUpdated('anchor'));
    }
}
