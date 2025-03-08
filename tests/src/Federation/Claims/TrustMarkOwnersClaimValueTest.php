<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\Claims;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Claims\JwksClaim;
use SimpleSAML\OpenID\Federation\Claims\TrustMarkOwnersClaimValue;

#[CoversClass(TrustMarkOwnersClaimValue::class)]
class TrustMarkOwnersClaimValueTest extends TestCase
{
    protected string $trustMarkId;
    protected string $subject = 'subject';
    protected MockObject $jwksClaimMock;
    protected array $otherClaims;

    protected function setUp(): void
    {
        $this->trustMarkId = 'trustMarkId';
        $this->subject = 'subject';
        $this->jwksClaimMock = $this->createMock(JwksClaim::class);
        $this->otherClaims = ['key' => 'value'];
    }

    protected function sut(
        ?string $trustMarkId = null,
        ?string $subject = null,
        ?JwksClaim $jwksClaim = null,
        ?array $otherClaims = null,
    ): TrustMarkOwnersClaimValue {
        $trustMarkId ??= $this->trustMarkId;
        $subject ??= $this->subject;
        $jwksClaim ??= $this->jwksClaimMock;
        $otherClaims ??= $this->otherClaims;

        return new TrustMarkOwnersClaimValue(
            $trustMarkId,
            $subject,
            $jwksClaim,
            $otherClaims,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustMarkOwnersClaimValue::class, $this->sut());
    }

    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertSame($this->trustMarkId, $sut->getTrustMarkId());
        $this->assertSame($this->subject, $sut->getSubject());
        $this->assertSame($this->jwksClaimMock, $sut->getJwks());
        $this->assertSame($this->otherClaims, $sut->getOtherClaims());
    }

    public function testCanJsonSerialize(): void
    {
        $this->assertSame(
            [
                'trust_mark_id' => $this->trustMarkId,
                'sub' => $this->subject,
                'jwks' => [],
                'key' => 'value',
            ],
            $this->sut()->jsonSerialize(),
        );
    }
}
