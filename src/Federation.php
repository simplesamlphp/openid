<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use DateInterval;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Codebooks\TrustMarkStatusEndpointUsagePolicyEnum;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Factories\AlgorithmManagerDecoratorFactory;
use SimpleSAML\OpenID\Factories\CacheDecoratorFactory;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\HttpClientDecoratorFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerDecoratorFactory;
use SimpleSAML\OpenID\Federation\EntityCollection\CacheEntityCollectionStore;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionFilter;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionPaginator;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionSorter;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionStoreInterface;
use SimpleSAML\OpenID\Federation\EntityCollection\InMemoryEntityCollectionStore;
use SimpleSAML\OpenID\Federation\EntityStatementFetcher;
use SimpleSAML\OpenID\Federation\Factories\EntityCollectionFactory;
use SimpleSAML\OpenID\Federation\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Federation\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustChainBagFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustChainFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkDelegationFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkStatusResponseFactory;
use SimpleSAML\OpenID\Federation\FederationDiscovery;
use SimpleSAML\OpenID\Federation\MetadataPolicyApplicator;
use SimpleSAML\OpenID\Federation\MetadataPolicyResolver;
use SimpleSAML\OpenID\Federation\SubordinateListingFetcher;
use SimpleSAML\OpenID\Federation\TrustChainResolver;
use SimpleSAML\OpenID\Federation\TrustMarkFetcher;
use SimpleSAML\OpenID\Federation\TrustMarkStatusResponseFetcher;
use SimpleSAML\OpenID\Federation\TrustMarkValidator;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsDecoratorBuilderFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;
use SimpleSAML\OpenID\Utils\KeyPairResolver;

/**
 * @see \SimpleSAML\Test\OpenID\FederationTest
 */
class Federation
{
    protected DateIntervalDecorator $maxCacheDurationDecorator;

    protected DateIntervalDecorator $timestampValidationLeewayDecorator;

    protected int $maxTrustChainDepth;

    protected int $maxAuthorityHints;

    protected int $maxTrustChainFetches;

    protected int $trustChainResolveTimeout;

    protected int $maxDiscoveryDepth;

    protected int $maxDiscoveredEntities;

    protected int $maxSubordinatesPerListing;

    protected int $maxListingFetchSizeBytes;

    protected ?CacheDecorator $cacheDecorator;

    protected HttpClientDecorator $httpClientDecorator;

    protected ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null;

    protected ?JwsDecoratorBuilder $jwsDecoratorBuilder = null;

    protected ?JwsVerifierDecorator $jwsVerifierDecorator  = null;

    protected ?SubordinateListingFetcher $subordinateListingFetcher = null;

    protected ?FederationDiscovery $federationDiscovery = null;

    protected ?EntityCollectionFilter $entityCollectionFilter = null;

    protected ?EntityCollectionSorter $entityCollectionSorter = null;

    protected ?EntityCollectionPaginator $entityCollectionPaginator = null;

    protected ?EntityStatementFetcher $entityStatementFetcher = null;

    protected ?MetadataPolicyResolver $metadataPolicyResolver = null;

    protected ?MetadataPolicyApplicator $metadataPolicyApplicator = null;

    protected ?TrustChainFactory $trustChainFactory = null;

    protected ?TrustChainResolver $trustChainResolver = null;

    protected ?EntityStatementFactory $entityStatementFactory = null;

    protected ?RequestObjectFactory $requestObjectFactory = null;

    protected ?TrustMarkFactory $trustMarkFactory = null;

    protected ?Helpers $helpers = null;

    protected ?AlgorithmManagerDecoratorFactory $algorithmManagerDecoratorFactory = null;

    protected ?JwsSerializerManagerDecoratorFactory $jwsSerializerManagerDecoratorFactory = null;

    protected ?JwsDecoratorBuilderFactory $jwsDecoratorBuilderFactory = null;

    protected ?JwsVerifierDecoratorFactory $jwsVerifierDecoratorFactory = null;

    protected ?JwksDecoratorFactory $jwksDecoratorFactory = null;

    protected ?DateIntervalDecoratorFactory $dateIntervalDecoratorFactory = null;

    protected ?HttpClientDecoratorFactory $httpClientDecoratorFactory = null;

    protected ?TrustChainBagFactory $trustChainBagFactory = null;

    protected ?CacheDecoratorFactory $cacheDecoratorFactory = null;

    protected ?ArtifactFetcher $artifactFetcher = null;

    protected ?ClaimFactory $claimFactory = null;

