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
use SimpleSAML\OpenID\Jws\Factories\JwsDecoratorBuilderFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Network\DestinationPolicy;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\TokenStatusList\Factories\StatusListFactory;
use SimpleSAML\OpenID\TokenStatusList\Factories\StatusListTokenFactory;
use SimpleSAML\OpenID\TokenStatusList\Factories\StatusReferenceFactory;
use SimpleSAML\OpenID\TokenStatusList\StatusListTokenFetcher;
use SimpleSAML\OpenID\TokenStatusList\StatusResolver;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

/**
 * Entry point for the Token Status List specification.
 *
 * https://datatracker.ietf.org/doc/html/draft-ietf-oauth-status-list
 *
 * @see \SimpleSAML\Test\OpenID\TokenStatusListTest
 */
class TokenStatusList
{
    protected DateIntervalDecorator $maxCacheDurationDecorator;

    protected DateIntervalDecorator $timestampValidationLeewayDecorator;

    protected ?CacheDecorator $cacheDecorator;

    protected HttpClientDecorator $httpClientDecorator;

    protected ?DateIntervalDecoratorFactory $dateIntervalDecoratorFactory = null;

    protected ?CacheDecoratorFactory $cacheDecoratorFactory = null;

    protected ?HttpClientDecoratorFactory $httpClientDecoratorFactory = null;

    protected ?Helpers $helpers = null;

    protected ?ArtifactFetcher $artifactFetcher = null;

    protected ?ClaimFactory $claimFactory = null;

    protected ?JwsSerializerManagerDecoratorFactory $jwsSerializerManagerDecoratorFactory = null;

    protected ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null;

    protected ?AlgorithmManagerDecoratorFactory $algorithmManagerDecoratorFactory = null;

    protected ?AlgorithmManagerDecorator $algorithmManagerDecorator = null;

    protected ?JwsDecoratorBuilderFactory $jwsDecoratorBuilderFactory = null;

    protected ?JwsDecoratorBuilder $jwsDecoratorBuilder = null;

    protected ?JwsVerifierDecoratorFactory $jwsVerifierDecoratorFactory = null;

    protected ?JwsVerifierDecorator $jwsVerifierDecorator = null;

    protected ?JwksDecoratorFactory $jwksDecoratorFactory = null;

    protected ?StatusListFactory $statusListFactory = null;

    protected ?StatusReferenceFactory $statusReferenceFactory = null;

    protected ?StatusListTokenFactory $statusListTokenFactory = null;

    protected ?StatusListTokenFetcher $statusListTokenFetcher = null;

    protected ?StatusResolver $statusResolver = null;

    protected DestinationPolicy $destinationPolicy;


    /**
     * @param \DateInterval $maxCacheDuration Ceiling for how long a fetched Status List Token is cached. The
     * token's own `ttl` and expiration time shorten it further, never lengthen it.
     * @param array<string,mixed> $httpClientConfig
     * @param ?\SimpleSAML\OpenID\Network\DestinationPolicy $destinationPolicy Where Status List Token fetches
     * may be sent. Defaults to refusing every non-public destination, since the status list URI is named by
     * the issuer of the credential being checked.
     */
    public function __construct(
        protected readonly SupportedAlgorithms $supportedAlgorithms = new SupportedAlgorithms(),
        protected readonly SupportedSerializers $supportedSerializers = new SupportedSerializers(),
        DateInterval $maxCacheDuration = new DateInterval('PT6H'),
        DateInterval $timestampValidationLeeway = new DateInterval('PT1M'),
        ?CacheInterface $cache = null,
        protected readonly ?LoggerInterface $logger = null,
        ?Client $client = null,
        array $httpClientConfig = [],
        int $maxFetchSizeBytes = HttpClientDecorator::DEFAULT_MAX_FETCH_SIZE_BYTES,
        ?DestinationPolicy $destinationPolicy = null,
    ) {
        $this->maxCacheDurationDecorator = $this->dateIntervalDecoratorFactory()->build($maxCacheDuration);
        $this->timestampValidationLeewayDecorator = $this->dateIntervalDecoratorFactory()
            ->build($timestampValidationLeeway);
        $this->cacheDecorator = is_null($cache) ? null : $this->cacheDecoratorFactory()->build($cache);
        $this->destinationPolicy = $destinationPolicy ?? new DestinationPolicy(logger: $this->logger);
        $this->httpClientDecorator = $this->httpClientDecoratorFactory()->build(
            $client,
            $httpClientConfig,
            $maxFetchSizeBytes,
            $this->destinationPolicy,
        );
    }


    /**
     * The policy deciding where outbound requests may be sent, so that the same rules can be applied before a
     * destination is ever fetched, such as when it is registered.
     */
    public function destinationPolicy(): DestinationPolicy
    {
        return $this->destinationPolicy;
    }


