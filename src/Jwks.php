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
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jwks\JwksFetcher;

class Jwks
{
    protected DateIntervalDecorator $maxCacheDuration;
    protected ?CacheDecorator $cacheDecorator;
    protected ?JwksFetcher $jwksFetcher = null;
    protected HttpClientDecorator $httpClientDecorator;

    public function __construct(
        DateInterval $maxCacheDuration = new DateInterval('PT1H'),
        CacheInterface $cache = null,
        Client $httpClient = null,
        protected readonly JwksFactory $jwksFactory = new JwksFactory(),
        protected readonly ?LoggerInterface $logger = null,
        protected readonly Helpers $helpers = new Helpers(),
    ) {
        $this->maxCacheDuration = new DateIntervalDecorator($maxCacheDuration);
        $this->cacheDecorator = is_null($cache) ? null : new CacheDecorator($cache);
        $this->httpClientDecorator = new HttpClientDecorator($httpClient);
    }

    public function getJwksFetcher(): JwksFetcher
    {
        return $this->jwksFetcher ??= new JwksFetcher(
            $this->httpClientDecorator,
            $this->jwksFactory,
            $this->maxCacheDuration,
            $this->cacheDecorator,
            $this->logger,
            $this->helpers,
        );
    }
}
