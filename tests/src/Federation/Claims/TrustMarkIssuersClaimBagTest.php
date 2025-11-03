<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\Claims;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Federation\Claims\TrustMarkIssuersClaimBag;
use SimpleSAML\OpenID\Federation\Claims\TrustMarkIssuersClaimValue;

#[CoversClass(TrustMarkIssuersClaimBag::class)]
final class TrustMarkIssuersClaimBagTest extends TestCase
{
    protected MockObject $trustMarkIssuerClaimValueMock;


    protected function setUp(): void
    {
        $this->trustMarkIssuerClaimValueMock = $this->createMock(TrustMarkIssuersClaimValue::class);
        $this->trustMarkIssuerClaimValueMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkIssuerClaimValueMock->method('getTrustMarkIssuers')
            ->willReturn(['https://issuer1.org', 'https://issuer2.org']);
    }


    protected function sut(
        TrustMarkIssuersClaimValue ...$trustMarkIssuerClaimValues,
    ): TrustMarkIssuersClaimBag {
        return new TrustMarkIssuersClaimBag(...$trustMarkIssuerClaimValues);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustMarkIssuersClaimBag::class, $this->sut());
    }


    public function testCanAddAndGet(): void
    {
        $this->assertEmpty($this->sut()->getAll());
        $sut = $this->sut($this->trustMarkIssuerClaimValueMock);
        $this->assertCount(1, $sut->getAll());
        $this->assertTrue($sut->has('trustMarkType'));
        $this->assertSame($this->trustMarkIssuerClaimValueMock, $sut->get('trustMarkType'));

        $trustMarkIssuerClaimValueMock2 = $this->createMock(TrustMarkIssuersClaimValue::class);
        $trustMarkIssuerClaimValueMock2->method('getTrustMarkType')->willReturn('trustMarkType2');
        $sut->add($trustMarkIssuerClaimValueMock2);
        $this->assertCount(2, $sut->getAll());
        $this->assertTrue($sut->has('trustMarkType2'));
        $this->assertSame($trustMarkIssuerClaimValueMock2, $sut->get('trustMarkType2'));
    }


    public function testCanJsonSerialize(): void
    {
        $trustMarkIssuerClaimValueMock2 = $this->createMock(TrustMarkIssuersClaimValue::class);
        $trustMarkIssuerClaimValueMock2->method('getTrustMarkType')->willReturn('trustMarkType2');
        $this->trustMarkIssuerClaimValueMock->method('getTrustMarkIssuers')->willReturn([]);

        $sut = $this->sut($this->trustMarkIssuerClaimValueMock, $trustMarkIssuerClaimValueMock2);
        $this->assertSame(
            [
                'trustMarkType' => ['https://issuer1.org', 'https://issuer2.org'],
                'trustMarkType2' => [],
            ],
            $sut->jsonSerialize(),
        );
    }
}
