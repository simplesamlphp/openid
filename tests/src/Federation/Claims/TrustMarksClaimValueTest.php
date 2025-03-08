<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\Claims;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimValue;

#[CoversClass(TrustMarksClaimValue::class)]
class TrustMarksClaimValueTest extends TestCase
{
    protected string $trustMarkId;
    protected string $trustMark;
    protected array $otherClaims = [];

    protected function setUp(): void
    {
        $this->trustMarkId = 'trustMarkId';
        $this->trustMark = 'token';
        $this->otherClaims = ['something' => 'else'];
    }

    protected function sut(
        ?string $trustMarkId = null,
        ?string $trustMark = null,
        ?array $otherClaims = null,
    ): TrustMarksClaimValue {
        $trustMarkId ??= $this->trustMarkId;
        $trustMark ??= $this->trustMark;
        $otherClaims ??= $this->otherClaims;

        return new TrustMarksClaimValue($trustMarkId, $trustMark, $otherClaims);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustMarksClaimValue::class, $this->sut());
    }

    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertSame($this->trustMarkId, $sut->getTrustMarkId());
        $this->assertSame($this->trustMark, $sut->getTrustMark());
        $this->assertSame($this->otherClaims, $sut->getOtherClaims());
    }

    public function testCanJsonSerialize(): void
    {
        $this->assertSame(
            ['trust_mark_id' => $this->trustMarkId, 'trust_mark' => $this->trustMark, 'something' => 'else'],
            $this->sut()->jsonSerialize(),
        );
    }
}
