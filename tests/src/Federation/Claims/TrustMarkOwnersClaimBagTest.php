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
        $this->trustMarkOwnersClaimValueMock->method('getTrustMarkId')->willReturn('trustMarkId');
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
        $this->assertTrue($sut->has('trustMarkId'));
        $this->assertSame($this->trustMarkOwnersClaimValueMock, $sut->get('trustMarkId'));

        $trustMarkClaimValueMock2 = $this->createMock(TrustMarkOwnersClaimValue::class);
        $trustMarkClaimValueMock2->method('getTrustMarkId')->willReturn('trustMarkId2');
        $sut->add($trustMarkClaimValueMock2);
        $this->assertCount(2, $sut->getAll());
        $this->assertTrue($sut->has('trustMarkId2'));
        $this->assertSame($trustMarkClaimValueMock2, $sut->get('trustMarkId2'));
    }

    public function testCanJsonSerialize(): void
    {
        $trustMarkClaimValueMock2 = $this->createMock(TrustMarkOwnersClaimValue::class);
        $trustMarkClaimValueMock2->method('getTrustMarkId')->willReturn('trustMarkId2');
        $this->trustMarkOwnersClaimValueMock->method('getSubject')->willReturn('subject2');

        $sut = $this->sut(
            $this->trustMarkOwnersClaimValueMock,
            $trustMarkClaimValueMock2,
        );

        $this->assertArrayHasKey('trustMarkId', $sut->jsonSerialize());
        $this->assertArrayHasKey('trustMarkId2', $sut->jsonSerialize());
    }
}
