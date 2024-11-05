<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID;

use DateInterval;
use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\OpenID\Factories\AlgorithmManagerFactory;
use SimpleSAML\OpenID\Factories\CacheDecoratorFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\HttpClientDecoratorFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jwks\Factories\SignedJwksFactory;
use SimpleSAML\OpenID\Jwks\JwksFetcher;
use SimpleSAML\OpenID\Jws\Factories\JwsParserFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\SupportedAlgorithms;
use SimpleSAML\OpenID\SupportedSerializers;

#[CoversClass(Jwks::class)]
#[UsesClass(JwksFactory::class)]
#[UsesClass(ParsedJwsFactory::class)]
#[UsesClass(SignedJwksFactory::class)]
#[UsesClass(JwksFetcher::class)]
class JwksTest extends TestCase
{
    protected MockObject $supportedAlgorithmsMock;
    protected MockObject $supportedSerializersMock;
    protected MockObject $maxCacheDurationMock;
    protected MockObject $cacheMock;
    protected MockObject $httpClientMock;
    protected MockObject $loggerMock;
    protected MockObject $helpersMock;
    protected MockObject $algorithmManagerFactoryMock;
    protected MockObject $jwsSerializerManagerFactoryMock;
    protected MockObject $jwsParserFactoryMock;
    protected MockObject $jwsVerifierFactoryMock;
    protected MockObject $timestampValidationLeewayMock;
    protected MockObject $dateIntervalDecoratorFactoryMock;
    protected MockObject $cacheDecoratorFactoryMock;
    protected MockObject $httpClientDecoratorFactoryMock;
    protected function setUp(): void
    {
        $this->supportedAlgorithmsMock = $this->createMock(SupportedAlgorithms::class);
        $this->supportedSerializersMock = $this->createMock(SupportedSerializers::class);
        $this->maxCacheDurationMock = $this->createMock(DateInterval::class);
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->httpClientMock = $this->createMock(Client::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->helpersMock = $this->createMock(Helpers::class);
        $this->algorithmManagerFactoryMock = $this->createMock(AlgorithmManagerFactory::class);
        $this->jwsSerializerManagerFactoryMock = $this->createMock(JwsSerializerManagerFactory::class);
        $this->jwsParserFactoryMock = $this->createMock(JwsParserFactory::class);
        $this->jwsVerifierFactoryMock = $this->createMock(JwsVerifierFactory::class);
        $this->timestampValidationLeewayMock = $this->createMock(DateInterval::class);
        $this->dateIntervalDecoratorFactoryMock = $this->createMock(DateIntervalDecoratorFactory::class);
        $this->cacheDecoratorFactoryMock = $this->createMock(CacheDecoratorFactory::class);
        $this->httpClientDecoratorFactoryMock = $this->createMock(HttpClientDecoratorFactory::class);
    }

    protected function sut(
        ?SupportedAlgorithms $supportedAlgorithms = null,
        ?SupportedSerializers $supportedSerializers = null,
        ?DateInterval $maxCacheDuration = null,
        ?CacheInterface $cache = null,
        ?Client $httpClient = null,
        ?LoggerInterface $logger = null,
        ?Helpers $helpers = null,
        ?AlgorithmManagerFactory $algorithmManagerFactory = null,
        ?JwsSerializerManagerFactory $jwsSerializerManagerFactory = null,
        ?JwsParserFactory $jwsParserFactory = null,
        ?JwsVerifierFactory $jwsVerifierFactory = null,
        ?DateInterval $timestampValidationLeeway = null,
        ?DateIntervalDecoratorFactory $dateIntervalDecoratorFactory = null,
        ?CacheDecoratorFactory $cacheDecoratorFactory = null,
        ?HttpClientDecoratorFactory $httpClientDecoratorFactory = null,
    ): Jwks {
        $supportedAlgorithms ??= $this->supportedAlgorithmsMock;
        $supportedSerializers ??= $this->supportedSerializersMock;
        $maxCacheDuration ??= $this->maxCacheDurationMock;
        $cache ??= $this->cacheMock;
        $httpClient ??= $this->httpClientMock;
        $logger ??= $this->loggerMock;
        $helpers ??= $this->helpersMock;
        $algorithmManagerFactory ??= $this->algorithmManagerFactoryMock;
        $jwsSerializerManagerFactory ??= $this->jwsSerializerManagerFactoryMock;
        $jwsParserFactory ??= $this->jwsParserFactoryMock;
        $jwsVerifierFactory ??= $this->jwsVerifierFactoryMock;
        $timestampValidationLeeway ??= $this->timestampValidationLeewayMock;
        $dateIntervalDecoratorFactory ??= $this->dateIntervalDecoratorFactoryMock;
        $cacheDecoratorFactory ??= $this->cacheDecoratorFactoryMock;
        $httpClientDecoratorFactory ??= $this->httpClientDecoratorFactoryMock;

        return new Jwks(
            $supportedAlgorithms,
            $supportedSerializers,
            $maxCacheDuration,
            $cache,
            $httpClient,
            $logger,
            $helpers,
            $algorithmManagerFactory,
            $jwsSerializerManagerFactory,
            $jwsParserFactory,
            $jwsVerifierFactory,
            $timestampValidationLeeway,
            $dateIntervalDecoratorFactory,
            $cacheDecoratorFactory,
            $httpClientDecoratorFactory,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Jwks::class, $this->sut());
    }

    public function testCanBuildTools(): void
    {
        $sut = $this->sut();

        $this->assertInstanceOf(JwksFactory::class, $sut->jwksFactory());
        $this->assertInstanceOf(SignedJwksFactory::class, $sut->signedJwksFactory());
        $this->assertInstanceOf(JwksFetcher::class, $sut->jwksFetcher());
    }
}
