<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\Claims;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Federation\Claims\TrustMarkOwnersClaimBag;
use SimpleSAML\OpenID\Federation\Claims\TrustMarkOwnersClaimValue;

#[CoversClass(TrustMarkOwnersClaimBag::class)]
final class TrustMarkOwnersClaimBagTest extends TestCase
{
    protected MockObject $trustMarkOwnersClaimValueMock;

    protected function setUp(): void
    {
        $this->trustMarkOwnersClaimValueMock = $this->createMock(TrustMarkOwnersClaimValue::class);
        $this->trustMarkOwnersClaimValueMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkOwnersClaimValueMock->method('getSubject')->willReturn('subject');
    }

    protected function sut(
        TrustMarkOwnersClaimValue ...$trustMarkOwnersClaimValue,
    ): TrustMarkOwnersClaimBag {
        return new TrustMarkOwnersClaimBag(...$trustMarkOwnersClaimValue);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustMarkOwnersClaimBag::class, $this->sut());
    }

    public function testCanAddAndGet(): void
    {
        $this->assertEmpty($this->sut()->getAll());
        $sut = $this->sut($this->trustMarkOwnersClaimValueMock);
        $this->assertCount(1, $sut->getAll());
        $this->assertTrue($sut->has('trustMarkType'));
        $this->assertSame($this->trustMarkOwnersClaimValueMock, $sut->get('trustMarkType'));

        $trustMarkClaimValueMock2 = $this->createMock(TrustMarkOwnersClaimValue::class);
        $trustMarkClaimValueMock2->method('getTrustMarkType')->willReturn('trustMarkType2');
        $sut->add($trustMarkClaimValueMock2);
        $this->assertCount(2, $sut->getAll());
        $this->assertTrue($sut->has('trustMarkType2'));
        $this->assertSame($trustMarkClaimValueMock2, $sut->get('trustMarkType2'));
    }

    public function testCanJsonSerialize(): void
    {
        $trustMarkClaimValueMock2 = $this->createMock(TrustMarkOwnersClaimValue::class);
        $trustMarkClaimValueMock2->method('getTrustMarkType')->willReturn('trustMarkType2');
        $this->trustMarkOwnersClaimValueMock->method('getSubject')->willReturn('subject2');

        $sut = $this->sut(
            $this->trustMarkOwnersClaimValueMock,
            $trustMarkClaimValueMock2,
        );

        $this->assertArrayHasKey('trustMarkType', $sut->jsonSerialize());
        $this->assertArrayHasKey('trustMarkType2', $sut->jsonSerialize());
    }
}
