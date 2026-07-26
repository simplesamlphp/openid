<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID;

use DateInterval;
use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Factories\AlgorithmManagerDecoratorFactory;
use SimpleSAML\OpenID\Factories\CacheDecoratorFactory;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\HttpClientDecoratorFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerDecoratorFactory;
use SimpleSAML\OpenID\Federation;
use SimpleSAML\OpenID\Federation\EntityCollection\CacheEntityCollectionStore;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionFilter;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionPaginator;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionSorter;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionStoreInterface;
use SimpleSAML\OpenID\Federation\EntityStatementFetcher;
use SimpleSAML\OpenID\Federation\Factories\EntityCollectionFactory;
use SimpleSAML\OpenID\Federation\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Federation\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustChainFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkDelegationFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkFactory;
use SimpleSAML\OpenID\Federation\FederationDiscovery;
use SimpleSAML\OpenID\Federation\MetadataPolicyApplicator;
use SimpleSAML\OpenID\Federation\MetadataPolicyResolver;
use SimpleSAML\OpenID\Federation\SubordinateListingFetcher;
use SimpleSAML\OpenID\Federation\TrustChainResolver;
use SimpleSAML\OpenID\Federation\TrustMarkFetcher;
use SimpleSAML\OpenID\Federation\TrustMarkStatusResponseFetcher;
use SimpleSAML\OpenID\Federation\TrustMarkValidator;
use SimpleSAML\OpenID\Jws\AbstractJwsFetcher;
use SimpleSAML\OpenID\Jws\Factories\JwsDecoratorBuilderFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsFetcher;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\SupportedAlgorithms;
use SimpleSAML\OpenID\SupportedSerializers;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;
use SimpleSAML\OpenID\Utils\KeyPairResolver;

