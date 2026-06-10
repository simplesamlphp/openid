<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use DateInterval;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Factories\CacheDecoratorFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\HttpClientDecoratorFactory;
use SimpleSAML\OpenID\RequestObject\RequestUriFetcher;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

class RequestObject
{
    protected DateIntervalDecorator $timestampValidationLeewayDecorator;

    protected ?DateIntervalDecoratorFactory $dateIntervalDecoratorFactory = null;

    protected HttpClientDecorator $httpClientDecorator;

    protected ?HttpClientDecoratorFactory $httpClientDecoratorFactory = null;

    protected ?CacheDecoratorFactory $cacheDecoratorFactory = null;

    protected ?CacheDecorator $cacheDecorator;


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
}
