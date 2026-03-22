<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\ValueAbstracts;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\ValueAbstracts\TrustAnchorConfig;
use SimpleSAML\OpenID\ValueAbstracts\TrustAnchorConfigBag;

#[CoversClass(TrustAnchorConfigBag::class)]
final class TrustAnchorConfigBagTest extends TestCase
{
    private function mockConfig(string $entityId): TrustAnchorConfig
    {
        $config = $this->createMock(TrustAnchorConfig::class);
        $config->method('getEntityId')->willReturn($entityId);

        return $config;
    }


    public function testConstructAndGetAll(): void
    {
        $config1 = $this->mockConfig('https://anchor1.example.org');
        $config2 = $this->mockConfig('https://anchor2.example.org');
        $bag = new TrustAnchorConfigBag($config1, $config2);

        $this->assertCount(2, $bag->getAll());
        $this->assertSame($config1, $bag->getByEntityId('https://anchor1.example.org'));
        $this->assertSame($config2, $bag->getByEntityId('https://anchor2.example.org'));
    }


    public function testAdd(): void
    {
        $config1 = $this->mockConfig('https://anchor1.example.org');
        $bag = new TrustAnchorConfigBag();
        $bag->add($config1);

        $this->assertCount(1, $bag->getAll());
        $this->assertSame($config1, $bag->getByEntityId('https://anchor1.example.org'));
    }


    public function testGetByEntityIdReturnsNullWhenNotFound(): void
    {
        $bag = new TrustAnchorConfigBag();
        $this->assertNotInstanceOf(
            \SimpleSAML\OpenID\ValueAbstracts\TrustAnchorConfig::class,
            $bag->getByEntityId('https://non-existent.example.org'),
        );
    }


    public function testGetByEntityIdOrFailThrowsWhenNotFound(): void
    {
        $bag = new TrustAnchorConfigBag();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("No trust anchor config found for entity ID 'https://non-existent.example.org'.");
        $bag->getByEntityIdOrFail('https://non-existent.example.org');
    }


    public function testGetByEntityIdOrFailReturnsConfig(): void
    {
        $config1 = $this->mockConfig('https://anchor1.example.org');
        $bag = new TrustAnchorConfigBag($config1);

        $this->assertSame($config1, $bag->getByEntityIdOrFail('https://anchor1.example.org'));
    }


    public function testGetAllEntityIds(): void
    {
        $config1 = $this->mockConfig('https://anchor1.example.org');
        $config2 = $this->mockConfig('https://anchor2.example.org');
        $bag = new TrustAnchorConfigBag($config1, $config2);

        $entityIds = $bag->getAllEntityIds();
        $this->assertCount(2, $entityIds);
        $this->assertContains('https://anchor1.example.org', $entityIds);
        $this->assertContains('https://anchor2.example.org', $entityIds);
    }


    public function testHas(): void
    {
        $config1 = $this->mockConfig('https://anchor1.example.org');
        $bag = new TrustAnchorConfigBag($config1);

        $this->assertTrue($bag->has('https://anchor1.example.org'));
        $this->assertFalse($bag->has('https://anchor2.example.org'));
    }


    public function testGetInCommonWith(): void
    {
        $config1 = $this->mockConfig('https://anchor1.example.org');
        $config2 = $this->mockConfig('https://anchor2.example.org');
        $config3 = $this->mockConfig('https://anchor3.example.org');

        $bag1 = new TrustAnchorConfigBag($config1, $config2);
        $bag2 = new TrustAnchorConfigBag($config2, $config3);

        $commonBag = $bag1->getInCommonWith($bag2);

        $this->assertCount(1, $commonBag->getAll());
        $this->assertTrue($commonBag->has('https://anchor2.example.org'));
        $this->assertFalse($commonBag->has('https://anchor1.example.org'));
        $this->assertFalse($commonBag->has('https://anchor3.example.org'));
    }
}
