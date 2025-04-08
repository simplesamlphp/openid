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
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jwks\Factories\SignedJwksFactory;
use SimpleSAML\OpenID\Jwks\JwksFetcher;
use SimpleSAML\OpenID\Jws\Factories\JwsDecoratorBuilderFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

class Jwks
{
    protected DateIntervalDecorator $maxCacheDurationDecorator;

    protected DateIntervalDecorator $timestampValidationLeewayDecorator;

    protected ?CacheDecorator $cacheDecorator;

    protected ?JwksFetcher $jwksFetcher = null;

    protected HttpClientDecorator $httpClientDecorator;

    protected ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null;

    protected ?JwsDecoratorBuilder $jwsDecoratorBuilder = null;

    protected ?JwsVerifierDecorator $jwsVerifierDecorator  = null;

    protected ?JwksDecoratorFactory $jwksDecoratorFactory = null;

    protected ?SignedJwksFactory $signedJwksFactory = null;

    protected ?Helpers $helpers = null;

    protected ?AlgorithmManagerDecoratorFactory $algorithmManagerDecoratorFactory = null;

    protected ?JwsSerializerManagerDecoratorFactory $jwsSerializerManagerDecoratorFactory = null;

    protected ?JwsDecoratorBuilderFactory $jwsDecoratorBuilderFactory = null;

    protected ?JwsVerifierDecoratorFactory $jwsVerifierDecoratorFactory = null;

    protected ?DateIntervalDecoratorFactory $dateIntervalDecoratorFactory = null;

    protected ?CacheDecoratorFactory $cacheDecoratorFactory = null;

    protected ?HttpClientDecoratorFactory $httpClientDecoratorFactory = null;

    protected ?ClaimFactory $claimFactory = null;
    protected ?AlgorithmManagerDecorator $algorithmManagerDecorator = null;

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

    public function jwksDecoratorFactory(): JwksDecoratorFactory
    {
        return $this->jwksDecoratorFactory ??= new JwksDecoratorFactory();
    }

    public function signedJwksFactory(): SignedJwksFactory
    {
        return $this->signedJwksFactory ??= new SignedJwksFactory(
            $this->jwsDecoratorBuilder(),
            $this->jwsVerifierDecorator(),
            $this->jwksDecoratorFactory(),
            $this->jwsSerializerManagerDecorator(),
            $this->timestampValidationLeewayDecorator,
            $this->helpers(),
            $this->claimFactory(),
        );
    }

    public function jwksFetcher(): JwksFetcher
    {
        return $this->jwksFetcher ??= new JwksFetcher(
            $this->httpClientDecorator,
            $this->jwksDecoratorFactory(),
            $this->signedJwksFactory(),
            $this->maxCacheDurationDecorator,
            $this->helpers(),
            $this->claimFactory(),
            $this->cacheDecorator,
            $this->logger,
        );
    }

    public function helpers(): Helpers
    {
        return $this->helpers ??= new Helpers();
    }

    public function algorithmManagerDecoratorFactory(): AlgorithmManagerDecoratorFactory
    {
        if (is_null($this->algorithmManagerDecoratorFactory)) {
            $this->algorithmManagerDecoratorFactory = new AlgorithmManagerDecoratorFactory();
        }

        return $this->algorithmManagerDecoratorFactory;
    }

    public function algorithmManagerDecorator(): AlgorithmManagerDecorator
    {
        return $this->algorithmManagerDecorator ??= $this->algorithmManagerDecoratorFactory()->build(
            $this->supportedAlgorithms,
        );
    }

    public function jwsSerializerManagerDecoratorFactory(): JwsSerializerManagerDecoratorFactory
    {
        if (is_null($this->jwsSerializerManagerDecoratorFactory)) {
            $this->jwsSerializerManagerDecoratorFactory = new JwsSerializerManagerDecoratorFactory();
        }

        return $this->jwsSerializerManagerDecoratorFactory;
    }

    public function jwsDecoratorBuilderFactory(): JwsDecoratorBuilderFactory
    {
        if (is_null($this->jwsDecoratorBuilderFactory)) {
            $this->jwsDecoratorBuilderFactory = new JwsDecoratorBuilderFactory();
        }

        return $this->jwsDecoratorBuilderFactory;
    }

    public function jwsVerifierDecoratorFactory(): JwsVerifierDecoratorFactory
    {
        if (is_null($this->jwsVerifierDecoratorFactory)) {
            $this->jwsVerifierDecoratorFactory = new JwsVerifierDecoratorFactory();
        }

        return $this->jwsVerifierDecoratorFactory;
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

    public function jwsVerifierDecorator(): JwsVerifierDecorator
    {
        return $this->jwsVerifierDecorator ??= $this->jwsVerifierDecoratorFactory()->build(
            $this->algorithmManagerDecoratorFactory()->build($this->supportedAlgorithms),
        );
    }

    public function jwsDecoratorBuilder(): JwsDecoratorBuilder
    {
        return $this->jwsDecoratorBuilder ??= $this->jwsDecoratorBuilderFactory()->build(
            $this->jwsSerializerManagerDecorator(),
        );
    }

    public function jwsSerializerManagerDecorator(): JwsSerializerManagerDecorator
    {
        return $this->jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorFactory()
            ->build($this->supportedSerializers);
    }

    public function claimFactory(): ClaimFactory
    {
        return $this->claimFactory ??= new ClaimFactory(
            $this->helpers(),
        );
    }
}