#[CoversClass(Federation::class)]
#[UsesClass(ParsedJwsFactory::class)]
#[UsesClass(EntityStatementFetcher::class)]
#[UsesClass(MetadataPolicyResolver::class)]
#[UsesClass(MetadataPolicyApplicator::class)]
#[UsesClass(TrustChainFactory::class)]
#[UsesClass(TrustChainResolver::class)]
#[UsesClass(EntityStatementFactory::class)]
#[UsesClass(RequestObjectFactory::class)]
#[UsesClass(TrustMarkFactory::class)]
#[UsesClass(AlgorithmManagerDecoratorFactory::class)]
#[UsesClass(JwsSerializerManagerDecoratorFactory::class)]
#[UsesClass(JwsDecoratorBuilderFactory::class)]
#[UsesClass(JwsDecoratorBuilder::class)]
#[UsesClass(JwsVerifierDecoratorFactory::class)]
#[UsesClass(DateIntervalDecorator::class)]
#[UsesClass(DateIntervalDecoratorFactory::class)]
#[UsesClass(CacheDecorator::class)]
#[UsesClass(CacheDecoratorFactory::class)]
#[UsesClass(HttpClientDecorator::class)]
#[UsesClass(HttpClientDecoratorFactory::class)]
#[UsesClass(ArtifactFetcher::class)]
#[UsesClass(AbstractJwsFetcher::class)]
#[UsesClass(JwsFetcher::class)]
#[UsesClass(AlgorithmManagerDecorator::class)]
#[UsesClass(JwsVerifierDecorator::class)]
#[UsesClass(JwsSerializerManagerDecorator::class)]
#[UsesClass(ClaimFactory::class)]
#[UsesClass(TrustMarkDelegationFactory::class)]
#[UsesClass(TrustMarkValidator::class)]
#[UsesClass(TrustMarkFetcher::class)]
#[UsesClass(TrustMarkStatusResponseFetcher::class)]
#[UsesClass(KeyPairResolver::class)]
#[UsesClass(CacheEntityCollectionStore::class)]
#[UsesClass(EntityCollectionFilter::class)]
#[UsesClass(EntityCollectionPaginator::class)]
#[UsesClass(EntityCollectionSorter::class)]
#[UsesClass(EntityCollectionFactory::class)]
#[UsesClass(FederationDiscovery::class)]
#[UsesClass(SubordinateListingFetcher::class)]
final class FederationTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\SimpleSAML\OpenID\SupportedAlgorithms
     */
    protected \PHPUnit\Framework\MockObject\Stub $supportedAlgorithmsMock;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\SimpleSAML\OpenID\SupportedSerializers
     */
    protected \PHPUnit\Framework\MockObject\Stub $supportedSerializersMock;

    protected DateInterval $maxCacheDuration;

    protected DateInterval $timestampValidationLeeway;

    protected int $maxTrustChainDepth;

    protected int $maxAuthorityHints;

    protected int $maxTrustChainFetches;

    protected int $trustChainResolveTimeout;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\Psr\SimpleCache\CacheInterface
     */
    protected \PHPUnit\Framework\MockObject\Stub $cacheMock;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\Psr\Log\LoggerInterface
     */
    protected \PHPUnit\Framework\MockObject\Stub $loggerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\GuzzleHttp\Client
     */
    protected \PHPUnit\Framework\MockObject\Stub $clientMock;


    protected function setUp(): void
    {
        $this->supportedAlgorithmsMock = $this->createStub(SupportedAlgorithms::class);
        $this->supportedSerializersMock = $this->createStub(SupportedSerializers::class);
        $this->maxCacheDuration = new DateInterval('PT6H');
        $this->timestampValidationLeeway = new DateInterval('PT1M');
        $this->maxTrustChainDepth = 9;
        $this->maxAuthorityHints = 6;
        $this->maxTrustChainFetches = 100;
        $this->trustChainResolveTimeout = 30;
        $this->cacheMock = $this->createStub(CacheInterface::class);
        $this->loggerMock = $this->createStub(LoggerInterface::class);
        $this->clientMock = $this->createStub(Client::class);
    }


    protected function sut(
        ?SupportedAlgorithms $supportedAlgorithms = null,
        ?SupportedSerializers $supportedSerializers = null,
        ?DateInterval $maxCacheDuration = null,
        ?DateInterval $timestampValidationLeeway = null,
        ?int $maxTrustChainDepth = null,
        ?CacheInterface $cache = null,
        ?LoggerInterface $logger = null,
        ?Client $client = null,
        ?int $maxAuthorityHints = null,
        ?int $maxTrustChainFetches = null,
        ?int $trustChainResolveTimeout = null,
        ?int $maxDiscoveryDepth = null,
    ): Federation {
        $supportedAlgorithms ??= $this->supportedAlgorithmsMock;
        $supportedSerializers ??= $this->supportedSerializersMock;
        $maxCacheDuration ??= $this->maxCacheDuration;
        $timestampValidationLeeway ??= $this->timestampValidationLeeway;
        $maxTrustChainDepth ??= $this->maxTrustChainDepth;
        $cache ??= $this->cacheMock;
        $logger ??= $this->loggerMock;
        $client ??= $this->clientMock;
        $maxAuthorityHints ??= $this->maxAuthorityHints;
        $maxTrustChainFetches ??= $this->maxTrustChainFetches;
        $trustChainResolveTimeout ??= $this->trustChainResolveTimeout;
        $maxDiscoveryDepth ??= 10;

        return new Federation(
            $supportedAlgorithms,
            $supportedSerializers,
            $maxCacheDuration,
            $timestampValidationLeeway,
            $maxTrustChainDepth,
            $cache,
            $logger,
            $client,
            maxDiscoveryDepth: $maxDiscoveryDepth,
            maxAuthorityHints: $maxAuthorityHints,
            maxTrustChainFetches: $maxTrustChainFetches,
            trustChainResolveTimeout: $trustChainResolveTimeout,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Federation::class, $this->sut());
    }


    public function testPassesTrustChainResolverLimits(): void
    {
        $trustChainResolver = $this->sut(
            maxTrustChainDepth: 4,
            maxAuthorityHints: 2,
            maxTrustChainFetches: 25,
            trustChainResolveTimeout: 15,
        )->trustChainResolver();

        $this->assertSame(4, $trustChainResolver->getMaxTrustChainDepth());
        $this->assertSame(2, $trustChainResolver->getMaxAuthorityHints());
        $this->assertSame(25, $trustChainResolver->getMaxTrustChainFetches());
        $this->assertSame(15, $trustChainResolver->getTrustChainResolveTimeout());
    }


    public function testPassesDiscoveryLimits(): void
    {
        $sut = $this->sut();

        $this->assertSame(
            $sut->maxDiscoveredEntities(),
            $sut->federationDiscovery()->getMaxDiscoveredEntities(),
        );
        $this->assertSame(
            $sut->maxListingFetchSizeBytes(),
            $sut->federationDiscovery()->getMaxFetchSizeBytes(),
        );
        $this->assertSame(
            $sut->maxSubordinatesPerListing(),
            $sut->subordinateListingFetcher()->getMaxSubordinates(),
        );
        $this->assertSame(
            $sut->maxListingFetchSizeBytes(),
            $sut->subordinateListingFetcher()->getMaxFetchSizeBytes(),
        );
    }


    public function testListingsGetALargerFetchSizeAllowanceThanStatements(): void
    {
        $sut = $this->sut();

        // A listing or collection grows with the federation, an entity statement does not.
        $this->assertGreaterThan(
            HttpClientDecorator::DEFAULT_MAX_FETCH_SIZE_BYTES,
            $sut->maxListingFetchSizeBytes(),
        );
    }


    public function testClampsDiscoveryLimits(): void
    {
        $sut = $this->sut(maxDiscoveryDepth: 500);
        $this->assertSame(20, $sut->maxDiscoveryDepth());

        $sut = $this->sut(maxDiscoveryDepth: 0);
        $this->assertSame(1, $sut->maxDiscoveryDepth());
    }


    public function testClampsTrustChainResolverLimits(): void
    {
        $sut = $this->sut(maxAuthorityHints: 100, maxTrustChainFetches: 100000, trustChainResolveTimeout: 100000);

        $this->assertSame(12, $sut->maxAuthorityHints());
        $this->assertSame(1000, $sut->maxTrustChainFetches());
        $this->assertSame(300, $sut->trustChainResolveTimeout());
    }


    public function testCanBuildTools(): void
    {
        $sut = $this->sut();

        $this->assertInstanceOf(EntityStatementFetcher::class, $sut->entityStatementFetcher());
        $this->assertInstanceOf(MetadataPolicyResolver::class, $sut->metadataPolicyResolver());
        $this->assertInstanceOf(TrustChainFactory::class, $sut->trustChainFactory());
        $this->assertInstanceOf(TrustChainResolver::class, $sut->trustChainResolver());
        $this->assertInstanceOf(EntityStatementFactory::class, $sut->entityStatementFactory());
        $this->assertInstanceOf(RequestObjectFactory::class, $sut->requestObjectFactory());
        $this->assertInstanceOf(TrustMarkFactory::class, $sut->trustMarkFactory());
        $this->assertInstanceOf(DateIntervalDecorator::class, $sut->maxCacheDurationDecorator());
        $this->assertInstanceOf(SupportedAlgorithms::class, $sut->supportedAlgorithms());
        $this->assertInstanceOf(SupportedSerializers::class, $sut->supportedSerializers());
        $this->assertNotEmpty($sut->maxTrustChainDepth());
        $this->assertNotEmpty($sut->maxAuthorityHints());
        $this->assertNotEmpty($sut->maxTrustChainFetches());
        $this->assertNotEmpty($sut->trustChainResolveTimeout());
        $this->assertInstanceOf(TrustMarkDelegationFactory::class, $sut->trustMarkDelegationFactory());
        $this->assertInstanceOf(TrustMarkValidator::class, $sut->trustMarkValidator());
        $this->assertInstanceOf(TrustMarkFetcher::class, $sut->trustMarkFetcher());
        $this->assertInstanceOf(KeyPairResolver::class, $sut->keyPairResolver());
        $this->assertInstanceOf(SubordinateListingFetcher::class, $sut->subordinateListingFetcher());
        $this->assertInstanceOf(EntityCollectionStoreInterface::class, $sut->entityCollectionStore());
        $this->assertInstanceOf(EntityCollectionFactory::class, $sut->entityCollectionFactory());
        $this->assertInstanceOf(FederationDiscovery::class, $sut->federationDiscovery());
        $this->assertInstanceOf(EntityCollectionFilter::class, $sut->entityCollectionFilter());
        $this->assertInstanceOf(EntityCollectionSorter::class, $sut->entityCollectionSorter());
        $this->assertInstanceOf(EntityCollectionPaginator::class, $sut->entityCollectionPaginator());
    }
}
