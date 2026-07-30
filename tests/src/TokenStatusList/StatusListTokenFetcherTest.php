<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\TokenStatusList;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use SimpleSAML\OpenID\Codebooks\ContentTypesEnum;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Jws\AbstractJwsFetcher;
use SimpleSAML\OpenID\Jws\JwsFetcher;
use SimpleSAML\OpenID\TokenStatusList\Factories\StatusListTokenFactory;
use SimpleSAML\OpenID\TokenStatusList\StatusListToken;
use SimpleSAML\OpenID\TokenStatusList\StatusListTokenFetcher;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

#[CoversClass(StatusListTokenFetcher::class)]
#[UsesClass(AbstractJwsFetcher::class)]
#[UsesClass(JwsFetcher::class)]
final class StatusListTokenFetcherTest extends TestCase
{
    protected const URI = 'https://example.com/statuslists/1';


    protected MockObject $statusListTokenFactoryMock;

    protected MockObject $artifactFetcherMock;

    protected MockObject $maxCacheDurationMock;

    protected MockObject $statusListTokenMock;

    protected MockObject $responseMock;


    protected function setUp(): void
    {
        $this->statusListTokenFactoryMock = $this->createMock(StatusListTokenFactory::class);
        $this->artifactFetcherMock = $this->createMock(ArtifactFetcher::class);
        $this->maxCacheDurationMock = $this->createMock(DateIntervalDecorator::class);

        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->responseMock->method('getStatusCode')->willReturn(200);
        $this->responseMock->method('getHeaderLine')
            ->willReturn(ContentTypesEnum::ApplicationStatusListJwt->value);
        $this->artifactFetcherMock->method('fromNetwork')->willReturn($this->responseMock);
        $this->artifactFetcherMock->method('readResponseBodyAsString')->willReturn('token');

        $this->statusListTokenMock = $this->createMock(StatusListToken::class);
        $this->statusListTokenMock->method('getToken')->willReturn('token');
        $this->statusListTokenFactoryMock->method('fromToken')->willReturn($this->statusListTokenMock);
    }


