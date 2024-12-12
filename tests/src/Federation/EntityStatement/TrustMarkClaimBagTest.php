<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\EntityStatement;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Federation\EntityStatement\TrustMarkClaim;
use SimpleSAML\OpenID\Federation\EntityStatement\TrustMarkClaimBag;

#[CoversClass(TrustMarkClaimBag::class)]
class TrustMarkClaimBagTest extends TestCase
{
    protected MockObject $trustMarkClaimMock;

    protected function setUp(): void
    {
        $this->trustMarkClaimMock = $this->createMock(TrustMarkClaim::class);
    }

    protected function sut(
        TrustMarkClaim ...$trustMarkClaims,
    ): TrustMarkClaimBag {
        return new TrustMarkClaimBag(...$trustMarkClaims);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustMarkClaimBag::class, $this->sut());
        $this->assertInstanceOf(TrustMarkClaimBag::class, $this->sut($this->trustMarkClaimMock));
    }

    public function testCanGetAll(): void
    {
        $sut = $this->sut();

        $this->assertEmpty($sut->getAll());
        $sut->add($this->trustMarkClaimMock);
        $this->assertCount(1, $sut->getAll());
        $sut->add($this->trustMarkClaimMock, $this->trustMarkClaimMock);
        $this->assertCount(3, $sut->getAll());
    }

    public function testCanGetAllFor(): void
    {
        $firstTrustMarkClaim = $this->createMock(TrustMarkClaim::class);
        $firstTrustMarkClaim->method('getId')->willReturn('first');
        $secondTrustMarkClaim = $this->createMock(TrustMarkClaim::class);
        $secondTrustMarkClaim->method('getId')->willReturn('second');

        $sut = $this->sut($firstTrustMarkClaim, $secondTrustMarkClaim);

        $allForSecond = $sut->gerAllFor('second');

        $this->assertCount(1, $allForSecond);
        $this->assertSame($secondTrustMarkClaim->getId(), $allForSecond[0]->getId());
    }

    public function testCanGetFirstFor(): void
    {
        $firstTrustMarkClaim = $this->createMock(TrustMarkClaim::class);
        $firstTrustMarkClaim->method('getId')->willReturn('first');
        $secondTrustMarkClaim = $this->createMock(TrustMarkClaim::class);
        $secondTrustMarkClaim->method('getId')->willReturn('second');

        $sut = $this->sut($firstTrustMarkClaim, $secondTrustMarkClaim);

        $second = $sut->getFirstFor('second');
        $this->assertSame($secondTrustMarkClaim->getId(), $second->getId());
    }

    public function testGetFirstForReturnNullIfNoneFound(): void
    {

        $firstTrustMarkClaim = $this->createMock(TrustMarkClaim::class);
        $firstTrustMarkClaim->method('getId')->willReturn('first');

        $sut = $this->sut($firstTrustMarkClaim);

        $this->assertNull($sut->getFirstFor('second'));
    }
}
