<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\TokenStatusList;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\StatusTypeEnum;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Exceptions\StatusListException;
use SimpleSAML\OpenID\Exceptions\StatusListTokenException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\TokenStatusList\Factories\StatusListFactory;
use SimpleSAML\OpenID\TokenStatusList\StatusList;
use SimpleSAML\OpenID\TokenStatusList\StatusListToken;
use SimpleSAML\OpenID\TokenStatusList\StatusListTokenFetcher;
use SimpleSAML\OpenID\TokenStatusList\StatusReference;
use SimpleSAML\OpenID\TokenStatusList\StatusResolver;
use SimpleSAML\OpenID\TokenStatusList\StatusResult;

#[CoversClass(StatusResolver::class)]
#[UsesClass(StatusResult::class)]
#[UsesClass(StatusReference::class)]
#[UsesClass(StatusList::class)]
#[UsesClass(StatusListFactory::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Base64Url::class)]
#[UsesClass(Helpers\Type::class)]
final class StatusResolverTest extends TestCase
{
    protected const URI = 'https://example.com/statuslists/1';


    /** @var mixed[] */
    protected array $jwks = ['keys' => [['kty' => 'EC', 'crv' => 'P-256', 'x' => 'x', 'y' => 'y']]];

    protected MockObject $statusListTokenFetcherMock;

    protected MockObject $statusListTokenMock;

    protected StatusReference $statusReference;

    protected StatusList $statusList;


    protected function setUp(): void
    {
        $helpers = new Helpers();

        $this->statusListTokenFetcherMock = $this->createMock(StatusListTokenFetcher::class);

        $this->statusList = (new StatusListFactory($helpers))->fromEntries(
            [
                1 => StatusTypeEnum::Invalid,
                2 => StatusTypeEnum::Suspended,
                3 => 0b11,
            ],
            2,
            1024,
        );

        $this->statusListTokenMock = $this->createMock(StatusListToken::class);
        $this->statusListTokenMock->method('getSubject')->willReturn(self::URI);
        $this->statusListTokenMock->method('getStatusList')->willReturn($this->statusList);

        $this->statusReference = new StatusReference(self::URI, 1, $helpers);
    }


