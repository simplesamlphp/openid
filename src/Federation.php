<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use DateInterval;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\OpenID\Factories\AlgorithmManagerFactory;
use SimpleSAML\OpenID\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Factories\JwksFactory;
use SimpleSAML\OpenID\Factories\JwsParserFactory;
use SimpleSAML\OpenID\Factories\JwsVerifierFactory;
use SimpleSAML\OpenID\Factories\TrustChainFactory;
use SimpleSAML\OpenID\Federation\EntityStatementFetcher;
use SimpleSAML\OpenID\Federation\TrustChainFetcher;

class Federation
{
    protected const DEFAULT_HTTP_CLIENT_CONFIG = [RequestOptions::ALLOW_REDIRECTS => true,];
    protected static ?EntityStatementFetcher $entityStatementFetcher = null;
    protected static ?TrustChainFetcher $trustChainFetcher = null;
    protected EntityStatementFactory $entityStatementFactory;

    public function __construct(
        protected readonly SupportedAlgorithms $supportedAlgorithms = new SupportedAlgorithms(),
        protected readonly DateInterval $maxEntityStatementCacheDuration = new DateInterval('PT6H'),
        protected readonly ?CacheInterface $cache = null,
        protected readonly ?LoggerInterface $logger = null,
        protected readonly Client $httpClient = new Client(self::DEFAULT_HTTP_CLIENT_CONFIG),
        protected readonly RequestFactoryInterface $requestFactory = new HttpFactory(),
        protected readonly Helpers $helpers = new Helpers(),
        AlgorithmManagerFactory $algorithmManagerFactory = new AlgorithmManagerFactory(),
        JwsParserFactory $jwsParserFactory = new JwsParserFactory(),
        JwsVerifierFactory $jwsVerifierFactory = new JwsVerifierFactory(),
        EntityStatementFactory $entityStatementFactory = null,
        JwksFactory $jwksFactory = new JwksFactory(),
        protected TrustChainFactory $trustChainFactory = new TrustChainFactory(),
    ) {
        $this->entityStatementFactory = $entityStatementFactory ?? new EntityStatementFactory(
            $jwsParserFactory->build(),
            $jwsVerifierFactory->build($algorithmManagerFactory->build($this->supportedAlgorithms)),
            $jwksFactory,
        );
    }

    public function entityStatementFetcher(): EntityStatementFetcher
    {
        return self::$entityStatementFetcher ??= new EntityStatementFetcher(
            $this->httpClient,
            $this->maxEntityStatementCacheDuration,
            $this->cache,
            $this->logger,
            $this->helpers,
            $this->entityStatementFactory,
        );
    }

    public function trustChainFetcher(): TrustChainFetcher
    {
        return self::$trustChainFetcher ??= new TrustChainFetcher(
            $this->entityStatementFetcher(),
            $this->trustChainFactory,
            $this->logger,
        );
    }
}