    public function dateIntervalDecoratorFactory(): DateIntervalDecoratorFactory
    {
        return $this->dateIntervalDecoratorFactory ??= new DateIntervalDecoratorFactory();
    }


    public function cacheDecoratorFactory(): CacheDecoratorFactory
    {
        return $this->cacheDecoratorFactory ??= new CacheDecoratorFactory();
    }


    public function httpClientDecoratorFactory(): HttpClientDecoratorFactory
    {
        return $this->httpClientDecoratorFactory ??= new HttpClientDecoratorFactory($this->logger);
    }


    public function maxCacheDurationDecorator(): DateIntervalDecorator
    {
        return $this->maxCacheDurationDecorator;
    }


    public function timestampValidationLeewayDecorator(): DateIntervalDecorator
    {
        return $this->timestampValidationLeewayDecorator;
    }


    public function cacheDecorator(): ?CacheDecorator
    {
        return $this->cacheDecorator;
    }


    public function helpers(): Helpers
    {
        return $this->helpers ??= new Helpers();
    }


    public function artifactFetcher(): ArtifactFetcher
    {
        return $this->artifactFetcher ??= new ArtifactFetcher(
            $this->httpClientDecorator,
            $this->cacheDecorator(),
            $this->logger,
        );
    }


    public function claimFactory(): ClaimFactory
    {
        return $this->claimFactory ??= new ClaimFactory(
            $this->helpers(),
        );
    }


    public function jwsSerializerManagerDecoratorFactory(): JwsSerializerManagerDecoratorFactory
    {
        return $this->jwsSerializerManagerDecoratorFactory ??= new JwsSerializerManagerDecoratorFactory();
    }


    public function jwsSerializerManagerDecorator(): JwsSerializerManagerDecorator
    {
        return $this->jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorFactory()
            ->build($this->supportedSerializers);
    }


    public function algorithmManagerDecoratorFactory(): AlgorithmManagerDecoratorFactory
    {
        return $this->algorithmManagerDecoratorFactory ??= new AlgorithmManagerDecoratorFactory();
    }


    public function algorithmManagerDecorator(): AlgorithmManagerDecorator
    {
        return $this->algorithmManagerDecorator ??= $this->algorithmManagerDecoratorFactory()
            ->build($this->supportedAlgorithms);
    }


    public function jwsDecoratorBuilderFactory(): JwsDecoratorBuilderFactory
    {
        return $this->jwsDecoratorBuilderFactory ??= new JwsDecoratorBuilderFactory();
    }


    public function jwsDecoratorBuilder(): JwsDecoratorBuilder
    {
        return $this->jwsDecoratorBuilder ??= $this->jwsDecoratorBuilderFactory()->build(
            $this->jwsSerializerManagerDecorator(),
            $this->algorithmManagerDecorator(),
            $this->helpers(),
        );
    }


    public function jwsVerifierDecoratorFactory(): JwsVerifierDecoratorFactory
    {
        return $this->jwsVerifierDecoratorFactory ??= new JwsVerifierDecoratorFactory();
    }


    public function jwsVerifierDecorator(): JwsVerifierDecorator
    {
        return $this->jwsVerifierDecorator ??= $this->jwsVerifierDecoratorFactory()->build(
            $this->algorithmManagerDecorator(),
        );
    }


    public function jwksDecoratorFactory(): JwksDecoratorFactory
    {
        return $this->jwksDecoratorFactory ??= new JwksDecoratorFactory();
    }


    public function statusListFactory(): StatusListFactory
    {
        return $this->statusListFactory ??= new StatusListFactory(
            $this->helpers(),
        );
    }


    public function statusReferenceFactory(): StatusReferenceFactory
    {
        return $this->statusReferenceFactory ??= new StatusReferenceFactory(
            $this->helpers(),
        );
    }


    public function statusListTokenFactory(): StatusListTokenFactory
    {
        return $this->statusListTokenFactory ??= new StatusListTokenFactory(
            $this->jwsDecoratorBuilder(),
            $this->jwsVerifierDecorator(),
            $this->jwksDecoratorFactory(),
            $this->jwsSerializerManagerDecorator(),
            $this->timestampValidationLeewayDecorator(),
            $this->helpers(),
            $this->claimFactory(),
            $this->statusListFactory(),
        );
    }


    public function statusListTokenFetcher(): StatusListTokenFetcher
    {
        return $this->statusListTokenFetcher ??= new StatusListTokenFetcher(
            $this->statusListTokenFactory(),
            $this->artifactFetcher(),
            $this->maxCacheDurationDecorator(),
            $this->helpers(),
            $this->logger,
        );
    }


    public function statusResolver(): StatusResolver
    {
        return $this->statusResolver ??= new StatusResolver(
            $this->statusListTokenFetcher(),
            $this->logger,
        );
    }
}
