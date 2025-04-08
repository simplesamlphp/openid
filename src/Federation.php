<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use DateInterval;
use GuzzleHttp\Client;
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
use SimpleSAML\OpenID\Federation\EntityStatementFetcher;
use SimpleSAML\OpenID\Federation\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Federation\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustChainBagFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustChainFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkDelegationFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkFactory;
use SimpleSAML\OpenID\Federation\MetadataPolicyApplicator;
use SimpleSAML\OpenID\Federation\MetadataPolicyResolver;
use SimpleSAML\OpenID\Federation\TrustChainResolver;
use SimpleSAML\OpenID\Federation\TrustMarkFetcher;
use SimpleSAML\OpenID\Federation\TrustMarkValidator;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsDecoratorBuilderFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

class Federation
{
    protected DateIntervalDecorator $maxCacheDurationDecorator;

    protected DateIntervalDecorator $timestampValidationLeewayDecorator;

    protected int $maxTrustChainDepth;

    protected ?CacheDecorator $cacheDecorator;

    protected HttpClientDecorator $httpClientDecorator;

    protected ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null;

    protected ?JwsDecoratorBuilder $jwsDecoratorBuilder = null;

    protected ?JwsVerifierDecorator $jwsVerifierDecorator  = null;

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

    public function __construct(
        protected readonly SupportedAlgorithms $supportedAlgorithms = new SupportedAlgorithms(),
        protected readonly SupportedSerializers $supportedSerializers = new SupportedSerializers(),
        DateInterval $maxCacheDuration = new DateInterval('PT6H'),
        DateInterval $timestampValidationLeeway = new DateInterval('PT1M'),
        int $maxTrustChainDepth = 9,
        ?CacheInterface $cache = null,
        protected readonly ?LoggerInterface $logger = null,
        ?Client $client = null,
    ) {
        $this->maxCacheDurationDecorator = $this->dateIntervalDecoratorFactory()->build($maxCacheDuration);
        $this->timestampValidationLeewayDecorator = $this->dateIntervalDecoratorFactory()
            ->build($timestampValidationLeeway);
        $this->maxTrustChainDepth = min(20, max(1, $maxTrustChainDepth));
        $this->cacheDecorator = is_null($cache) ? null : $this->cacheDecoratorFactory()->build($cache);
        $this->httpClientDecorator = $this->httpClientDecoratorFactory()->build($client);
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
            $this->maxCacheDurationDecorator,
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
            $this->timestampValidationLeewayDecorator,
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
            $this->maxCacheDurationDecorator,
            $this->cacheDecorator,
            $this->logger,
            $this->maxTrustChainDepth,
        );
    }

    public function entityStatementFactory(): EntityStatementFactory
    {
        return $this->entityStatementFactory ??= new EntityStatementFactory(
            $this->jwsDecoratorBuilder(),
            $this->jwsVerifierDecorator(),
            $this->jwksDecoratorFactory(),
            $this->jwsSerializerManagerDecorator(),
            $this->timestampValidationLeewayDecorator,
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
            $this->timestampValidationLeewayDecorator,
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
            $this->timestampValidationLeewayDecorator,
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
            $this->timestampValidationLeewayDecorator,
            $this->helpers(),
            $this->claimFactory(),
        );
    }

    public function trustMarkValidator(): TrustMarkValidator
    {
        return $this->trustMarkValidator ??= new TrustMarkValidator(
            $this->trustChainResolver(),
            $this->trustMarkFactory(),
            $this->trustMarkDelegationFactory(),
            $this->maxCacheDurationDecorator,
            $this->cacheDecorator(),
            $this->logger,
        );
    }

    public function trustMarkFetcher(): TrustMarkFetcher
    {
        return $this->trustMarkFetcher ??= new TrustMarkFetcher(
            $this->trustMarkFactory(),
            $this->artifactFetcher(),
            $this->maxCacheDurationDecorator,
            $this->helpers(),
            $this->logger,
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
            $this->httpClientDecoratorFactory = new HttpClientDecoratorFactory();
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
}
