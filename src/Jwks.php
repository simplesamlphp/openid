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
    protected DateIntervalDecorator $maxCacheDurationDecorator;
    protected DateIntervalDecorator $timestampValidationLeewayDecorator;
    protected ?CacheDecorator $cacheDecorator;
    protected ?JwksFetcher $jwksFetcher = null;
    protected HttpClientDecorator $httpClientDecorator;
    protected ?JwsSerializerManager $jwsSerializerManager = null;
    protected ?JwsParser $jwsParser = null;
    protected ?JwsVerifier $jwsVerifier  = null;
    protected ?JwksFactory $jwksFactory = null;
    protected ?SignedJwksFactory $signedJwksFactory = null;
    protected ?Helpers $helpers = null;
    protected ?AlgorithmManagerFactory $algorithmManagerFactory = null;
    protected ?JwsSerializerManagerFactory $jwsSerializerManagerFactory = null;
    protected ?JwsParserFactory $jwsParserFactory = null;
    protected ?JwsVerifierFactory $jwsVerifierFactory = null;
    protected ?DateIntervalDecoratorFactory $dateIntervalDecoratorFactory = null;
    protected ?CacheDecoratorFactory $cacheDecoratorFactory = null;
    protected ?HttpClientDecoratorFactory $httpClientDecoratorFactory = null;

    public function __construct(
        protected readonly SupportedAlgorithms $supportedAlgorithms = new SupportedAlgorithms(),
        protected readonly SupportedSerializers $supportedSerializers = new SupportedSerializers(),
        DateInterval $maxCacheDuration = new DateInterval('PT1H'),
        DateInterval $timestampValidationLeeway = new DateInterval('PT1M'),
        ?CacheInterface $cache = null,
        protected readonly ?LoggerInterface $logger = null,
        ?Client $httpClient = null,
    ) {
        $this->maxCacheDurationDecorator = $this->dateIntervalDecoratorFactory()->build($maxCacheDuration);
        $this->timestampValidationLeewayDecorator = $this->dateIntervalDecoratorFactory()
            ->build($timestampValidationLeeway);
        $this->cacheDecorator = is_null($cache) ? null : $this->cacheDecoratorFactory()->build($cache);
        $this->httpClientDecorator = $this->httpClientDecoratorFactory()->build($httpClient);
    }

    public function jwksFactory(): JwksFactory
    {
        return $this->jwksFactory ??= new JwksFactory();
    }

    public function signedJwksFactory(): SignedJwksFactory
    {
        return $this->signedJwksFactory ??= new SignedJwksFactory(
            $this->jwsParser(),
            $this->jwsVerifier(),
            $this->jwksFactory(),
            $this->jwsSerializerManager(),
            $this->timestampValidationLeewayDecorator,
            $this->helpers(),
        );
    }

    public function jwksFetcher(): JwksFetcher
    {
        return $this->jwksFetcher ??= new JwksFetcher(
            $this->httpClientDecorator,
            $this->jwksFactory(),
            $this->signedJwksFactory(),
            $this->maxCacheDurationDecorator,
            $this->cacheDecorator,
            $this->logger,
            $this->helpers(),
        );
    }

    public function helpers(): Helpers
    {
        return $this->helpers ??= new Helpers();
    }

    public function algorithmManagerFactory(): AlgorithmManagerFactory
    {
        if (is_null($this->algorithmManagerFactory)) {
            $this->algorithmManagerFactory = new AlgorithmManagerFactory();
        }
        return $this->algorithmManagerFactory;
    }

    public function jwsSerializerManagerFactory(): JwsSerializerManagerFactory
    {
        if (is_null($this->jwsSerializerManagerFactory)) {
            $this->jwsSerializerManagerFactory = new JwsSerializerManagerFactory();
        }
        return $this->jwsSerializerManagerFactory;
    }

    public function jwsParserFactory(): JwsParserFactory
    {
        if (is_null($this->jwsParserFactory)) {
            $this->jwsParserFactory = new JwsParserFactory();
        }
        return $this->jwsParserFactory;
    }

    public function jwsVerifierFactory(): JwsVerifierFactory
    {
        if (is_null($this->jwsVerifierFactory)) {
            $this->jwsVerifierFactory = new JwsVerifierFactory();
        }
        return $this->jwsVerifierFactory;
    }

    public function dateIntervalDecoratorFactory(): DateIntervalDecoratorFactory
    {
        if (is_null($this->dateIntervalDecoratorFactory)) {
            $this->dateIntervalDecoratorFactory = new DateIntervalDecoratorFactory();
        }

        return $this->dateIntervalDecoratorFactory;
    }

    public function cacheDecoratorFactory(): CacheDecoratorFactory
    {
        if (is_null($this->cacheDecoratorFactory)) {
            $this->cacheDecoratorFactory = new CacheDecoratorFactory();
        }

        return $this->cacheDecoratorFactory;
    }

    public function httpClientDecoratorFactory(): HttpClientDecoratorFactory
    {
        if (is_null($this->httpClientDecoratorFactory)) {
            $this->httpClientDecoratorFactory = new HttpClientDecoratorFactory();
        }

        return $this->httpClientDecoratorFactory;
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
}
