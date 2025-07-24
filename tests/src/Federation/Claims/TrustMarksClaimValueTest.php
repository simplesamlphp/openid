<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\Claims;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimValue;

#[CoversClass(TrustMarksClaimValue::class)]
final class TrustMarksClaimValueTest extends TestCase
{
    protected string $trustMarkType;

    protected string $trustMark;

    protected array $otherClaims = [];

    protected function setUp(): void
    {
        $this->trustMarkType = 'trustMarkType';
        $this->trustMark = 'token';
        $this->otherClaims = ['something' => 'else'];
    }

    protected function sut(
        ?string $trustMarkType = null,
        ?string $trustMark = null,
        ?array $otherClaims = null,
    ): TrustMarksClaimValue {
        $trustMarkType ??= $this->trustMarkType;
        $trustMark ??= $this->trustMark;
        $otherClaims ??= $this->otherClaims;

        return new TrustMarksClaimValue($trustMarkType, $trustMark, $otherClaims);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustMarksClaimValue::class, $this->sut());
    }

    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertSame($this->trustMarkType, $sut->getTrustMarkType());
        $this->assertSame($this->trustMark, $sut->getTrustMark());
        $this->assertSame($this->otherClaims, $sut->getOtherClaims());
    }

    public function testCanJsonSerialize(): void
    {
        $this->assertSame(
            ['trust_mark_type' => $this->trustMarkType, 'trust_mark' => $this->trustMark, 'something' => 'else'],
            $this->sut()->jsonSerialize(),
        );
    }
}
