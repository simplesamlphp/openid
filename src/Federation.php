<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use DateInterval;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Factories\AlgorithmManagerFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerFactory;
use SimpleSAML\OpenID\Federation\EntityStatementFetcher;
use SimpleSAML\OpenID\Federation\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Federation\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustChainFactory;
use SimpleSAML\OpenID\Federation\MetadataPolicyResolver;
use SimpleSAML\OpenID\Federation\TrustChainResolver;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsParserFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierFactory;

class Federation
{
    protected const DEFAULT_HTTP_CLIENT_CONFIG = [RequestOptions::ALLOW_REDIRECTS => true,];
    protected static ?EntityStatementFetcher $entityStatementFetcher = null;
    protected static ?TrustChainResolver $trustChainResolver = null;
    protected EntityStatementFactory $entityStatementFactory;
    protected TrustChainFactory $trustChainFactory;
    protected DateIntervalDecorator $maxCacheDuration;
    protected DateIntervalDecorator $timestampValidationLeeway;
    protected int $maxTrustChainDepth;
    protected ?CacheDecorator $cacheDecorator;
    protected RequestObjectFactory $requestObjectFactory;
    protected HttpClientDecorator $httpClientDecorator;
    protected MetadataPolicyResolver $metadataPolicyResolver;

    public function __construct(
        protected readonly SupportedAlgorithms $supportedAlgorithms = new SupportedAlgorithms(),
        protected readonly SupportedSerializers $supportedSerializers = new SupportedSerializers(),
        DateInterval $maxCacheDuration = new DateInterval('PT6H'),
        DateInterval $timestampValidationLeeway = new DateInterval('PT1M'),
        int $maxTrustChainDepth = 9,
        CacheInterface $cache = null,
        protected readonly ?LoggerInterface $logger = null,
        Client $client = null,
        protected readonly Helpers $helpers = new Helpers(),
        AlgorithmManagerFactory $algorithmManagerFactory = new AlgorithmManagerFactory(),
        JwsSerializerManagerFactory $jwsSerializerManagerFactory = new JwsSerializerManagerFactory(),
        JwsParserFactory $jwsParserFactory = new JwsParserFactory(),
        JwsVerifierFactory $jwsVerifierFactory = new JwsVerifierFactory(),
        EntityStatementFactory $entityStatementFactory = null,
        JwksFactory $jwksFactory = new JwksFactory(),
        TrustChainFactory $trustChainFactory = null,
        RequestObjectFactory $requestObjectFactory = null,
        MetadataPolicyResolver $metadataPolicyResolver = null,
    ) {
        $this->maxCacheDuration = new DateIntervalDecorator($maxCacheDuration);
        $this->timestampValidationLeeway = new DateIntervalDecorator($timestampValidationLeeway);
        $this->maxTrustChainDepth = min(20, max(1, $maxTrustChainDepth));
        $this->cacheDecorator = is_null($cache) ? null : new CacheDecorator($cache);
        $this->httpClientDecorator = new HttpClientDecorator($client);

        $jwsSerializerManager = $jwsSerializerManagerFactory->build($this->supportedSerializers);
        $jwsParser = $jwsParserFactory->build($jwsSerializerManager);
        $jwsVerifier = $jwsVerifierFactory->build($algorithmManagerFactory->build($this->supportedAlgorithms));

        $this->entityStatementFactory = $entityStatementFactory ?? new EntityStatementFactory(
            $jwsParser,
            $jwsVerifier,
            $jwksFactory,
            $jwsSerializerManager,
            $this->timestampValidationLeeway,
        );

        $this->requestObjectFactory = $requestObjectFactory ?? new RequestObjectFactory(
            $jwsParser,
            $jwsVerifier,
            $jwksFactory,
            $jwsSerializerManager,
            $this->timestampValidationLeeway,
        );

        $this->metadataPolicyResolver = $metadataPolicyResolver ?? new MetadataPolicyResolver($this->helpers);

        $this->trustChainFactory = $trustChainFactory ?? new TrustChainFactory(
            $this->entityStatementFactory,
            $this->timestampValidationLeeway,
            $this->helpers,
            $this->metadataPolicyResolver,
        );
    }

    public function entityStatementFetcher(): EntityStatementFetcher
    {
        return self::$entityStatementFetcher ??= new EntityStatementFetcher(
            $this->httpClientDecorator,
            $this->entityStatementFactory,
            $this->maxCacheDuration,
            $this->cacheDecorator,
            $this->logger,
            $this->helpers,
        );
    }

    public function trustChainResolver(): TrustChainResolver
    {
        return self::$trustChainResolver ??= new TrustChainResolver(
            $this->entityStatementFetcher(),
            $this->trustChainFactory,
            $this->maxCacheDuration,
            $this->cacheDecorator,
            $this->logger,
            $this->maxTrustChainDepth,
        );
    }

    public function entityStatementFactory(): EntityStatementFactory
    {
        return $this->entityStatementFactory;
    }

    public function requestObjectFactory(): RequestObjectFactory
    {
        return $this->requestObjectFactory;
    }
}