    protected ?TrustMarkDelegationFactory $trustMarkDelegationFactory = null;

    protected ?TrustMarkValidator $trustMarkValidator = null;

    protected ?TrustMarkFetcher $trustMarkFetcher = null;

    protected ?AlgorithmManagerDecorator $algorithmManagerDecorator = null;

    protected ?TrustMarkStatusResponseFactory $trustMarkStatusResponseFactory = null;

    protected ?TrustMarkStatusResponseFetcher $trustMarkStatusResponseFetcher = null;

    protected ?KeyPairResolver $keyPairResolver = null;

    protected ?EntityCollectionFactory $entityCollectionFactory = null;


    /**
     * @param array<string,mixed> $httpClientConfig
     * @param int $maxAuthorityHints Maximum number of authority hints allowed on a single entity configuration.
     * @param int $maxTrustChainFetches Maximum number of entity statement fetches allowed for a single Trust Chain
     * resolution. Together with $trustChainResolveTimeout this bounds one resolution as a whole, which the per-node
     * depth and authority hints limits can not do on their own.
     * @param int $trustChainResolveTimeout Wall clock deadline, in seconds, for a single Trust Chain resolution.
     * @param int $maxFetchSizeBytes Maximum response body size to read for any single fetch (entity statements,
     * JWKS documents). Subordinate listings and entity collections have their own, larger allowance.
     * @param int $maxDiscoveredEntities Total number of entities a single federation discovery may collect.
     * @param int $maxSubordinatesPerListing Maximum number of entity IDs taken from a single listing.
     * @param int $maxListingFetchSizeBytes Maximum response body size to read for a subordinate listing or an
     * entity collection. Larger than $maxFetchSizeBytes by default, since these responses grow with the size
     * of the federation rather than being one statement each.
     */
    public function __construct(
        protected readonly SupportedAlgorithms $supportedAlgorithms = new SupportedAlgorithms(),
        protected readonly SupportedSerializers $supportedSerializers = new SupportedSerializers(),
        DateInterval $maxCacheDuration = new DateInterval('PT6H'),
        DateInterval $timestampValidationLeeway = new DateInterval('PT1M'),
        int $maxTrustChainDepth = 9,
        ?CacheInterface $cache = null,
        protected readonly ?LoggerInterface $logger = null,
        ?Client $client = null,
        // phpcs:ignore
        protected readonly TrustMarkStatusEndpointUsagePolicyEnum $defaultTrustMarkStatusEndpointUsagePolicyEnum = TrustMarkStatusEndpointUsagePolicyEnum::NotUsed,
        int $maxDiscoveryDepth = 10,
        protected ?EntityCollectionStoreInterface $entityCollectionStore = null,
        array $httpClientConfig = [],
        int $maxAuthorityHints = 6,
        int $maxTrustChainFetches = 100,
        int $trustChainResolveTimeout = 30,
        int $maxFetchSizeBytes = HttpClientDecorator::DEFAULT_MAX_FETCH_SIZE_BYTES,
        int $maxDiscoveredEntities = FederationDiscovery::DEFAULT_MAX_DISCOVERED_ENTITIES,
        int $maxSubordinatesPerListing = SubordinateListingFetcher::DEFAULT_MAX_SUBORDINATES,
        int $maxListingFetchSizeBytes = SubordinateListingFetcher::DEFAULT_MAX_FETCH_SIZE_BYTES,
    ) {
        $this->maxCacheDurationDecorator = $this->dateIntervalDecoratorFactory()->build($maxCacheDuration);
        $this->timestampValidationLeewayDecorator = $this->dateIntervalDecoratorFactory()
            ->build($timestampValidationLeeway);
        $this->maxTrustChainDepth = min(20, max(1, $maxTrustChainDepth));
        $this->maxAuthorityHints = min(12, max(1, $maxAuthorityHints));
        $this->maxTrustChainFetches = min(1000, max(1, $maxTrustChainFetches));
        $this->trustChainResolveTimeout = min(300, max(1, $trustChainResolveTimeout));
        // Clamped at both ends, as maxTrustChainDepth is. Left open ended, the traversal could be told to go
        // arbitrarily deep.
        $this->maxDiscoveryDepth = min(20, max(1, $maxDiscoveryDepth));
        $this->maxDiscoveredEntities = min(100000, max(1, $maxDiscoveredEntities));
        $this->maxSubordinatesPerListing = min(10000, max(1, $maxSubordinatesPerListing));
        $this->maxListingFetchSizeBytes = max(1, $maxListingFetchSizeBytes);
        $this->cacheDecorator = is_null($cache) ? null : $this->cacheDecoratorFactory()->build($cache);
        $this->httpClientDecorator = $this->httpClientDecoratorFactory()->build(
            $client,
            $httpClientConfig,
            $maxFetchSizeBytes,
        );
    }


