<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\EntityStatement;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Exceptions\TrustMarkClaimException;
use SimpleSAML\OpenID\Federation\EntityStatement\TrustMarkClaim;
use SimpleSAML\OpenID\Federation\TrustMark;

#[CoversClass(TrustMarkClaim::class)]
class TrustMarkClaimTest extends TestCase
{
    protected string $id;
    protected MockObject $trustMarkMock;
    protected array $otherClaims = [];

    protected function setUp(): void
    {
        $this->id = 'id';
        $this->trustMarkMock = $this->createMock(TrustMark::class);
        $this->trustMarkMock->method('getIdentifier')->willReturn($this->id);
        $this->otherClaims = ['something' => 'else'];
    }

    protected function sut(
        ?string $id = null,
        ?TrustMark $trustMark = null,
        ?array $otherClaims = null,
    ): TrustMarkClaim {
        $id ??= $this->id;
        $trustMark ??= $this->trustMarkMock;
        $otherClaims ??= $this->otherClaims;

        return new TrustMarkClaim($id, $trustMark, $otherClaims);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustMarkClaim::class, $this->sut());
    }

    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertSame($this->id, $sut->getId());
        $this->assertSame($this->trustMarkMock, $sut->getTrustMark());
        $this->assertSame($this->otherClaims, $sut->getOtherClaims());
    }

    public function testConstructThrowsForDifferentIds(): void
    {
        $this->expectException(TrustMarkClaimException::class);
        $this->expectExceptionMessage('identifier');

        $this->sut('differentId');
    }

    public function testConstructThrowsForDifferentTrustMarkPayload(): void
    {
        $this->trustMarkMock->method('getPayload')
            ->willReturn(['something' => 'different']);

        $this->expectException(TrustMarkClaimException::class);
        $this->expectExceptionMessage('different');

        $this->sut();
    }
}
