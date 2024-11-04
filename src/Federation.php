<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use DateInterval;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Factories\AlgorithmManagerFactory;
use SimpleSAML\OpenID\Factories\CacheDecoratorFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\HttpClientDecoratorFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerFactory;
use SimpleSAML\OpenID\Federation\EntityStatementFetcher;
use SimpleSAML\OpenID\Federation\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Federation\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustChainFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkFactory;
use SimpleSAML\OpenID\Federation\MetadataPolicyResolver;
use SimpleSAML\OpenID\Federation\TrustChainResolver;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsParserFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierFactory;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Jws\JwsVerifier;
use SimpleSAML\OpenID\Serializers\JwsSerializerManager;

class Federation
{
    protected DateIntervalDecorator $maxCacheDuration;
    protected DateIntervalDecorator $timestampValidationLeeway;
    protected int $maxTrustChainDepth;
    protected ?CacheDecorator $cacheDecorator;
    protected HttpClientDecorator $httpClientDecorator;
    protected JwsSerializerManager $jwsSerializerManager;
    protected JwsParser $jwsParser;
    protected JwsVerifier $jwsVerifier;

    public function __construct(
        protected readonly SupportedAlgorithms $supportedAlgorithms = new SupportedAlgorithms(),
        protected readonly SupportedSerializers $supportedSerializers = new SupportedSerializers(),
        DateInterval $maxCacheDuration = new DateInterval('PT6H'),
        DateInterval $timestampValidationLeeway = new DateInterval('PT1M'),
        int $maxTrustChainDepth = 9,
        ?CacheInterface $cache = null,
        protected readonly ?LoggerInterface $logger = null,
        ?Client $client = null,
        protected readonly Helpers $helpers = new Helpers(),
        AlgorithmManagerFactory $algorithmManagerFactory = new AlgorithmManagerFactory(),
        JwsSerializerManagerFactory $jwsSerializerManagerFactory = new JwsSerializerManagerFactory(),
        JwsParserFactory $jwsParserFactory = new JwsParserFactory(),
        JwsVerifierFactory $jwsVerifierFactory = new JwsVerifierFactory(),
        protected ?EntityStatementFactory $entityStatementFactory = null,
        protected JwksFactory $jwksFactory = new JwksFactory(),
        protected ?TrustChainFactory $trustChainFactory = null,
        protected ?RequestObjectFactory $requestObjectFactory = null,
        protected ?MetadataPolicyResolver $metadataPolicyResolver = null,
        protected ?TrustMarkFactory $trustMarkFactory = null,
        protected ?EntityStatementFetcher $entityStatementFetcher = null,
        protected ?TrustChainResolver $trustChainResolver = null,
        DateIntervalDecoratorFactory $dateIntervalDecoratorFactory = new DateIntervalDecoratorFactory(),
        CacheDecoratorFactory $cacheDecoratorFactory = new CacheDecoratorFactory(),
        HttpClientDecoratorFactory $httpClientDecoratorFactory = new HttpClientDecoratorFactory(),
    ) {
        $this->maxCacheDuration = $dateIntervalDecoratorFactory->build($maxCacheDuration);
        $this->timestampValidationLeeway = $dateIntervalDecoratorFactory->build($timestampValidationLeeway);
        $this->maxTrustChainDepth = min(20, max(1, $maxTrustChainDepth));
        $this->cacheDecorator = is_null($cache) ? null : $cacheDecoratorFactory->build($cache);
        $this->httpClientDecorator = $httpClientDecoratorFactory->build($client);

        $this->jwsSerializerManager = $jwsSerializerManagerFactory->build($this->supportedSerializers);
        $this->jwsParser = $jwsParserFactory->build($this->jwsSerializerManager);
        $this->jwsVerifier = $jwsVerifierFactory->build($algorithmManagerFactory->build($this->supportedAlgorithms));
    }

    public function entityStatementFetcher(): EntityStatementFetcher
    {
        return $this->entityStatementFetcher ??= new EntityStatementFetcher(
            $this->httpClientDecorator,
            $this->entityStatementFactory(),
            $this->maxCacheDuration,
            $this->cacheDecorator,
            $this->logger,
            $this->helpers,
        );
    }

    public function metadataPolicyResolver(): MetadataPolicyResolver
    {
        return $this->metadataPolicyResolver ??= new MetadataPolicyResolver($this->helpers);
    }

    public function trustChainFactory(): TrustChainFactory
    {
        return $this->trustChainFactory ??= new TrustChainFactory(
            $this->entityStatementFactory(),
            $this->timestampValidationLeeway,
            $this->helpers,
            $this->metadataPolicyResolver(),
        );
    }

    public function trustChainResolver(): TrustChainResolver
    {
        return $this->trustChainResolver ??= new TrustChainResolver(
            $this->entityStatementFetcher(),
            $this->trustChainFactory(),
            $this->maxCacheDuration,
            $this->cacheDecorator,
            $this->logger,
            $this->maxTrustChainDepth,
        );
    }

    public function entityStatementFactory(): EntityStatementFactory
    {
        return $this->entityStatementFactory ??= new EntityStatementFactory(
            $this->jwsParser,
            $this->jwsVerifier,
            $this->jwksFactory,
            $this->jwsSerializerManager,
            $this->timestampValidationLeeway,
        );
    }

    public function requestObjectFactory(): RequestObjectFactory
    {
        return $this->requestObjectFactory ??= new RequestObjectFactory(
            $this->jwsParser,
            $this->jwsVerifier,
            $this->jwksFactory,
            $this->jwsSerializerManager,
            $this->timestampValidationLeeway,
        );
    }

    public function trustMarkFactory(): TrustMarkFactory
    {
        return $this->trustMarkFactory ??= new TrustMarkFactory(
            $this->jwsParser,
            $this->jwsVerifier,
            $this->jwksFactory,
            $this->jwsSerializerManager,
            $this->timestampValidationLeeway,
        );
    }
}