    protected function sut(): StatusListTokenFetcher
    {
        return new StatusListTokenFetcher(
            $this->statusListTokenFactoryMock,
            $this->artifactFetcherMock,
            $this->maxCacheDurationMock,
            $this->createStub(\SimpleSAML\OpenID\Helpers::class),
            $this->createStub(\Psr\Log\LoggerInterface::class),
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(StatusListTokenFetcher::class, $this->sut());
    }


    /**
     * The specification defines a media type for the response, so a response of some other type is not a Status
     * List Token.
     */
    public function testHasRightExpectedContentTypeHttpHeader(): void
    {
        $this->assertSame(
            ContentTypesEnum::ApplicationStatusListJwt->value,
            $this->sut()->getExpectedContentTypeHttpHeader(),
        );
    }


    public function testCanFetchFromCache(): void
    {
        $this->artifactFetcherMock->expects($this->once())
            ->method('fromCacheAsString')
            ->willReturn('token');

        $this->assertSame($this->statusListTokenMock, $this->sut()->fromCache(self::URI));
    }


    public function testReturnsNullWhenNotCached(): void
    {
        $this->artifactFetcherMock->method('fromCacheAsString')->willReturn(null);

        $this->assertNotInstanceOf(StatusListToken::class, $this->sut()->fromCache(self::URI));
    }


    public function testFallsBackToNetworkWhenNotCached(): void
    {
        $this->artifactFetcherMock->method('fromCacheAsString')->willReturn(null);
        $this->artifactFetcherMock->expects($this->once())->method('fromNetwork');

        $this->assertSame($this->statusListTokenMock, $this->sut()->fromCacheOrNetwork(self::URI));
    }


    /**
     * The specification expects the JWT and CWT representations to be chosen between by content negotiation, and
     * this fetcher only understands the JWT one, so a provider defaulting to CWT has to be told.
     */
    public function testAsksForTheJwtRepresentation(): void
    {
        $this->artifactFetcherMock->method('fromCacheAsString')->willReturn(null);
        $this->artifactFetcherMock->expects($this->once())
            ->method('fromNetwork')
            ->with(
                self::URI,
                HttpMethodsEnum::GET,
                ['headers' => ['Accept' => ContentTypesEnum::ApplicationStatusListJwt->value]],
            )
            ->willReturn($this->responseMock);

        $this->sut()->fromCacheOrNetwork(self::URI);
    }


    /**
     * Including on a direct network fetch, which a caller forcing a refresh reaches for.
     */
    public function testAsksForTheJwtRepresentationOnADirectNetworkFetch(): void
    {
        $this->artifactFetcherMock->expects($this->once())
            ->method('fromNetwork')
            ->with(
                self::URI,
                HttpMethodsEnum::GET,
                ['headers' => ['Accept' => ContentTypesEnum::ApplicationStatusListJwt->value]],
            )
            ->willReturn($this->responseMock);

        $this->sut()->fromNetwork(self::URI);
    }


    /**
     * A caller that has its own Accept header in mind keeps it.
     */
    public function testDoesNotOverrideACallerSuppliedAcceptHeader(): void
    {
        $this->artifactFetcherMock->expects($this->once())
            ->method('fromNetwork')
            ->with(self::URI, HttpMethodsEnum::GET, ['headers' => ['Accept' => 'application/jwt']])
            ->willReturn($this->responseMock);

        $this->sut()->fromNetwork(self::URI, HttpMethodsEnum::GET, ['headers' => ['Accept' => 'application/jwt']]);
    }


    /**
     * Header names are case-insensitive, so a caller's lowercase `accept` is not a different header that ours
     * can be added alongside.
     */
    public function testDoesNotAddASecondAcceptHeaderInAnotherCase(): void
    {
        $this->artifactFetcherMock->expects($this->once())
            ->method('fromNetwork')
            ->with(self::URI, HttpMethodsEnum::GET, ['headers' => ['accept' => 'application/jwt']])
            ->willReturn($this->responseMock);

        $this->sut()->fromNetwork(self::URI, HttpMethodsEnum::GET, ['headers' => ['accept' => 'application/jwt']]);
    }


    /**
     * Nothing about a fetched token is trustworthy until its signature and subject have been checked, and this
     * fetcher holds no key material to check them with, so it offers a path that does not cache.
     */
    public function testCanFetchFromNetworkWithoutCaching(): void
    {
        $this->artifactFetcherMock->expects($this->never())->method('cacheIt');

        $this->assertSame($this->statusListTokenMock, $this->sut()->fromNetworkWithoutCaching(self::URI));
    }


    public function testCanCacheAVerifiedToken(): void
    {
        $this->statusListTokenMock->method('getExpirationTime')->willReturn(null);
        $this->statusListTokenMock->method('getTimeToLive')->willReturn(600);
        $this->maxCacheDurationMock->method('getInSeconds')->willReturn(21600);

        $this->artifactFetcherMock->expects($this->once())
            ->method('cacheIt')
            ->with('token', 600, self::URI);

        $this->sut()->cacheIt($this->statusListTokenMock, self::URI);
    }


    /**
     * The ttl claim bounds how long a copy may be held, so it caps the cache duration alongside the expiration
     * time and the configured maximum.
     */
    public function testCachesForTheShortestOfTtlAndExpirationTime(): void
    {
        $this->statusListTokenMock->method('getExpirationTime')->willReturn(time() + 3600);
        $this->statusListTokenMock->method('getTimeToLive')->willReturn(600);
        $this->maxCacheDurationMock->method('lowestInSecondsComparedToExpirationTime')->willReturn(3600);

        $this->artifactFetcherMock->expects($this->once())
            ->method('cacheIt')
            ->with('token', 600, self::URI);

        $this->sut()->fromNetwork(self::URI);
    }


    public function testCachesForTheExpirationTimeWhenItIsShorterThanTtl(): void
    {
        $this->statusListTokenMock->method('getExpirationTime')->willReturn(time() + 300);
        $this->statusListTokenMock->method('getTimeToLive')->willReturn(43200);
        $this->maxCacheDurationMock->method('lowestInSecondsComparedToExpirationTime')->willReturn(300);

        $this->artifactFetcherMock->expects($this->once())
            ->method('cacheIt')
            ->with('token', 300, self::URI);

        $this->sut()->fromNetwork(self::URI);
    }


    /**
     * Without either claim the configured maximum is all there is to go on.
     */
    public function testCachesForMaxCacheDurationWhenTokenStatesNeither(): void
    {
        $this->statusListTokenMock->method('getExpirationTime')->willReturn(null);
        $this->statusListTokenMock->method('getTimeToLive')->willReturn(null);
        $this->maxCacheDurationMock->method('getInSeconds')->willReturn(21600);

        $this->artifactFetcherMock->expects($this->once())
            ->method('cacheIt')
            ->with('token', 21600, self::URI);

        $this->sut()->fromNetwork(self::URI);
    }


    /**
     * A fractional ttl is truncated rather than rounded up, so a copy is never held longer than stated.
     */
    public function testTruncatesFractionalTtl(): void
    {
        $this->statusListTokenMock->method('getExpirationTime')->willReturn(null);
        $this->statusListTokenMock->method('getTimeToLive')->willReturn(1.9);
        $this->maxCacheDurationMock->method('getInSeconds')->willReturn(21600);

        $this->artifactFetcherMock->expects($this->once())
            ->method('cacheIt')
            ->with('token', 1, self::URI);

        $this->sut()->fromNetwork(self::URI);
    }


    /**
     * A token that may not be held at all is not cached, rather than cached for zero seconds.
     */
    public function testDoesNotCacheWhenNothingIsLeftOfTheCacheDuration(): void
    {
        $this->statusListTokenMock->method('getExpirationTime')->willReturn(time() - 1);
        $this->statusListTokenMock->method('getTimeToLive')->willReturn(600);
        $this->maxCacheDurationMock->method('lowestInSecondsComparedToExpirationTime')->willReturn(0);

        $this->artifactFetcherMock->expects($this->never())->method('cacheIt');

        $this->sut()->fromNetwork(self::URI);
    }


    public function testDoesNotCacheWhenCachingIsNotAskedFor(): void
    {
        $this->artifactFetcherMock->expects($this->never())->method('cacheIt');

        $this->sut()->fromNetwork(self::URI, shouldCache: false);
    }
}