    public function jwsVerifierDecorator(): JwsVerifierDecorator
    {
        return $this->jwsVerifierDecorator ??= $this->jwsVerifierDecoratorFactory()->build(
            $this->algorithmManagerDecorator(),
        );
    }


    public function jwsDecoratorBuilder(): JwsDecoratorBuilder
    {
        return $this->jwsDecoratorBuilder ??= $this->jwsDecoratorBuilderFactory()->build(
            $this->jwsSerializerManagerDecorator(),
            $this->algorithmManagerDecorator(),
            $this->helpers(),
        );
    }


    public function jwsSerializerManagerDecorator(): JwsSerializerManagerDecorator
    {
        return $this->jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorFactory()
            ->build($this->supportedSerializers);
    }


    public function entityStatementFetcher(): EntityStatementFetcher
    {
        return $this->entityStatementFetcher ??= new EntityStatementFetcher(
            $this->entityStatementFactory(),
            $this->artifactFetcher(),
            $this->maxCacheDurationDecorator(),
            $this->helpers(),
            $this->logger,
        );
    }


    public function metadataPolicyResolver(): MetadataPolicyResolver
    {
        return $this->metadataPolicyResolver ??= new MetadataPolicyResolver($this->helpers());
    }


    public function metadataPolicyApplicator(): MetadataPolicyApplicator
    {
        return $this->metadataPolicyApplicator ??= new MetadataPolicyApplicator($this->helpers());
    }


    public function trustChainFactory(): TrustChainFactory
    {
        return $this->trustChainFactory ??= new TrustChainFactory(
            $this->entityStatementFactory(),
            $this->timestampValidationLeewayDecorator(),
            $this->metadataPolicyResolver(),
            $this->metadataPolicyApplicator(),
            $this->helpers(),
        );
    }


    public function trustChainResolver(): TrustChainResolver
    {
        return $this->trustChainResolver ??= new TrustChainResolver(
            $this->entityStatementFetcher(),
            $this->trustChainFactory(),
            $this->trustChainBagFactory(),
            $this->maxCacheDurationDecorator(),
            $this->cacheDecorator,
            $this->logger,
            $this->maxTrustChainDepth,
            $this->maxAuthorityHints,
            $this->maxTrustChainFetches,
            $this->trustChainResolveTimeout,
        );
    }


    public function entityStatementFactory(): EntityStatementFactory
    {
        return $this->entityStatementFactory ??= new EntityStatementFactory(
            $this->jwsDecoratorBuilder(),
            $this->jwsVerifierDecorator(),
            $this->jwksDecoratorFactory(),
            $this->jwsSerializerManagerDecorator(),
            $this->timestampValidationLeewayDecorator(),
            $this->helpers(),
            $this->claimFactory(),
        );
    }


    public function requestObjectFactory(): RequestObjectFactory
    {
        return $this->requestObjectFactory ??= new RequestObjectFactory(
            $this->jwsDecoratorBuilder(),
            $this->jwsVerifierDecorator(),
            $this->jwksDecoratorFactory(),
            $this->jwsSerializerManagerDecorator(),
            $this->timestampValidationLeewayDecorator(),
            $this->helpers(),
            $this->claimFactory(),
        );
    }


    public function trustMarkFactory(): TrustMarkFactory
    {
        return $this->trustMarkFactory ??= new TrustMarkFactory(
            $this->jwsDecoratorBuilder(),
            $this->jwsVerifierDecorator(),
            $this->jwksDecoratorFactory(),
            $this->jwsSerializerManagerDecorator(),
            $this->timestampValidationLeewayDecorator(),
            $this->helpers(),
            $this->claimFactory(),
        );
    }


    public function trustMarkDelegationFactory(): TrustMarkDelegationFactory
    {
        return $this->trustMarkDelegationFactory ?? new TrustMarkDelegationFactory(
            $this->jwsDecoratorBuilder(),
            $this->jwsVerifierDecorator(),
            $this->jwksDecoratorFactory(),
            $this->jwsSerializerManagerDecorator(),
            $this->timestampValidationLeewayDecorator(),
            $this->helpers(),
            $this->claimFactory(),
        );
    }


