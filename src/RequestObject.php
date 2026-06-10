<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use DateInterval;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Core\Factories\RequestObjectFactory as ConnectRequestObjectFactory;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Factories\AlgorithmManagerDecoratorFactory;
use SimpleSAML\OpenID\Factories\CacheDecoratorFactory;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\HttpClientDecoratorFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerDecoratorFactory;
use SimpleSAML\OpenID\Federation\Factories\RequestObjectFactory as FederationRequestObjectFactory;
use SimpleSAML\OpenID\Jar\Factories\RequestObjectFactory as JarRequestObjectFactory;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsDecoratorBuilderFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\RequestObject\RequestObjectFactories;
use SimpleSAML\OpenID\RequestObject\RequestObjectParser;
use SimpleSAML\OpenID\RequestObject\RequestUriFetcher;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

class RequestObject
{
    protected DateIntervalDecorator $timestampValidationLeewayDecorator;

    protected ?DateIntervalDecoratorFactory $dateIntervalDecoratorFactory = null;

    protected HttpClientDecorator $httpClientDecorator;

    protected ?HttpClientDecoratorFactory $httpClientDecoratorFactory = null;

    protected ?CacheDecoratorFactory $cacheDecoratorFactory = null;

    protected ?CacheDecorator $cacheDecorator;

    protected ?RequestObjectFactories $requestObjectFactories = null;

    protected ?RequestObjectParser $requestObjectParser = null;

    protected ?ConnectRequestObjectFactory $connectRequestObjectFactory = null;

    protected ?JarRequestObjectFactory $jarRequestObjectFactory = null;

    protected ?FederationRequestObjectFactory $federationRequestObjectFactory = null;

    protected ?AlgorithmManagerDecoratorFactory $algorithmManagerDecoratorFactory = null;

    protected ?AlgorithmManagerDecorator $algorithmManagerDecorator = null;

    protected ?JwsSerializerManagerDecoratorFactory $jwsSerializerManagerDecoratorFactory = null;

    protected ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null;

    protected ?JwsDecoratorBuilderFactory $jwsDecoratorBuilderFactory = null;

    protected ?JwsDecoratorBuilder $jwsDecoratorBuilder = null;

    protected ?JwsVerifierDecoratorFactory $jwsVerifierDecoratorFactory = null;

    protected ?JwsVerifierDecorator $jwsVerifierDecorator = null;

    protected ?JwksDecoratorFactory $jwksDecoratorFactory = null;

    protected ?ClaimFactory $claimFactory = null;

    protected ?Helpers $helpers = null;


    public function __construct(
        protected readonly SupportedAlgorithms $supportedAlgorithms = new SupportedAlgorithms(
            new SignatureAlgorithmBag(
                SignatureAlgorithmEnum::none,
                SignatureAlgorithmEnum::RS256,
            ),
        ),
        protected readonly SupportedSerializers $supportedSerializers = new SupportedSerializers(),
        DateInterval $timestampValidationLeeway = new DateInterval('PT1M'),
        protected readonly ?LoggerInterface $logger = null,
        protected ?ArtifactFetcher $artifactFetcher = null,
        protected ?RequestUriFetcher $requestUriFetcher = null,
        ?Client $client = null,
        ?CacheInterface $cache = null,
    ) {
        $this->timestampValidationLeewayDecorator = $this->dateIntervalDecoratorFactory()
            ->build($timestampValidationLeeway);
        $this->httpClientDecorator = $this->httpClientDecoratorFactory()->build($client);
        $this->cacheDecorator = is_null($cache) ? null : $this->cacheDecoratorFactory()->build($cache);
    }


    public function dateIntervalDecoratorFactory(): DateIntervalDecoratorFactory
    {
        return $this->dateIntervalDecoratorFactory ??= new DateIntervalDecoratorFactory();
    }


    public function artifactFetcher(): ArtifactFetcher
    {
        return $this->artifactFetcher ??= new ArtifactFetcher(
            $this->httpClientDecorator,
            $this->cacheDecorator(),
            $this->logger,
        );
    }


