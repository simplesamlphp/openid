<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\TokenStatusList;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\StatusTypeEnum;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\TokenStatusList\StatusListToken;
use SimpleSAML\OpenID\TokenStatusList\StatusReference;
use SimpleSAML\OpenID\TokenStatusList\StatusResult;

#[CoversClass(StatusResult::class)]
#[UsesClass(StatusReference::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Type::class)]
final class StatusResultTest extends TestCase
{
    protected MockObject $statusListTokenMock;

    protected StatusReference $statusReference;

    protected DateTimeImmutable $resolvedAt;


    protected function setUp(): void
    {
        $this->statusListTokenMock = $this->createMock(StatusListToken::class);
        $this->statusReference = new StatusReference('https://example.com/statuslists/1', 0, new Helpers());
        $this->resolvedAt = new DateTimeImmutable('@1686920170');
    }


    protected function sut(int $status = 0): StatusResult
    {
        return new StatusResult(
            $status,
            $this->statusReference,
            $this->statusListTokenMock,
            $this->resolvedAt,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(StatusResult::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut(1);

        $this->assertSame(1, $sut->getStatus());
        $this->assertSame(StatusTypeEnum::Invalid, $sut->getStatusType());
        $this->assertSame($this->statusReference, $sut->getStatusReference());
        $this->assertSame($this->statusListTokenMock, $sut->getStatusListToken());
        $this->assertSame($this->resolvedAt, $sut->getResolvedAt());
    }


    public function testIsValidOnlyForTheValidStatus(): void
    {
        $this->assertTrue($this->sut(0)->isValid());
        $this->assertFalse($this->sut(1)->isValid());
        $this->assertFalse($this->sut(2)->isValid());
        // An application specific value is not a valid Referenced Token either.
        $this->assertFalse($this->sut(3)->isValid());
    }


    public function testStatusTypeIsNullForUnregisteredValue(): void
    {
        $this->assertNotInstanceOf(\SimpleSAML\OpenID\Codebooks\StatusTypeEnum::class, $this->sut(3)->getStatusType());
        $this->assertSame(3, $this->sut(3)->getStatus());
    }


    public function testIsFreshWithinTheTimeToLive(): void
    {
        $this->statusListTokenMock->method('getTimeToLive')->willReturn(600);
        $this->statusListTokenMock->method('getPayloadClaim')->willReturn(null);

        $sut = $this->sut();

        $this->assertTrue($sut->isFreshAt($this->resolvedAt));
        $this->assertTrue($sut->isFreshAt($this->resolvedAt->modify('+599 seconds')));
        $this->assertTrue($sut->isFreshAt($this->resolvedAt->modify('+600 seconds')));
        $this->assertFalse($sut->isFreshAt($this->resolvedAt->modify('+601 seconds')));
    }


    /**
     * The specification allows a fractional ttl, so the window it opens is measured with the subsecond part
     * intact. Truncating both sides to whole seconds would leave a result looking fresh for most of a second
     * after the issuer's window had closed.
     */
    public function testMeasuresAFractionalTimeToLiveBelowTheSecond(): void
    {
        $this->statusListTokenMock->method('getTimeToLive')->willReturn(0.5);
        $this->statusListTokenMock->method('getPayloadClaim')->willReturn(null);

        $resolvedAt = new DateTimeImmutable('@1686920170.100000');

        $sut = new StatusResult(0, $this->statusReference, $this->statusListTokenMock, $resolvedAt);

        // 0.4s in, so still inside the half second the issuer allowed.
        $this->assertTrue($sut->isFreshAt(new DateTimeImmutable('@1686920170.500000')));
        // 0.8s in, and past it -- even though both instants truncate to the same whole second.
        $this->assertFalse($sut->isFreshAt(new DateTimeImmutable('@1686920170.900000')));
    }


    /**
     * A token carrying neither claim states no limit, so the caller's own policy is all there is.
     */
    public function testIsFreshWhenTokenStatesNeitherLimit(): void
    {
        $this->statusListTokenMock->method('getTimeToLive')->willReturn(null);
        $this->statusListTokenMock->method('getPayloadClaim')->willReturn(null);

        $this->assertTrue($this->sut()->isFreshAt($this->resolvedAt->modify('+10 years')));
    }


    /**
     * Past its expiration the Status List Token states nothing at all, so a result taken from it is not fresh
     * however much of its ttl is left.
     */
    public function testIsNotFreshPastTheTokenExpiration(): void
    {
        $this->statusListTokenMock->method('getTimeToLive')->willReturn(86400);
        $this->statusListTokenMock->method('getPayloadClaim')
            ->willReturn($this->resolvedAt->getTimestamp() + 600);

        $sut = $this->sut();

        $this->assertTrue($sut->isFreshAt($this->resolvedAt->modify('+599 seconds')));
        $this->assertFalse($sut->isFreshAt($this->resolvedAt->modify('+601 seconds')));
    }


    /**
     * RFC 7519 asks for the current time to be before the expiration time, so the expiration instant itself is
     * already past it -- unlike the ttl window, whose final instant is still within what the issuer allowed.
     */
    public function testTheExpirationInstantItselfIsNotFreshUnlikeTheTimeToLiveDeadline(): void
    {
        $this->statusListTokenMock->method('getTimeToLive')->willReturn(600);
        $this->statusListTokenMock->method('getPayloadClaim')
            ->willReturn($this->resolvedAt->getTimestamp() + 600);

        $sut = $this->sut();

        $this->assertTrue($sut->isFreshAt($this->resolvedAt->modify('+599 seconds')));
        // The ttl window has one second left at this point, so expiration is the only thing ending it.
        $this->assertFalse($sut->isFreshAt($this->resolvedAt->modify('+600 seconds')));
    }


    /**
     * Expiration alone bounds a token that states no ttl.
     */
    public function testExpirationBoundsFreshnessWithoutATimeToLive(): void
    {
        $this->statusListTokenMock->method('getTimeToLive')->willReturn(null);
        $this->statusListTokenMock->method('getPayloadClaim')
            ->willReturn($this->resolvedAt->getTimestamp() + 300);

        $sut = $this->sut();

        $this->assertTrue($sut->isFreshAt($this->resolvedAt->modify('+299 seconds')));
        $this->assertFalse($sut->isFreshAt($this->resolvedAt->modify('+300 seconds')));
    }


    /**
     * Whichever of the two limits falls first is the one that applies.
     */
    public function testTimeToLiveBoundsFreshnessBeforeExpiration(): void
    {
        $this->statusListTokenMock->method('getTimeToLive')->willReturn(60);
        $this->statusListTokenMock->method('getPayloadClaim')
            ->willReturn($this->resolvedAt->getTimestamp() + 86400);

        $sut = $this->sut();

        $this->assertTrue($sut->isFreshAt($this->resolvedAt->modify('+60 seconds')));
        $this->assertFalse($sut->isFreshAt($this->resolvedAt->modify('+61 seconds')));
    }


    /**
     * Asking whether an expired result is fresh answers the question rather than throwing.
     */
    public function testIsFreshAtDoesNotThrowForAnExpiredToken(): void
    {
        $this->statusListTokenMock->method('getTimeToLive')->willReturn(600);
        $this->statusListTokenMock->method('getPayloadClaim')
            ->willReturn($this->resolvedAt->getTimestamp() - 1);

        $this->assertFalse($this->sut()->isFreshAt($this->resolvedAt));
    }
}