    public function trustMarkStatusResponseFactory(): TrustMarkStatusResponseFactory
    {
        return $this->trustMarkStatusResponseFactory ??= new TrustMarkStatusResponseFactory(
            $this->jwsDecoratorBuilder(),
            $this->jwsVerifierDecorator(),
            $this->jwksDecoratorFactory(),
            $this->jwsSerializerManagerDecorator(),
            $this->timestampValidationLeewayDecorator(),
            $this->helpers(),
            $this->claimFactory(),
        );
    }


    public function trustMarkStatusResponseFetcher(): TrustMarkStatusResponseFetcher
    {
        return $this->trustMarkStatusResponseFetcher ??= new TrustMarkStatusResponseFetcher(
            $this->trustMarkStatusResponseFactory(),
            $this->artifactFetcher(),
            $this->maxCacheDurationDecorator(),
            $this->helpers(),
            $this->logger,
        );
    }


    public function trustMarkValidator(): TrustMarkValidator
    {
        return $this->trustMarkValidator ??= new TrustMarkValidator(
            $this->trustChainResolver(),
            $this->trustMarkFactory(),
            $this->trustMarkDelegationFactory(),
            $this->trustMarkStatusResponseFetcher(),
            $this->maxCacheDurationDecorator(),
            $this->cacheDecorator(),
            $this->logger,
            $this->defaultTrustMarkStatusEndpointUsagePolicyEnum(),
        );
    }


    public function trustMarkFetcher(): TrustMarkFetcher
    {
        return $this->trustMarkFetcher ??= new TrustMarkFetcher(
            $this->trustMarkFactory(),
            $this->artifactFetcher(),
            $this->maxCacheDurationDecorator(),
            $this->helpers(),
            $this->logger,
        );
    }


    public function subordinateListingFetcher(): SubordinateListingFetcher
    {
        return $this->subordinateListingFetcher ??= new SubordinateListingFetcher(
            $this->artifactFetcher(),
            $this->helpers(),
            $this->maxCacheDurationDecorator(),
            $this->logger,
            $this->maxSubordinatesPerListing,
            $this->maxListingFetchSizeBytes,
        );
    }


    public function entityCollectionStore(): EntityCollectionStoreInterface
    {
        if ($this->entityCollectionStore instanceof Federation\EntityCollection\EntityCollectionStoreInterface) {
            return $this->entityCollectionStore;
        }

        return $this->entityCollectionStore =
        $this->cacheDecorator() instanceof \SimpleSAML\OpenID\Decorators\CacheDecorator ?
        new CacheEntityCollectionStore(
            $this->cacheDecorator(),
            $this->helpers(),
            $this->logger,
        ) :
        new InMemoryEntityCollectionStore();
    }


    public function entityCollectionFactory(): EntityCollectionFactory
    {
        return $this->entityCollectionFactory ??= new EntityCollectionFactory(
            $this->entityCollectionFilter(),
            $this->entityCollectionSorter(),
            $this->entityCollectionPaginator(),
        );
    }


    public function federationDiscovery(): FederationDiscovery
    {
        if (!$this->federationDiscovery instanceof \SimpleSAML\OpenID\Federation\FederationDiscovery) {
            $this->federationDiscovery = new FederationDiscovery(
                $this->entityStatementFetcher(),
                $this->subordinateListingFetcher(),
                $this->entityCollectionStore(),
                $this->maxCacheDurationDecorator(),
                $this->entityCollectionFactory(),
                $this->artifactFetcher(),
                $this->helpers(),
                $this->logger,
                $this->maxDiscoveryDepth,
                $this->maxDiscoveredEntities,
                $this->maxListingFetchSizeBytes,
            );
        }

        return $this->federationDiscovery;
    }


    public function entityCollectionFilter(): EntityCollectionFilter
    {
        return $this->entityCollectionFilter ??= new EntityCollectionFilter($this->helpers());
    }


    public function entityCollectionSorter(): EntityCollectionSorter
    {
        return $this->entityCollectionSorter ??= new EntityCollectionSorter($this->helpers());
    }


    public function entityCollectionPaginator(): EntityCollectionPaginator
    {
        return $this->entityCollectionPaginator ??= new EntityCollectionPaginator(
            $this->helpers(),
        );
    }


    public function helpers(): Helpers
    {
        return $this->helpers ??= new Helpers();
    }


