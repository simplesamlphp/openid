<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\Claims;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimBag;
use SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimValue;

#[CoversClass(TrustMarksClaimBag::class)]
class TrustMarkClaimBagTest extends TestCase
{
    protected MockObject $trustMarkClaimMock;

    protected function setUp(): void
    {
        $this->trustMarkClaimMock = $this->createMock(\SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimValue::class);
    }

    protected function sut(
        \SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimValue ...$trustMarkClaims,
    ): TrustMarksClaimBag {
        return new \SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimBag(...$trustMarkClaims);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustMarksClaimBag::class, $this->sut());
        $this->assertInstanceOf(TrustMarksClaimBag::class, $this->sut($this->trustMarkClaimMock));
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
        $firstTrustMarkClaim = $this->createMock(TrustMarksClaimValue::class);
        $firstTrustMarkClaim->method('getTrustMarkId')->willReturn('first');
        $secondTrustMarkClaim = $this->createMock(TrustMarksClaimValue::class);
        $secondTrustMarkClaim->method('getTrustMarkId')->willReturn('second');

        $sut = $this->sut($firstTrustMarkClaim, $secondTrustMarkClaim);

        $allForSecond = $sut->gerAllFor('second');

        $this->assertCount(1, $allForSecond);
        $this->assertSame($secondTrustMarkClaim->getTrustMarkId(), $allForSecond[0]->getTrustMarkId());
    }

    public function testCanGetFirstFor(): void
    {
        $firstTrustMarkClaim = $this->createMock(TrustMarksClaimValue::class);
        $firstTrustMarkClaim->method('getTrustMarkId')->willReturn('first');
        $secondTrustMarkClaim = $this->createMock(\SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimValue::class);
        $secondTrustMarkClaim->method('getTrustMarkId')->willReturn('second');

        $sut = $this->sut($firstTrustMarkClaim, $secondTrustMarkClaim);

        $second = $sut->getFirstFor('second');
        $this->assertSame($secondTrustMarkClaim->getTrustMarkId(), $second->getTrustMarkId());
    }

    public function testGetFirstForReturnNullIfNoneFound(): void
    {

        $firstTrustMarkClaim = $this->createMock(TrustMarksClaimValue::class);
        $firstTrustMarkClaim->method('getTrustMarkId')->willReturn('first');

        $sut = $this->sut($firstTrustMarkClaim);

        $this->assertNull($sut->getFirstFor('second'));
    }
}
