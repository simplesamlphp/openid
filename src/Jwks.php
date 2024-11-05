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
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jwks\Factories\SignedJwksFactory;
use SimpleSAML\OpenID\Jwks\JwksFetcher;
use SimpleSAML\OpenID\Jws\Factories\JwsParserFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierFactory;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Jws\JwsVerifier;
use SimpleSAML\OpenID\Serializers\JwsSerializerManager;

class Jwks
{
    protected DateIntervalDecorator $maxCacheDuration;
    protected DateIntervalDecorator $timestampValidationLeeway;
    protected ?CacheDecorator $cacheDecorator;
    protected ?JwksFetcher $jwksFetcher = null;
    protected HttpClientDecorator $httpClientDecorator;
    protected JwsSerializerManager $jwsSerializerManager;
    protected JwsParser $jwsParser;
    protected JwsVerifier $jwsVerifier;
    protected ?JwksFactory $jwksFactory = null;
    protected ?SignedJwksFactory $signedJwksFactory = null;

    public function __construct(
        protected readonly SupportedAlgorithms $supportedAlgorithms = new SupportedAlgorithms(),
        protected readonly SupportedSerializers $supportedSerializers = new SupportedSerializers(),
        DateInterval $maxCacheDuration = new DateInterval('PT1H'),
        CacheInterface $cache = null,
        Client $httpClient = null,
        protected readonly ?LoggerInterface $logger = null,
        protected readonly Helpers $helpers = new Helpers(),
        AlgorithmManagerFactory $algorithmManagerFactory = new AlgorithmManagerFactory(),
        JwsSerializerManagerFactory $jwsSerializerManagerFactory = new JwsSerializerManagerFactory(),
        JwsParserFactory $jwsParserFactory = new JwsParserFactory(),
        JwsVerifierFactory $jwsVerifierFactory = new JwsVerifierFactory(),
        DateInterval $timestampValidationLeeway = new DateInterval('PT1M'),
        DateIntervalDecoratorFactory $dateIntervalDecoratorFactory = new DateIntervalDecoratorFactory(),
        CacheDecoratorFactory $cacheDecoratorFactory = new CacheDecoratorFactory(),
        HttpClientDecoratorFactory $httpClientDecoratorFactory = new HttpClientDecoratorFactory(),
    ) {
        $this->maxCacheDuration = $dateIntervalDecoratorFactory->build($maxCacheDuration);
        $this->timestampValidationLeeway = $dateIntervalDecoratorFactory->build($timestampValidationLeeway);
        $this->cacheDecorator = is_null($cache) ? null : $cacheDecoratorFactory->build($cache);
        $this->httpClientDecorator = $httpClientDecoratorFactory->build($httpClient);

        $this->jwsSerializerManager = $jwsSerializerManagerFactory->build($this->supportedSerializers);
        $this->jwsParser = $jwsParserFactory->build($this->jwsSerializerManager);
        $this->jwsVerifier = $jwsVerifierFactory->build($algorithmManagerFactory->build($this->supportedAlgorithms));
    }

    public function jwksFactory(): JwksFactory
    {
        return $this->jwksFactory ??= new JwksFactory();
    }

    public function signedJwksFactory(): SignedJwksFactory
    {
        return $this->signedJwksFactory ??= new SignedJwksFactory(
            $this->jwsParser,
            $this->jwsVerifier,
            $this->jwksFactory(),
            $this->jwsSerializerManager,
            $this->timestampValidationLeeway,
            $this->helpers,
        );
    }

    public function jwksFetcher(): JwksFetcher
    {
        return $this->jwksFetcher ??= new JwksFetcher(
            $this->httpClientDecorator,
            $this->jwksFactory(),
            $this->signedJwksFactory(),
            $this->maxCacheDuration,
            $this->cacheDecorator,
            $this->logger,
            $this->helpers,
        );
    }
}
