<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\EntityCollection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Federation\EntityCollection\InMemoryEntityCollectionStore;

#[CoversClass(InMemoryEntityCollectionStore::class)]
final class InMemoryEntityCollectionStoreTest extends TestCase
{
    private InMemoryEntityCollectionStore $store;


    protected function setUp(): void
    {
        $this->store = new InMemoryEntityCollectionStore();
    }


    public function testStoreAndGet(): void
    {
        $entities = ['id1' => ['sub' => 'id1']];
        $this->store->store('anchor', $entities, 3600);

        $this->assertSame($entities, $this->store->get('anchor'));
    }


    public function testGetExpired(): void
    {
        $entities = ['id1' => ['sub' => 'id1']];
        // Store with negative TTL so it's immediately expired
        $this->store->store('anchor', $entities, -10);

        $this->assertNull($this->store->get('anchor'));
    }


    public function testGetNonExistent(): void
    {
        $this->assertNull($this->store->get('non-existent'));
    }


    public function testClear(): void
    {
        $entities = ['id1' => ['sub' => 'id1']];
        $this->store->store('anchor', $entities, 3600);
        $this->store->clear('anchor');

        $this->assertNull($this->store->get('anchor'));
    }


    public function testLastUpdated(): void
    {
        $timestamp = 123456789;
        $this->store->storeLastUpdated('anchor', $timestamp, 3600);

        $this->assertSame($timestamp, $this->store->getLastUpdated('anchor'));

        $this->store->clearLastUpdated('anchor');
        $this->assertNull($this->store->getLastUpdated('anchor'));
    }
}
