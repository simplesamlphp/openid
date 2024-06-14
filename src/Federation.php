<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\OpenID\Federation\EntityStatementFetcher;

class Federation
{
    private static ?EntityStatementFetcher $entityStatementFetcher = null;

    public function __construct(
        private readonly \DateInterval $maxEntityStatementCacheDuration = new \DateInterval('PT6H'),
        private readonly ?CacheInterface $cache = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ClientInterface $httpClient = new Client(),
        private readonly RequestFactoryInterface $requestFactory = new HttpFactory(),
        private readonly Helpers $helpers = new Helpers(),
    ) {
    }

    public function entityStatementFetcher(): EntityStatementFetcher
    {
        return self::$entityStatementFetcher ??= new EntityStatementFetcher(
            $this->maxEntityStatementCacheDuration,
            $this->cache,
            $this->logger,
            $this->httpClient,
            $this->requestFactory,
            $this->helpers,
        );
    }
}
