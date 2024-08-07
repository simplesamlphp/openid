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
use SimpleSAML\OpenID\Factories\AlgorithmManagerFactory;
use SimpleSAML\OpenID\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Factories\JwksFactory;
use SimpleSAML\OpenID\Factories\JwsParserFactory;
use SimpleSAML\OpenID\Factories\JwsVerifierFactory;
use SimpleSAML\OpenID\Factories\TrustChainFactory;
use SimpleSAML\OpenID\Federation\EntityStatementFetcher;
use SimpleSAML\OpenID\Federation\TrustChainResolver;

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

    public function __construct(
        protected readonly SupportedAlgorithms $supportedAlgorithms = new SupportedAlgorithms(),
        DateInterval $maxCacheDuration = new DateInterval('PT6H'),
        DateInterval $timestampValidationLeeway = new DateInterval('PT1M'),
        int $maxTrustChainDepth = 9,
        CacheInterface $cache = null,
        protected readonly ?LoggerInterface $logger = null,
        protected readonly Client $httpClient = new Client(self::DEFAULT_HTTP_CLIENT_CONFIG),
        protected readonly Helpers $helpers = new Helpers(),
        AlgorithmManagerFactory $algorithmManagerFactory = new AlgorithmManagerFactory(),
        JwsParserFactory $jwsParserFactory = new JwsParserFactory(),
        JwsVerifierFactory $jwsVerifierFactory = new JwsVerifierFactory(),
        EntityStatementFactory $entityStatementFactory = null,
        JwksFactory $jwksFactory = new JwksFactory(),
        TrustChainFactory $trustChainFactory = null,
    ) {
        $this->maxCacheDuration = new DateIntervalDecorator($maxCacheDuration);
        $this->timestampValidationLeeway = new DateIntervalDecorator($timestampValidationLeeway);
        $this->maxTrustChainDepth = min(20, max(1, $maxTrustChainDepth));
        $this->cacheDecorator = is_null($cache) ? null : new CacheDecorator($cache);

        $this->entityStatementFactory = $entityStatementFactory ?? new EntityStatementFactory(
            $jwsParserFactory->build(),
            $jwsVerifierFactory->build($algorithmManagerFactory->build($this->supportedAlgorithms)),
            $jwksFactory,
            $this->timestampValidationLeeway,
        );

        $this->trustChainFactory = $trustChainFactory ?? new TrustChainFactory(
            $this->entityStatementFactory,
            $this->timestampValidationLeeway,
        );
    }

    public function entityStatementFetcher(): EntityStatementFetcher
    {
        return self::$entityStatementFetcher ??= new EntityStatementFetcher(
            $this->httpClient,
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
}
