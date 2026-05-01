<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\EntityDiscoveryException;
use SimpleSAML\OpenID\Federation\EntityCollection;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionStoreInterface;
use SimpleSAML\OpenID\Federation\EntityStatementFetcher;
use SimpleSAML\OpenID\Federation\Factories\EntityCollectionFactory;
use SimpleSAML\OpenID\Federation\FederationDiscovery;
use SimpleSAML\OpenID\Federation\SubordinateListingFetcher;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

#[CoversClass(FederationDiscovery::class)]
final class FederationDiscoveryTest extends TestCase
{
    protected MockObject&EntityStatementFetcher $entityStatementFetcherMock;

    protected MockObject&SubordinateListingFetcher $subordinateListingFetcherMock;

    protected MockObject&EntityCollectionStoreInterface $entityCollectionStoreMock;

    protected MockObject&DateIntervalDecorator $maxCacheDurationDecoratorMock;

    protected MockObject&EntityCollectionFactory $entityCollectionFactoryMock;

    protected MockObject&ArtifactFetcher $artifactFetcherMock;

    protected MockObject&Helpers $helpersMock;

    protected MockObject&LoggerInterface $loggerMock;


    protected function setUp(): void
    {
        $this->entityStatementFetcherMock = $this->createMock(EntityStatementFetcher::class);
        $this->subordinateListingFetcherMock = $this->createMock(SubordinateListingFetcher::class);
        $this->entityCollectionStoreMock = $this->createMock(EntityCollectionStoreInterface::class);
        $this->maxCacheDurationDecoratorMock = $this->createMock(DateIntervalDecorator::class);
        $this->entityCollectionFactoryMock = $this->createMock(EntityCollectionFactory::class);
        $this->artifactFetcherMock = $this->createMock(ArtifactFetcher::class);
        $this->helpersMock = $this->createMock(Helpers::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
    }


    protected function sut(int $maxDepth = 10): FederationDiscovery
    {
        return new FederationDiscovery(
            $this->entityStatementFetcherMock,
            $this->subordinateListingFetcherMock,
            $this->entityCollectionStoreMock,
            $this->maxCacheDurationDecoratorMock,
            $this->entityCollectionFactoryMock,
            $this->artifactFetcherMock,
            $this->helpersMock,
            $this->loggerMock,
            $maxDepth,
        );
    }


    public function testDiscoverReturnsCachedEntities(): void
    {
        $trustAnchorId = 'https://ta.example.org';
        $cachedEntities = ['https://entity.example.org' => ['sub' => 'https://entity.example.org']];
        $lastUpdated = 1234567890;
        $collection = $this->createStub(EntityCollection::class);

        $this->entityCollectionStoreMock->expects($this->once())
            ->method('get')
            ->with($trustAnchorId)
            ->willReturn($cachedEntities);

        $this->entityCollectionStoreMock->expects($this->once())
            ->method('getLastUpdated')
            ->with($trustAnchorId)
            ->willReturn($lastUpdated);

        $this->entityCollectionFactoryMock->expects($this->once())
            ->method('build')
            ->with($cachedEntities, $lastUpdated)
            ->willReturn($collection);

        $result = $this->sut()->discover($trustAnchorId);
        $this->assertSame($collection, $result);
    }


    public function testDiscoverBypassesCacheOnForceRefresh(): void
    {
        $trustAnchorId = 'https://ta.example.org';
        $taConfig = $this->createMock(\SimpleSAML\OpenID\Federation\EntityStatement::class);
        $taConfig->method('getExpirationTime')->willReturn(time() + 3600);
        $taConfig->method('getPayload')->willReturn(['sub' => $trustAnchorId]);
        $taConfig->method('getFederationListEndpoint')->willReturn(null);

        $this->entityCollectionStoreMock->expects($this->never())
            ->method('get');

        $this->entityStatementFetcherMock->expects($this->once())
            ->method('fromCacheOrWellKnownEndpoint')
            ->with($trustAnchorId)
            ->willReturn($taConfig);

        $this->maxCacheDurationDecoratorMock->method('lowestInSecondsComparedToExpirationTime')
            ->willReturn(3600);

        $collection = $this->createStub(EntityCollection::class);
        $this->entityCollectionFactoryMock->method('build')->willReturn($collection);

        $result = $this->sut()->discover($trustAnchorId, [], true);
        $this->assertSame($collection, $result);
    }


    public function testDiscoverWithTraversal(): void
    {
        $taId = 'https://ta.example.org';
        $subId = 'https://sub.example.org';
        $leafId = 'https://leaf.example.org';

        $taConfig = $this->createMock(\SimpleSAML\OpenID\Federation\EntityStatement::class);
        $taConfig->method('getExpirationTime')->willReturn(time() + 3600);
        $taConfig->method('getPayload')->willReturn(['sub' => $taId]);
        $taConfig->method('getFederationListEndpoint')->willReturn('https://ta.example.org/list');

        $subConfig = $this->createMock(\SimpleSAML\OpenID\Federation\EntityStatement::class);
        $subConfig->method('getPayload')->willReturn(['sub' => $subId]);
        $subConfig->method('getFederationListEndpoint')->willReturn('https://sub.example.org/list');

        $leafConfig = $this->createMock(\SimpleSAML\OpenID\Federation\EntityStatement::class);
        $leafConfig->method('getPayload')->willReturn(['sub' => $leafId]);
        $leafConfig->method('getFederationListEndpoint')->willReturn(null);

        $this->entityStatementFetcherMock->expects($this->exactly(3))->method('fromCacheOrWellKnownEndpoint')
            ->willReturnMap([
                [$taId, $taConfig],
                [$subId, $subConfig],
                [$leafId, $leafConfig],
            ]);

        $this->subordinateListingFetcherMock->expects($this->exactly(2))->method('fetch')
            ->willReturnMap([
                ['https://ta.example.org/list', [], false, [$subId]],
                ['https://sub.example.org/list', [], false, [$leafId]],
            ]);

        $this->maxCacheDurationDecoratorMock->method('lowestInSecondsComparedToExpirationTime')
            ->willReturn(3600);

        $expectedEntities = [
            $leafId => ['sub' => $leafId],
            $subId => ['sub' => $subId],
            $taId => ['sub' => $taId],
        ];

        $this->entityCollectionStoreMock->expects($this->once())
            ->method('store')
            ->with($taId, $expectedEntities, 3600);

        $this->sut()->discover($taId);
    }


    public function testDiscoverHandlesTraversalError(): void
    {
        $taId = 'https://ta.example.org';
        $subId = 'https://sub.example.org';

        $taConfig = $this->createMock(\SimpleSAML\OpenID\Federation\EntityStatement::class);
        $taConfig->method('getExpirationTime')->willReturn(time() + 3600);
        $taConfig->method('getPayload')->willReturn(['sub' => $taId]);
        $taConfig->method('getFederationListEndpoint')->willReturn('https://ta.example.org/list');

        $this->entityStatementFetcherMock->method('fromCacheOrWellKnownEndpoint')
            ->willReturnCallback(function (
                string $id,
            ) use (
                $taId,
                $taConfig,
): \PHPUnit\Framework\MockObject\MockObject {
                if ($id === $taId) {
                    return $taConfig;
                }

                throw new \Exception('Fetch failed');
            });

        $this->subordinateListingFetcherMock->method('fetch')
            ->with('https://ta.example.org/list')
            ->willReturn([$subId]);

        $this->maxCacheDurationDecoratorMock->method('lowestInSecondsComparedToExpirationTime')
            ->willReturn(3600);

        $expectedEntities = [
            $subId => [], // Should include subId with empty payload on failure
            $taId => ['sub' => $taId],
        ];

        $this->entityCollectionStoreMock->expects($this->once())
            ->method('store')
            ->with($taId, $expectedEntities, 3600);

        $this->sut()->discover($taId);
    }


    public function testFetchFromCollectionEndpointSuccess(): void
    {
        $endpointUri = 'https://example.org/collection';
        $filters = ['entity_type' => ['openid_provider']];
        $fullUri = 'https://example.org/collection?entity_type=openid_provider';
        $responseBody = json_encode([
            'entities' => [
                ['entity_id' => 'https://op1.example.org', 'ui_infos' => ['openid_provider' => ['name' => 'OP1']]],
            ],
            'next' => 'https://example.org/collection?from=op2',
            'last_updated' => 1234567890,
        ]);

        $urlHelper = $this->createMock(\SimpleSAML\OpenID\Helpers\Url::class);
        $urlHelper->method('withMultiValueParams')->with($endpointUri, $filters)->willReturn($fullUri);
        $this->helpersMock->method('url')->willReturn($urlHelper);

        $jsonHelper = $this->createMock(\SimpleSAML\OpenID\Helpers\Json::class);
        $jsonHelper->method('decode')->willReturn(json_decode($responseBody, true));
        $this->helpersMock->method('json')->willReturn($jsonHelper);

        $typeHelper = $this->createMock(\SimpleSAML\OpenID\Helpers\Type::class);
        $typeHelper->method('ensureNonEmptyString')->willReturn('https://op1.example.org');
        $typeHelper->method('ensureInt')->willReturn(1234567890);
        $this->helpersMock->method('type')->willReturn($typeHelper);

        $this->artifactFetcherMock->expects($this->once())
            ->method('fromCacheAsString')
            ->with($fullUri)
            ->willReturn(null);

        $this->artifactFetcherMock->expects($this->once())
            ->method('fromNetworkAsString')
            ->with($fullUri)
            ->willReturn($responseBody);

        $this->maxCacheDurationDecoratorMock->method('getInSeconds')->willReturn(3600);

        $this->artifactFetcherMock->expects($this->once())
            ->method('cacheIt')
            ->with($responseBody, 3600, $fullUri);

        $collection = $this->createStub(EntityCollection::class);
        $this->entityCollectionFactoryMock->expects($this->once())
            ->method('build')
            ->with(
                ['https://op1.example.org' => [
                    'sub' => 'https://op1.example.org',
                    'metadata' => ['openid_provider' => ['name' => 'OP1']],
                ]],
                1234567890,
                'https://example.org/collection?from=op2',
            )
            ->willReturn($collection);

        $result = $this->sut()->fetchFromCollectionEndpoint($endpointUri, $filters);
        $this->assertSame($collection, $result);
    }


    public function testFetchFromCollectionEndpointFailure(): void
    {
        $endpointUri = 'https://example.org/collection';
        $fullUri = 'https://example.org/collection';

        $urlHelper = $this->createMock(\SimpleSAML\OpenID\Helpers\Url::class);
        $urlHelper->method('withMultiValueParams')->willReturn($fullUri);
        $this->helpersMock->method('url')->willReturn($urlHelper);

        $this->artifactFetcherMock->method('fromNetworkAsString')
            ->willThrowException(new \Exception('Network error'));

        $this->expectException(EntityDiscoveryException::class);
        $this->expectExceptionMessage('Unable to fetch entity collection');

        $this->sut()->fetchFromCollectionEndpoint($endpointUri);
    }


    public function testDiscoverEntityIds(): void
    {
        $trustAnchorId = 'https://ta.example.org';
        $collection = $this->createMock(EntityCollection::class);
        $collection->method('getEntities')->willReturn([
            'https://e1.example.org' => [],
            'https://e2.example.org' => [],
        ]);

        $this->entityCollectionStoreMock->method('get')->willReturn(['some' => 'data']);
        $this->entityCollectionFactoryMock->method('build')->willReturn($collection);

        $result = $this->sut()->discoverEntityIds($trustAnchorId);
        $this->assertSame(['https://e1.example.org', 'https://e2.example.org'], $result);
    }


    public function testTraverseRespectsMaxDepth(): void
    {
        $taId = 'https://ta.example.org';
        $subId = 'https://sub.example.org';

        $taConfig = $this->createMock(\SimpleSAML\OpenID\Federation\EntityStatement::class);
        $taConfig->method('getExpirationTime')->willReturn(time() + 3600);
        $taConfig->method('getPayload')->willReturn(['sub' => $taId]);
        $taConfig->method('getFederationListEndpoint')->willReturn('https://ta.example.org/list');

        $this->entityStatementFetcherMock->method('fromCacheOrWellKnownEndpoint')->willReturn($taConfig);
        $this->subordinateListingFetcherMock->method('fetch')->willReturn([$subId]);

        // sut with maxDepth = 0
        $this->entityCollectionStoreMock->expects($this->once())
            ->method('store')
            ->with($taId, [$taId => ['sub' => $taId]], $this->anything());

        $this->sut(0)->discover($taId);
    }


    public function testTraverseAvoidsLoops(): void
    {
        $taId = 'https://ta.example.org';

        $taConfig = $this->createMock(\SimpleSAML\OpenID\Federation\EntityStatement::class);
        $taConfig->method('getExpirationTime')->willReturn(time() + 3600);
        $taConfig->method('getPayload')->willReturn(['sub' => $taId]);
        $taConfig->method('getFederationListEndpoint')->willReturn('https://ta.example.org/list');

        $this->entityStatementFetcherMock->method('fromCacheOrWellKnownEndpoint')->willReturn($taConfig);
        // List endpoint returns the TA ID itself (a loop)
        $this->subordinateListingFetcherMock->method('fetch')->willReturn([$taId]);

        $this->entityCollectionStoreMock->expects($this->once())
            ->method('store')
            ->with($taId, [$taId => ['sub' => $taId]], $this->anything());

        $this->sut()->discover($taId);
    }


    public function testDiscoverLogsErrorOnException(): void
    {
        $trustAnchorId = 'https://ta.example.org';
        $this->entityStatementFetcherMock->method('fromCacheOrWellKnownEndpoint')
            ->willThrowException(new \Exception('Critical failure'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Federation discovery failed.', $this->anything());

        $this->sut()->discover($trustAnchorId);
    }


    public function testFetchFromCollectionEndpointReturnsCached(): void
    {
        $endpointUri = 'https://example.org/collection';
        $responseBody = json_encode(['entities' => []]);

        $urlHelper = $this->createMock(\SimpleSAML\OpenID\Helpers\Url::class);
        $urlHelper->method('withMultiValueParams')->willReturn($endpointUri);
        $this->helpersMock->method('url')->willReturn($urlHelper);

        $this->artifactFetcherMock->expects($this->once())
            ->method('fromCacheAsString')
            ->with($endpointUri)
            ->willReturn($responseBody);

        $jsonHelper = $this->createMock(\SimpleSAML\OpenID\Helpers\Json::class);
        $jsonHelper->method('decode')->willReturn(['entities' => []]);
        $this->helpersMock->method('json')->willReturn($jsonHelper);

        $collection = $this->createStub(EntityCollection::class);
        $this->entityCollectionFactoryMock->method('build')->willReturn($collection);

        $result = $this->sut()->fetchFromCollectionEndpoint($endpointUri);
        $this->assertSame($collection, $result);
    }


    public function testBuildEntityCollectionFromResponseThrowsOnMissingEntities(): void
    {
        $endpointUri = 'https://example.org/collection';
        $responseBody = json_encode(['invalid' => 'data']);

        $urlHelper = $this->createMock(\SimpleSAML\OpenID\Helpers\Url::class);
        $urlHelper->method('withMultiValueParams')->willReturn($endpointUri);
        $this->helpersMock->method('url')->willReturn($urlHelper);

        $this->artifactFetcherMock->method('fromNetworkAsString')->willReturn($responseBody);

        $jsonHelper = $this->createMock(\SimpleSAML\OpenID\Helpers\Json::class);
        $jsonHelper->method('decode')->willReturn(['invalid' => 'data']);
        $this->helpersMock->method('json')->willReturn($jsonHelper);

        $this->expectException(EntityDiscoveryException::class);
        $this->expectExceptionMessage('missing "entities" array');

        $this->sut()->fetchFromCollectionEndpoint($endpointUri);
    }


    public function testBuildEntityCollectionFromResponseSkipsNonArrayEntries(): void
    {
        $endpointUri = 'https://example.org/collection';
        $responseBody = json_encode([
            'entities' => [
                'not-an-array',
                ['entity_id' => 'https://valid.example.org'],
            ],
        ]);

        $urlHelper = $this->createMock(\SimpleSAML\OpenID\Helpers\Url::class);
        $urlHelper->method('withMultiValueParams')->willReturn($endpointUri);
        $this->helpersMock->method('url')->willReturn($urlHelper);

        $this->artifactFetcherMock->method('fromNetworkAsString')->willReturn($responseBody);

        $jsonHelper = $this->createMock(\SimpleSAML\OpenID\Helpers\Json::class);
        $jsonHelper->method('decode')->willReturn([
            'entities' => [
                'not-an-array',
                ['entity_id' => 'https://valid.example.org'],
            ],
        ]);
        $this->helpersMock->method('json')->willReturn($jsonHelper);

        $typeHelper = $this->createMock(\SimpleSAML\OpenID\Helpers\Type::class);
        $typeHelper->method('ensureNonEmptyString')->willReturn('https://valid.example.org');
        $this->helpersMock->method('type')->willReturn($typeHelper);

        $this->entityCollectionFactoryMock->expects($this->once())
            ->method('build')
            ->with(['https://valid.example.org' => [
                'sub' => 'https://valid.example.org',
                'metadata' => [],
            ]])
            ->willReturn($this->createStub(EntityCollection::class));

        $this->sut()->fetchFromCollectionEndpoint($endpointUri);
    }


    public function testBuildEntityCollectionFromResponseWithTrustMarksAndLastUpdated(): void
    {
        $endpointUri = 'https://example.org/collection';
        $responseBody = json_encode([
            'entities' => [
                [
                    'entity_id' => 'https://valid.example.org',
                    'trust_marks' => [['id' => 'tm1']],
                ],
            ],
            'last_updated' => '1234567890',
        ]);

        $urlHelper = $this->createMock(\SimpleSAML\OpenID\Helpers\Url::class);
        $urlHelper->method('withMultiValueParams')->willReturn($endpointUri);
        $this->helpersMock->method('url')->willReturn($urlHelper);

        $this->artifactFetcherMock->method('fromNetworkAsString')->willReturn($responseBody);

        $jsonHelper = $this->createMock(\SimpleSAML\OpenID\Helpers\Json::class);
        $jsonHelper->method('decode')->willReturn(json_decode($responseBody, true));
        $this->helpersMock->method('json')->willReturn($jsonHelper);

        $typeHelper = $this->createMock(\SimpleSAML\OpenID\Helpers\Type::class);
        $typeHelper->method('ensureNonEmptyString')->willReturn('https://valid.example.org');
        $typeHelper->method('ensureInt')->willReturn(1234567890);
        $this->helpersMock->method('type')->willReturn($typeHelper);

        $this->entityCollectionFactoryMock->expects($this->once())
            ->method('build')
            ->with(
                ['https://valid.example.org' => [
                    'sub' => 'https://valid.example.org',
                    'metadata' => [],
                    'trust_marks' => [['id' => 'tm1']],
                ]],
                1234567890,
            )
            ->willReturn($this->createStub(EntityCollection::class));

        $this->sut()->fetchFromCollectionEndpoint($endpointUri);
    }


    public function testTraverseHandlesSubordinateListingFailure(): void
    {
        $taId = 'https://ta.example.org';

        $taConfig = $this->createMock(\SimpleSAML\OpenID\Federation\EntityStatement::class);
        $taConfig->method('getExpirationTime')->willReturn(time() + 3600);
        $taConfig->method('getPayload')->willReturn(['sub' => $taId]);
        $taConfig->method('getFederationListEndpoint')->willReturn('https://ta.example.org/list');

        $this->entityStatementFetcherMock->method('fromCacheOrWellKnownEndpoint')->willReturn($taConfig);
        $this->subordinateListingFetcherMock->method('fetch')->willThrowException(new \Exception('Listing failed'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Failed to fetch subordinate listing during discovery.', $this->anything());

        $this->sut()->discover($taId);
    }
}