    public function requestUriFetcher(): RequestUriFetcher
    {
        return $this->requestUriFetcher ??= new RequestUriFetcher(
            $this->artifactFetcher(),
        );
    }


    public function httpClientDecoratorFactory(): HttpClientDecoratorFactory
    {
        return $this->httpClientDecoratorFactory ??= new HttpClientDecoratorFactory();
    }


    public function cacheDecoratorFactory(): CacheDecoratorFactory
    {
        return $this->cacheDecoratorFactory ??= new CacheDecoratorFactory();
    }


    public function cacheDecorator(): ?CacheDecorator
    {
        return $this->cacheDecorator;
    }


    public function requestObjectFactories(): RequestObjectFactories
    {
        return $this->requestObjectFactories ??= new RequestObjectFactories(
            $this->connectRequestObjectFactory(),
            $this->jarRequestObjectFactory(),
            $this->federationRequestObjectFactory(),
        );
    }


    public function requestObjectParser(): RequestObjectParser
    {
        return $this->requestObjectParser ??= new RequestObjectParser(
            $this->requestObjectFactories(),
            $this->requestUriFetcher(),
            $this->logger,
        );
    }


    public function connectRequestObjectFactory(): ConnectRequestObjectFactory
    {
        return $this->connectRequestObjectFactory ??= new ConnectRequestObjectFactory(
            $this->jwsDecoratorBuilder(),
            $this->jwsVerifierDecorator(),
            $this->jwksDecoratorFactory(),
            $this->jwsSerializerManagerDecorator(),
            $this->timestampValidationLeewayDecorator(),
            $this->helpers(),
            $this->claimFactory(),
        );
    }


    public function jarRequestObjectFactory(): JarRequestObjectFactory
    {
        return $this->jarRequestObjectFactory ??= new JarRequestObjectFactory(
            $this->jwsDecoratorBuilder(),
            $this->jwsVerifierDecorator(),
            $this->jwksDecoratorFactory(),
            $this->jwsSerializerManagerDecorator(),
            $this->timestampValidationLeewayDecorator(),
            $this->helpers(),
            $this->claimFactory(),
        );
    }


    public function federationRequestObjectFactory(): FederationRequestObjectFactory
    {
        return $this->federationRequestObjectFactory ??= new FederationRequestObjectFactory(
            $this->jwsDecoratorBuilder(),
            $this->jwsVerifierDecorator(),
            $this->jwksDecoratorFactory(),
            $this->jwsSerializerManagerDecorator(),
            $this->timestampValidationLeewayDecorator(),
            $this->helpers(),
            $this->claimFactory(),
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


    public function jwsVerifierDecorator(): JwsVerifierDecorator
    {
        return $this->jwsVerifierDecorator ??= $this->jwsVerifierDecoratorFactory()->build(
            $this->algorithmManagerDecorator(),
        );
    }


    public function jwsSerializerManagerDecorator(): JwsSerializerManagerDecorator
    {
        return $this->jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorFactory()
            ->build($this->supportedSerializers);
    }


    public function algorithmManagerDecorator(): AlgorithmManagerDecorator
    {
        return $this->algorithmManagerDecorator ??= $this->algorithmManagerDecoratorFactory()
            ->build($this->supportedAlgorithms);
    }


    public function algorithmManagerDecoratorFactory(): AlgorithmManagerDecoratorFactory
    {
        return $this->algorithmManagerDecoratorFactory ??= new AlgorithmManagerDecoratorFactory();
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


    public function claimFactory(): ClaimFactory
    {
        return $this->claimFactory ??= new ClaimFactory(
            $this->helpers(),
        );
    }


    public function helpers(): Helpers
    {
        return $this->helpers ??= new Helpers();
    }


    public function timestampValidationLeewayDecorator(): DateIntervalDecorator
    {
        return $this->timestampValidationLeewayDecorator;
    }
}
