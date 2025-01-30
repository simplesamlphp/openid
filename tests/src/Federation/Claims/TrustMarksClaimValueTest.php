<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\Claims;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimValue;

#[CoversClass(TrustMarksClaimValue::class)]
class TrustMarksClaimValueTest extends TestCase
{
    protected string $id;
    protected string $trustMark;
    protected array $otherClaims = [];

    protected function setUp(): void
    {
        $this->id = 'id';
        $this->trustMark = 'token';
        $this->otherClaims = ['something' => 'else'];
    }

    protected function sut(
        ?string $id = null,
        ?string $trustMark = null,
        ?array $otherClaims = null,
    ): TrustMarksClaimValue {
        $id ??= $this->id;
        $trustMark ??= $this->trustMark;
        $otherClaims ??= $this->otherClaims;

        return new TrustMarksClaimValue($id, $trustMark, $otherClaims);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustMarksClaimValue::class, $this->sut());
    }

    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertSame($this->id, $sut->getTrustMarkId());
        $this->assertSame($this->trustMark, $sut->getTrustMark());
        $this->assertSame($this->otherClaims, $sut->getOtherClaims());
    }

    // TODO mivanci Move this checks to TrustMarkValiadtor once the testbed starts conforming.
//    public function testConstructThrowsForDifferentIds(): void
//    {
//        $this->expectException(TrustMarkClaimException::class);
//        $this->expectExceptionMessage('identifier');
//
//        $this->sut('differentId');
//    }
//
//    public function testConstructThrowsForDifferentTrustMarkPayload(): void
//    {
//        $this->trustMark->method('getPayload')
//            ->willReturn(['something' => 'different']);
//
//        $this->expectException(TrustMarkClaimException::class);
//        $this->expectExceptionMessage('different');
//
//        $this->sut();
//    }
}
