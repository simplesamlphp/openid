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
use SimpleSAML\OpenID\Factories\JwsSerializerManagerFactory;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jwks\Factories\SignedJwksFactory;
use SimpleSAML\OpenID\Jwks\JwksFetcher;
use SimpleSAML\OpenID\Jws\Factories\JwsParserFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierFactory;

class Jwks
{
    protected DateIntervalDecorator $maxCacheDuration;
    protected DateIntervalDecorator $timestampValidationLeeway;
    protected ?CacheDecorator $cacheDecorator;
    protected ?JwksFetcher $jwksFetcher = null;
    protected HttpClientDecorator $httpClientDecorator;
    protected SignedJwksFactory $signedJwksFactory;

    public function __construct(
        protected readonly SupportedAlgorithms $supportedAlgorithms = new SupportedAlgorithms(),
        protected readonly SupportedSerializers $supportedSerializers = new SupportedSerializers(),
        DateInterval $maxCacheDuration = new DateInterval('PT1H'),
        CacheInterface $cache = null,
        Client $httpClient = null,
        protected readonly JwksFactory $jwksFactory = new JwksFactory(),
        SignedJwksFactory $signedJwksFactory = null,
        protected readonly ?LoggerInterface $logger = null,
        protected readonly Helpers $helpers = new Helpers(),
        AlgorithmManagerFactory $algorithmManagerFactory = new AlgorithmManagerFactory(),
        JwsSerializerManagerFactory $jwsSerializerManagerFactory = new JwsSerializerManagerFactory(),
        JwsParserFactory $jwsParserFactory = new JwsParserFactory(),
        JwsVerifierFactory $jwsVerifierFactory = new JwsVerifierFactory(),
        DateInterval $timestampValidationLeeway = new DateInterval('PT1M'),
    ) {
        $this->maxCacheDuration = new DateIntervalDecorator($maxCacheDuration);
        $this->timestampValidationLeeway = new DateIntervalDecorator($timestampValidationLeeway);
        $this->cacheDecorator = is_null($cache) ? null : new CacheDecorator($cache);
        $this->httpClientDecorator = new HttpClientDecorator($httpClient);

        $jwsSerializerManager = $jwsSerializerManagerFactory->build($this->supportedSerializers);

        $this->signedJwksFactory = $signedJwksFactory ?? new SignedJwksFactory(
            $jwsParserFactory->build($jwsSerializerManager),
            $jwsVerifierFactory->build($algorithmManagerFactory->build($this->supportedAlgorithms)),
            $this->jwksFactory,
            $jwsSerializerManager,
            $this->timestampValidationLeeway,
        );
    }

    public function getJwksFetcher(): JwksFetcher
    {
        return $this->jwksFetcher ??= new JwksFetcher(
            $this->httpClientDecorator,
            $this->jwksFactory,
            $this->signedJwksFactory,
            $this->maxCacheDuration,
            $this->cacheDecorator,
            $this->logger,
            $this->helpers,
        );
    }
}
