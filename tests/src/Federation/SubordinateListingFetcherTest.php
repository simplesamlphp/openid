<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\EntityDiscoveryException;
use SimpleSAML\OpenID\Federation\SubordinateListingFetcher;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

#[CoversClass(SubordinateListingFetcher::class)]
final class SubordinateListingFetcherTest extends TestCase
{
    protected MockObject&ArtifactFetcher $artifactFetcherMock;

    protected MockObject&Helpers $helpersMock;

    protected MockObject&\SimpleSAML\OpenID\Helpers\Json $jsonHelperMock;

    protected MockObject&\SimpleSAML\OpenID\Helpers\Type $typeHelperMock;

    protected MockObject&DateIntervalDecorator $maxCacheDurationMock;


    protected function setUp(): void
    {
        $this->artifactFetcherMock = $this->createMock(ArtifactFetcher::class);
        $this->helpersMock = $this->createMock(Helpers::class);
        $this->jsonHelperMock = $this->createMock(\SimpleSAML\OpenID\Helpers\Json::class);
        $this->typeHelperMock = $this->createMock(\SimpleSAML\OpenID\Helpers\Type::class);
        $this->maxCacheDurationMock = $this->createMock(DateIntervalDecorator::class);

        // Set up common helper mocks
        $urlHelper = $this->createMock(\SimpleSAML\OpenID\Helpers\Url::class);
        $urlHelper->method('withMultiValueParams')->willReturn('http://example.com/list');
        $this->helpersMock->method('url')->willReturn($urlHelper);
        $this->helpersMock->method('json')->willReturn($this->jsonHelperMock);
        $this->helpersMock->method('type')->willReturn($this->typeHelperMock);
    }


    protected function sut(): SubordinateListingFetcher
    {
        return new SubordinateListingFetcher(
            $this->artifactFetcherMock,
            $this->helpersMock,
            $this->maxCacheDurationMock,
            $this->createStub(\Psr\Log\LoggerInterface::class),
        );
    }


    public function testFetchReturnsCachedDataIfAvailable(): void
    {
        $uri = 'http://example.com/list';
        $cachedResponse = '["sub1", "sub2"]';
        $decodedResponse = ['sub1', 'sub2'];

        $this->artifactFetcherMock->expects($this->once())
            ->method('fromCacheAsString')
            ->with($uri)
            ->willReturn($cachedResponse);

        $this->artifactFetcherMock->expects($this->never())
            ->method('fromNetworkAsString');

        $this->jsonHelperMock->expects($this->once())
            ->method('decode')
            ->with($cachedResponse)
            ->willReturn($decodedResponse);

        $this->typeHelperMock->expects($this->once())
            ->method('ensureArrayWithValuesAsNonEmptyStrings')
            ->with($decodedResponse)
            ->willReturn($decodedResponse);

        $result = $this->sut()->fetch($uri);
        $this->assertSame($decodedResponse, $result);
    }


    public function testFetchFromNetworkOnCacheMissAndCachesResult(): void
    {
        $uri = 'http://example.com/list';
        $networkResponse = '["sub3"]';
        $decodedResponse = ['sub3'];
        $ttl = 3600;

        $this->artifactFetcherMock->expects($this->once())
            ->method('fromCacheAsString')
            ->with($uri)
            ->willReturn(null);

        $this->artifactFetcherMock->expects($this->once())
            ->method('fromNetworkAsString')
            ->with($uri)
            ->willReturn($networkResponse);

        $this->jsonHelperMock->expects($this->once())
            ->method('decode')
            ->with($networkResponse)
            ->willReturn($decodedResponse);

        $this->typeHelperMock->expects($this->once())
            ->method('ensureArrayWithValuesAsNonEmptyStrings')
            ->with($decodedResponse)
            ->willReturn($decodedResponse);

        $this->maxCacheDurationMock->method('getInSeconds')->willReturn($ttl);

        $this->artifactFetcherMock->expects($this->once())
            ->method('cacheIt')
            ->with($networkResponse, $ttl, $uri);

        $result = $this->sut()->fetch($uri);
        $this->assertSame($decodedResponse, $result);
    }


    public function testForceRefreshBypassesCache(): void
    {
        $uri = 'http://example.com/list';
        $networkResponse = '["sub4"]';
        $decodedResponse = ['sub4'];

        $this->artifactFetcherMock->expects($this->never())
            ->method('fromCacheAsString');

        $this->artifactFetcherMock->expects($this->once())
            ->method('fromNetworkAsString')
            ->with($uri)
            ->willReturn($networkResponse);

        $this->jsonHelperMock->method('decode')->willReturn($decodedResponse);
        $this->typeHelperMock->method('ensureArrayWithValuesAsNonEmptyStrings')->willReturn($decodedResponse);

        $result = $this->sut()->fetch($uri, [], true);
        $this->assertSame($decodedResponse, $result);
    }


    public function testFetchThrowsExceptionOnInvalidJson(): void
    {
        $uri = 'http://example.com/list';
        $invalidJson = 'invalid';

        $this->artifactFetcherMock->method('fromCacheAsString')->willReturn(null);
        $this->artifactFetcherMock->method('fromNetworkAsString')->willReturn($invalidJson);
        $this->jsonHelperMock->method('decode')->willReturn(null); // Invalid JSON decodes to null

        $this->expectException(EntityDiscoveryException::class);
        $this->expectExceptionMessage('JSON array');

        $this->sut()->fetch($uri);
    }
}
