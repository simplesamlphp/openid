<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Federation\EntityStatement;
use SimpleSAML\OpenID\Federation\TrustChain;
use SimpleSAML\OpenID\Federation\TrustChainBag;

#[CoversClass(TrustChainBag::class)]
class TrustChainBagTest extends TestCase
{
    protected MockObject $trustChainMock;

    protected function setUp(): void
    {
        $this->trustChainMock = $this->createMock(TrustChain::class);
    }

    protected function sut(
        ?TrustChain $trustChain = null,
    ): TrustChainBag {
        $trustChain ??= $this->trustChainMock;

        return new TrustChainBag($trustChain);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustChainBag::class, $this->sut());
    }

    public function testCanAdd(): void
    {
        $sut = $this->sut();
        $this->assertCount(1, $sut->getAll());
        $sut->add($this->trustChainMock);
        $this->assertCount(2, $sut->getAll());
    }

    public function testCanGetShortest(): void
    {
        $shortest = $this->createMock(TrustChain::class);
        $shortest->method('getResolvedLength')->willReturn(2);
        $mid = $this->createMock(TrustChain::class);
        $mid->method('getResolvedLength')->willReturn(3);
        $longest = $this->createMock(TrustChain::class);
        $longest->method('getResolvedLength')->willReturn(4);

        $sut = $this->sut($longest);

        $this->assertCount(1, $sut->getAll());
        $this->assertSame(4, $sut->getShortest()->getResolvedLength());

        $sut->add($mid);
        $this->assertCount(2, $sut->getAll());
        $this->assertSame(3, $sut->getShortest()->getResolvedLength());

        $sut->add($shortest);
        $this->assertCount(3, $sut->getAll());
        $this->assertSame(2, $sut->getShortest()->getResolvedLength());
    }

    public function testCanGetShortestByTrustAnchorPriority(): void
    {
        $trustAnchor1 = $this->createMock(EntityStatement::class);
        $trustAnchor1->method('getIssuer')->willReturn('ta1');
        $chain1 = $this->createMock(TrustChain::class);
        $chain1->method('getResolvedTrustAnchor')->willReturn($trustAnchor1);
        $chain1->method('getResolvedLength')->willReturn(2);

        $trustAnchor2 = $this->createMock(EntityStatement::class);
        $trustAnchor2->method('getIssuer')->willReturn('ta2');
        $chain2 = $this->createMock(TrustChain::class);
        $chain2->method('getResolvedTrustAnchor')->willReturn($trustAnchor2);
        $chain2->method('getResolvedLength')->willReturn(3);

        $sut = $this->sut($chain2);
        $sut->add($chain1);

        $this->assertSame($chain1, $sut->getShortestByTrustAnchorPriority('ta1'));
        $this->assertSame($sut->getShortestByTrustAnchorPriority('ta1', 'ta2')
            ->getResolvedTrustAnchor()->getIssuer(), 'ta1');

        $this->assertSame($chain2, $sut->getShortestByTrustAnchorPriority('ta2'));
        $this->assertSame($sut->getShortestByTrustAnchorPriority('ta2', 'ta1')
            ->getResolvedTrustAnchor()->getIssuer(), 'ta2');

        // Can get chain even if some trust anchors are unknown.
        $this->assertSame($chain2, $sut->getShortestByTrustAnchorPriority('unknown', 'ta2'));
        $this->assertSame($sut->getShortestByTrustAnchorPriority('unknown', 'ta2', 'ta1')
            ->getResolvedTrustAnchor()->getIssuer(), 'ta2');

        // Returns null if Trust Anchor is unknown.
        $this->assertNull($sut->getShortestByTrustAnchorPriority('unknown'));
    }

    public function testCanGetCount(): void
    {
        $this->assertSame(1, $this->sut()->getCount());
    }
}