    protected function sut(): StatusResolver
    {
        return new StatusResolver(
            $this->statusListTokenFetcherMock,
            $this->createStub(\Psr\Log\LoggerInterface::class),
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(StatusResolver::class, $this->sut());
    }


    public function testCanResolveWithToken(): void
    {
        $statusResult = $this->sut()->resolveWithToken(
            $this->statusReference,
            $this->statusListTokenMock,
            $this->jwks,
            256,
        );

        $this->assertSame(StatusTypeEnum::Invalid, $statusResult->getStatusType());
        $this->assertSame(1, $statusResult->getStatus());
        $this->assertFalse($statusResult->isValid());
        $this->assertSame($this->statusReference, $statusResult->getStatusReference());
        $this->assertSame($this->statusListTokenMock, $statusResult->getStatusListToken());
    }


    /**
     * An application specific value is reported as the number it is, rather than being flattened into a Status
     * Type the specification does not define for it.
     */
    public function testResolvesApplicationSpecificStatus(): void
    {
        $statusResult = $this->sut()->resolveWithToken(
            new StatusReference(self::URI, 3, new Helpers()),
            $this->statusListTokenMock,
            $this->jwks,
            256,
        );

        $this->assertSame(0b11, $statusResult->getStatus());
        $this->assertNotInstanceOf(\SimpleSAML\OpenID\Codebooks\StatusTypeEnum::class, $statusResult->getStatusType());
        $this->assertFalse($statusResult->isValid());
    }


    public function testCanResolveByFetchingTheToken(): void
    {
        $this->statusListTokenFetcherMock->method('fromCache')->willReturn(null);
        $this->statusListTokenFetcherMock->expects($this->once())
            ->method('fromNetworkWithoutCaching')
            ->with(self::URI, null)
            ->willReturn($this->statusListTokenMock);

        $statusResult = $this->sut()->resolve($this->statusReference, $this->jwks, 256);

        $this->assertSame(StatusTypeEnum::Invalid, $statusResult->getStatusType());
    }


    public function testResolvesFromTheCacheWithoutTouchingTheNetwork(): void
    {
        $this->statusListTokenFetcherMock->method('fromCache')->willReturn($this->statusListTokenMock);
        $this->statusListTokenFetcherMock->expects($this->never())->method('fromNetworkWithoutCaching');

        $statusResult = $this->sut()->resolve($this->statusReference, $this->jwks, 256);

        $this->assertSame(StatusTypeEnum::Invalid, $statusResult->getStatusType());
    }


    /**
     * A token is cached only once it has been verified and bound to the URI it was fetched from, so one bad
     * response can not keep being served back for the whole cache period.
     */
    public function testCachesTheTokenOnlyAfterItHasBeenVerified(): void
    {
        $this->statusListTokenFetcherMock->method('fromCache')->willReturn(null);
        $this->statusListTokenFetcherMock->method('fromNetworkWithoutCaching')
            ->willReturn($this->statusListTokenMock);

        $this->statusListTokenFetcherMock->expects($this->once())
            ->method('cacheIt')
            ->with($this->statusListTokenMock, self::URI);

        $this->sut()->resolve($this->statusReference, $this->jwks, 256);
    }


    public function testDoesNotCacheATokenThatFailsVerification(): void
    {
        $this->statusListTokenFetcherMock->method('fromCache')->willReturn(null);
        $this->statusListTokenFetcherMock->method('fromNetworkWithoutCaching')
            ->willReturn($this->statusListTokenMock);
        $this->statusListTokenMock->method('verifyWithKeySet')
            ->willThrowException(new JwsException('Could not verify JWS signature.'));

        $this->statusListTokenFetcherMock->expects($this->never())->method('cacheIt');

        $this->expectException(JwsException::class);

        $this->sut()->resolve($this->statusReference, $this->jwks, 256);
    }


    /**
     * A token whose subject does not match is not cached either, even though it verified.
     */
    public function testDoesNotCacheATokenWithAMismatchedSubject(): void
    {
        $statusListTokenMock = $this->createMock(StatusListToken::class);
        $statusListTokenMock->method('getSubject')->willReturn('https://example.com/statuslists/2');

        $this->statusListTokenFetcherMock->method('fromCache')->willReturn(null);
        $this->statusListTokenFetcherMock->method('fromNetworkWithoutCaching')->willReturn($statusListTokenMock);

        $this->statusListTokenFetcherMock->expects($this->never())->method('cacheIt');

        $this->expectException(StatusListTokenException::class);

        $this->sut()->resolve($this->statusReference, $this->jwks, 256);
    }


    /**
     * A token that came from the cache is not written straight back to it.
     */
    public function testDoesNotRecacheATokenTakenFromTheCache(): void
    {
        $this->statusListTokenFetcherMock->method('fromCache')->willReturn($this->statusListTokenMock);
        $this->statusListTokenFetcherMock->expects($this->never())->method('cacheIt');

        $this->sut()->resolve($this->statusReference, $this->jwks, 256);
    }


    /**
     * A cached token that no longer resolves -- the Status Provider having rotated its keys since it was stored,
     * say -- is worth no more than an empty cache, so it is treated as a miss rather than being the reason every
     * Referenced Token pointing at this URI stays unresolvable until the entry expires.
     */
    public function testRefetchesWhenTheCachedTokenNoLongerResolves(): void
    {
        $staleTokenMock = $this->createMock(StatusListToken::class);
        $staleTokenMock->method('verifyWithKeySet')
            ->willThrowException(new JwsException('Could not verify JWS signature.'));

        $this->statusListTokenFetcherMock->method('fromCache')->willReturn($staleTokenMock);
        $this->statusListTokenFetcherMock->expects($this->once())
            ->method('fromNetworkWithoutCaching')
            ->with(self::URI, null)
            ->willReturn($this->statusListTokenMock);

        $statusResult = $this->sut()->resolve($this->statusReference, $this->jwks, 256);

        $this->assertSame(StatusTypeEnum::Invalid, $statusResult->getStatusType());
        $this->assertSame($this->statusListTokenMock, $statusResult->getStatusListToken());
    }


    /**
     * Including when the cached entry cannot be parsed at all -- whatever sits under this URI failing to read is
     * no more of an answer about the Referenced Token than a stale token is.
     */
    public function testRefetchesWhenTheCachedEntryCannotBeParsed(): void
    {
        $this->statusListTokenFetcherMock->method('fromCache')
            ->willThrowException(new JwsException('Unable to parse token.'));
        $this->statusListTokenFetcherMock->expects($this->once())
            ->method('fromNetworkWithoutCaching')
            ->willReturn($this->statusListTokenMock);

        $statusResult = $this->sut()->resolve($this->statusReference, $this->jwks, 256);

        $this->assertSame(StatusTypeEnum::Invalid, $statusResult->getStatusType());
    }


    /**
     * The replacement is cached like any other verified token, which is also what displaces the unusable entry.
     */
    public function testCachesTheTokenFetchedToReplaceAnUnusableCachedOne(): void
    {
        $staleTokenMock = $this->createMock(StatusListToken::class);
        $staleTokenMock->method('getSubject')->willReturn('https://example.com/statuslists/2');

        $this->statusListTokenFetcherMock->method('fromCache')->willReturn($staleTokenMock);
        $this->statusListTokenFetcherMock->method('fromNetworkWithoutCaching')
            ->willReturn($this->statusListTokenMock);

        $this->statusListTokenFetcherMock->expects($this->once())
            ->method('cacheIt')
            ->with($this->statusListTokenMock, self::URI);

        $this->sut()->resolve($this->statusReference, $this->jwks, 256);
    }


    /**
     * One retry, not a loop: when the current representation fails the same way, that failure is the answer.
     */
    public function testFetchesOnlyOnceWhenTheFreshTokenFailsAsWell(): void
    {
        $staleTokenMock = $this->createMock(StatusListToken::class);
        $staleTokenMock->method('verifyWithKeySet')
            ->willThrowException(new JwsException('Could not verify JWS signature.'));

        $freshTokenMock = $this->createMock(StatusListToken::class);
        $freshTokenMock->method('verifyWithKeySet')
            ->willThrowException(new JwsException('Could not verify JWS signature.'));

        $this->statusListTokenFetcherMock->method('fromCache')->willReturn($staleTokenMock);
        $this->statusListTokenFetcherMock->expects($this->once())
            ->method('fromNetworkWithoutCaching')
            ->willReturn($freshTokenMock);
        $this->statusListTokenFetcherMock->expects($this->never())->method('cacheIt');

        $this->expectException(JwsException::class);

        $this->sut()->resolve($this->statusReference, $this->jwks, 256);
    }


    public function testPassesTheDeadlineToTheFetcher(): void
    {
        $deadline = microtime(true) + 10;

        $this->statusListTokenFetcherMock->method('fromCache')->willReturn(null);
        $this->statusListTokenFetcherMock->expects($this->once())
            ->method('fromNetworkWithoutCaching')
            ->with(self::URI, $deadline)
            ->willReturn($this->statusListTokenMock);

        $this->sut()->resolve($this->statusReference, $this->jwks, 256, null, $deadline);
    }


    /**
     * The signature is checked before anything is read out of the token.
     */
    public function testVerifiesTheSignature(): void
    {
        $this->statusListTokenMock->expects($this->once())
            ->method('verifyWithKeySet')
            ->with($this->jwks);

        $this->sut()->resolveWithToken($this->statusReference, $this->statusListTokenMock, $this->jwks, 256);
    }


    public function testThrowsWhenSignatureDoesNotVerify(): void
    {
        $this->statusListTokenMock->method('verifyWithKeySet')
            ->willThrowException(new JwsException('Could not verify JWS signature.'));

        $this->expectException(JwsException::class);

        $this->sut()->resolveWithToken($this->statusReference, $this->statusListTokenMock, $this->jwks, 256);
    }


    /**
     * A token handed to resolveWithToken() may have been parsed long ago, so its expiration is checked again
     * here rather than being trusted from construction time.
     */
    public function testRechecksExpirationAtResolutionTime(): void
    {
        $this->statusListTokenMock->expects($this->once())->method('getExpirationTime');

        $this->sut()->resolveWithToken($this->statusReference, $this->statusListTokenMock, $this->jwks, 256);
    }


    /**
     * An expired Status List must not be what decides a Referenced Token's status.
     */
    public function testThrowsForATokenThatHasExpiredSinceItWasParsed(): void
    {
        $this->statusListTokenMock->method('getExpirationTime')
            ->willThrowException(new JwsException('Expiration Time claim (1) is lesser than current time.'));

        $this->expectException(JwsException::class);

        $this->sut()->resolveWithToken($this->statusReference, $this->statusListTokenMock, $this->jwks, 256);
    }


    /**
     * A token whose subject is not the URI the Referenced Token pointed at says nothing about that token.
     */
    public function testThrowsWhenSubjectDoesNotMatchTheReferencedUri(): void
    {
        $statusListTokenMock = $this->createMock(StatusListToken::class);
        $statusListTokenMock->method('getSubject')->willReturn('https://example.com/statuslists/2');

        $this->expectException(StatusListTokenException::class);
        $this->expectExceptionMessage('subject does not match');

        $this->sut()->resolveWithToken($this->statusReference, $statusListTokenMock, $this->jwks, 256);
    }


    /**
     * Not even a trailing slash, since the two strings have to be equal.
     */
    public function testThrowsWhenSubjectDiffersOnlyByATrailingSlash(): void
    {
        $statusListTokenMock = $this->createMock(StatusListToken::class);
        $statusListTokenMock->method('getSubject')->willReturn(self::URI . '/');

        $this->expectException(StatusListTokenException::class);

        $this->sut()->resolveWithToken($this->statusReference, $statusListTokenMock, $this->jwks, 256);
    }


    public function testThrowsForIndexOutOfBoundsOfTheList(): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('out of bounds');

        $this->sut()->resolveWithToken(
            new StatusReference(self::URI, 1024, new Helpers()),
            $this->statusListTokenMock,
            $this->jwks,
            256,
        );
    }


    public function testUsesTheGivenResolutionTime(): void
    {
        $resolvedAt = new DateTimeImmutable('@1686920170');

        $statusResult = $this->sut()->resolveWithToken(
            $this->statusReference,
            $this->statusListTokenMock,
            $this->jwks,
            256,
            null,
            $resolvedAt,
        );

        $this->assertSame($resolvedAt, $statusResult->getResolvedAt());
    }


    public function testDefaultsTheResolutionTimeToNow(): void
    {
        $statusResult = $this->sut()->resolveWithToken(
            $this->statusReference,
            $this->statusListTokenMock,
            $this->jwks,
            256,
        );

        $this->assertEqualsWithDelta(time(), $statusResult->getResolvedAt()->getTimestamp(), 5);
    }


    /**
     * The size bound the caller sets is handed on to the decoder rather than being decided here.
     */
    public function testPassesTheSizeBoundsToTheToken(): void
    {
        $this->statusListTokenMock->expects($this->once())
            ->method('getStatusList')
            ->with(256, 512)
            ->willReturn($this->statusList);

        $this->sut()->resolveWithToken($this->statusReference, $this->statusListTokenMock, $this->jwks, 256, 512);
    }
}
