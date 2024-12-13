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
use SimpleSAML\OpenID\Federation\EntityStatement\Factories\TrustMarkClaimBagFactory;
use SimpleSAML\OpenID\Federation\EntityStatement\Factories\TrustMarkClaimFactory;
use SimpleSAML\OpenID\Federation\EntityStatementFetcher;
use SimpleSAML\OpenID\Federation\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Federation\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustChainBagFactory;
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
    protected DateIntervalDecorator $maxCacheDurationDecorator;
    protected DateIntervalDecorator $timestampValidationLeewayDecorator;
    protected int $maxTrustChainDepth;
    protected ?CacheDecorator $cacheDecorator;
    protected HttpClientDecorator $httpClientDecorator;
    protected ?JwsSerializerManager $jwsSerializerManager = null;
    protected ?JwsParser $jwsParser = null;
    protected ?JwsVerifier $jwsVerifier  = null;
    protected ?EntityStatementFetcher $entityStatementFetcher = null;
    protected ?MetadataPolicyResolver $metadataPolicyResolver = null;
    protected ?TrustChainFactory $trustChainFactory = null;
    protected ?TrustChainResolver $trustChainResolver = null;
    protected ?EntityStatementFactory $entityStatementFactory = null;
    protected ?RequestObjectFactory $requestObjectFactory = null;
    protected ?TrustMarkFactory $trustMarkFactory = null;
    protected ?TrustMarkClaimFactory $trustMarkClaimFactory = null;
    protected ?TrustMarkClaimBagFactory $trustMarkClaimBagFactory = null;
    protected ?Helpers $helpers = null;
    protected ?AlgorithmManagerFactory $algorithmManagerFactory = null;
    protected ?JwsSerializerManagerFactory $jwsSerializerManagerFactory = null;
    protected ?JwsParserFactory $jwsParserFactory = null;
    protected ?JwsVerifierFactory $jwsVerifierFactory = null;
    protected ?JwksFactory $jwksFactory = null;
    protected ?DateIntervalDecoratorFactory $dateIntervalDecoratorFactory = null;
    protected ?HttpClientDecoratorFactory $httpClientDecoratorFactory = null;
    protected ?TrustChainBagFactory $trustChainBagFactory = null;
    protected ?CacheDecoratorFactory $cacheDecoratorFactory = null;

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

    public function jwsVerifier(): JwsVerifier
    {
        return $this->jwsVerifier ??= $this->jwsVerifierFactory()->build(
            $this->algorithmManagerFactory()->build($this->supportedAlgorithms),
        );
    }

    public function jwsParser(): JwsParser
    {
        return $this->jwsParser ??= $this->jwsParserFactory()->build($this->jwsSerializerManager());
    }

    public function jwsSerializerManager(): JwsSerializerManager
    {
        return $this->jwsSerializerManager ??= $this->jwsSerializerManagerFactory()->build($this->supportedSerializers);
    }

    public function entityStatementFetcher(): EntityStatementFetcher
    {
        return $this->entityStatementFetcher ??= new EntityStatementFetcher(
            $this->httpClientDecorator,
            $this->entityStatementFactory(),
            $this->maxCacheDurationDecorator,
            $this->helpers(),
            $this->cacheDecorator,
            $this->logger,
        );
    }

    public function metadataPolicyResolver(): MetadataPolicyResolver
    {
        return $this->metadataPolicyResolver ??= new MetadataPolicyResolver($this->helpers());
    }

    public function trustChainFactory(): TrustChainFactory
    {
        return $this->trustChainFactory ??= new TrustChainFactory(
            $this->entityStatementFactory(),
            $this->timestampValidationLeewayDecorator,
            $this->helpers(),
            $this->metadataPolicyResolver(),
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
            $this->jwsParser(),
            $this->jwsVerifier(),
            $this->jwksFactory(),
            $this->jwsSerializerManager(),
            $this->timestampValidationLeewayDecorator,
            $this->helpers(),
            $this->trustMarkClaimFactory(),
            $this->trustMarkClaimBagFactory(),
        );
    }

    public function requestObjectFactory(): RequestObjectFactory
    {
        return $this->requestObjectFactory ??= new RequestObjectFactory(
            $this->jwsParser(),
            $this->jwsVerifier(),
            $this->jwksFactory(),
            $this->jwsSerializerManager(),
            $this->timestampValidationLeewayDecorator,
            $this->helpers(),
        );
    }

    public function trustMarkFactory(): TrustMarkFactory
    {
        return $this->trustMarkFactory ??= new TrustMarkFactory(
            $this->jwsParser(),
            $this->jwsVerifier(),
            $this->jwksFactory(),
            $this->jwsSerializerManager(),
            $this->timestampValidationLeewayDecorator,
            $this->helpers(),
        );
    }

    public function trustMarkClaimFactory(): TrustMarkClaimFactory
    {
        return $this->trustMarkClaimFactory ??= new TrustMarkClaimFactory($this->trustMarkFactory());
    }

    public function helpers(): Helpers
    {
        return $this->helpers ??= new Helpers();
    }

    public function algorithmManagerFactory(): AlgorithmManagerFactory
    {
        return $this->algorithmManagerFactory ??= new AlgorithmManagerFactory();
    }

    public function jwsSerializerManagerFactory(): JwsSerializerManagerFactory
    {
        return $this->jwsSerializerManagerFactory ??= new JwsSerializerManagerFactory();
    }

    public function jwsParserFactory(): JwsParserFactory
    {
        return $this->jwsParserFactory ??= new JwsParserFactory();
    }

    public function jwsVerifierFactory(): JwsVerifierFactory
    {
        return $this->jwsVerifierFactory ??= new JwsVerifierFactory();
    }

    public function jwksFactory(): JwksFactory
    {
        return $this->jwksFactory ??= new JwksFactory();
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

    public function trustMarkClaimBagFactory(): TrustMarkClaimBagFactory
    {
        return $this->trustMarkClaimBagFactory ??= new TrustMarkClaimBagFactory();
    }

    public function maxCacheDurationDecorator(): DateIntervalDecorator
    {
        return $this->maxCacheDurationDecorator;
    }

    public function supportedAlgorithms(): SupportedAlgorithms
    {
        return $this->supportedAlgorithms;
    }

    /**
     * @return \SimpleSAML\OpenID\SupportedSerializers
     */
    public function supportedSerializers(): SupportedSerializers
    {
        return $this->supportedSerializers;
    }

    public function maxTrustChainDepth(): int
    {
        return $this->maxTrustChainDepth;
    }
}