    public function algorithmManagerDecoratorFactory(): AlgorithmManagerDecoratorFactory
    {
        return $this->algorithmManagerDecoratorFactory ??= new AlgorithmManagerDecoratorFactory();
    }


    public function algorithmManagerDecorator(): AlgorithmManagerDecorator
    {
        return $this->algorithmManagerDecorator ??= $this->algorithmManagerDecoratorFactory()
            ->build($this->supportedAlgorithms);
    }


    public function jwsSerializerManagerDecoratorFactory(): JwsSerializerManagerDecoratorFactory
    {
        return $this->jwsSerializerManagerDecoratorFactory ??= new JwsSerializerManagerDecoratorFactory();
    }


    public function jwsDecoratorBuilderFactory(): JwsDecoratorBuilderFactory
    {
        return $this->jwsDecoratorBuilderFactory ??= new JwsDecoratorBuilderFactory();
    }


    public function jwsVerifierDecoratorFactory(): JwsVerifierDecoratorFactory
    {
        return $this->jwsVerifierDecoratorFactory ??= new JwsVerifierDecoratorFactory();
    }


    public function jwksDecoratorFactory(): JwksDecoratorFactory
    {
        return $this->jwksDecoratorFactory ??= new JwksDecoratorFactory();
    }


    public function dateIntervalDecoratorFactory(): DateIntervalDecoratorFactory
    {
        if (is_null($this->dateIntervalDecoratorFactory)) {
            $this->dateIntervalDecoratorFactory = new DateIntervalDecoratorFactory();
        }

        return $this->dateIntervalDecoratorFactory;
    }


    public function trustChainBagFactory(): TrustChainBagFactory
    {
        return $this->trustChainBagFactory ??= new TrustChainBagFactory();
    }


    public function httpClientDecoratorFactory(): HttpClientDecoratorFactory
    {
        if (is_null($this->httpClientDecoratorFactory)) {
            $this->httpClientDecoratorFactory = new HttpClientDecoratorFactory($this->logger);
        }

        return $this->httpClientDecoratorFactory;
    }


    public function cacheDecoratorFactory(): CacheDecoratorFactory
    {
        if (is_null($this->cacheDecoratorFactory)) {
            $this->cacheDecoratorFactory = new CacheDecoratorFactory();
        }

        return $this->cacheDecoratorFactory;
    }


    public function maxCacheDurationDecorator(): DateIntervalDecorator
    {
        return $this->maxCacheDurationDecorator;
    }


    public function supportedAlgorithms(): SupportedAlgorithms
    {
        return $this->supportedAlgorithms;
    }


    public function supportedSerializers(): SupportedSerializers
    {
        return $this->supportedSerializers;
    }


    public function maxTrustChainDepth(): int
    {
        return $this->maxTrustChainDepth;
    }


    public function maxDiscoveryDepth(): int
    {
        return $this->maxDiscoveryDepth;
    }


    public function maxDiscoveredEntities(): int
    {
        return $this->maxDiscoveredEntities;
    }


    public function maxSubordinatesPerListing(): int
    {
        return $this->maxSubordinatesPerListing;
    }


    public function maxListingFetchSizeBytes(): int
    {
        return $this->maxListingFetchSizeBytes;
    }


    public function maxAuthorityHints(): int
    {
        return $this->maxAuthorityHints;
    }


    public function maxTrustChainFetches(): int
    {
        return $this->maxTrustChainFetches;
    }


    public function trustChainResolveTimeout(): int
    {
        return $this->trustChainResolveTimeout;
    }


    public function cacheDecorator(): ?CacheDecorator
    {
        return $this->cacheDecorator;
    }


    public function artifactFetcher(): ArtifactFetcher
    {
        return $this->artifactFetcher ??= new ArtifactFetcher(
            $this->httpClientDecorator,
            $this->cacheDecorator(),
            $this->logger,
        );
    }


    public function claimFactory(): ClaimFactory
    {
        return $this->claimFactory ??= new ClaimFactory(
            $this->helpers(),
        );
    }


    public function timestampValidationLeewayDecorator(): DateIntervalDecorator
    {
        return $this->timestampValidationLeewayDecorator;
    }


    public function defaultTrustMarkStatusEndpointUsagePolicyEnum(): TrustMarkStatusEndpointUsagePolicyEnum
    {
        return $this->defaultTrustMarkStatusEndpointUsagePolicyEnum;
    }


    public function keyPairResolver(): KeyPairResolver
    {
        return $this->keyPairResolver ??= new KeyPairResolver(
            $this->helpers(),
            $this->logger,
        );
    }
}
