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
use SimpleSAML\OpenID\Jwks;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jwks\Factories\SignedJwksFactory;
use SimpleSAML\OpenID\Jwks\JwksFetcher;
use SimpleSAML\OpenID\Jws\Factories\JwsParserFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\SupportedAlgorithms;
use SimpleSAML\OpenID\SupportedSerializers;

#[CoversClass(Jwks::class)]
#[UsesClass(JwksFactory::class)]
#[UsesClass(ParsedJwsFactory::class)]
#[UsesClass(SignedJwksFactory::class)]
#[UsesClass(JwksFetcher::class)]
#[UsesClass(CacheDecorator::class)]
#[UsesClass(DateIntervalDecorator::class)]
#[UsesClass(HttpClientDecorator::class)]
#[UsesClass(CacheDecoratorFactory::class)]
#[UsesClass(DateIntervalDecoratorFactory::class)]
#[UsesClass(HttpClientDecoratorFactory::class)]
#[UsesClass(AlgorithmManagerDecoratorFactory::class)]
#[UsesClass(JwsSerializerManagerDecoratorFactory::class)]
#[UsesClass(JwsParserFactory::class)]
#[UsesClass(JwsVerifierDecoratorFactory::class)]
#[UsesClass(JwsParser::class)]
#[UsesClass(AlgorithmManagerDecorator::class)]
#[UsesClass(JwsVerifierDecorator::class)]
#[UsesClass(JwsSerializerManagerDecorator::class)]
#[UsesClass(ClaimFactory::class)]
class JwksTest extends TestCase
{
    protected MockObject $supportedAlgorithmsMock;
    protected MockObject $supportedSerializersMock;
    protected DateInterval $maxCacheDuration;
    protected MockObject $cacheMock;
    protected MockObject $httpClientMock;
    protected MockObject $loggerMock;
    protected DateInterval $timestampValidationLeeway;

    protected function setUp(): void
    {
        $this->supportedAlgorithmsMock = $this->createMock(SupportedAlgorithms::class);
        $this->supportedSerializersMock = $this->createMock(SupportedSerializers::class);
        $this->maxCacheDuration = new DateInterval('PT1M');
        $this->timestampValidationLeeway = new DateInterval('PT1M');
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->httpClientMock = $this->createMock(Client::class);
    }

    protected function sut(
        ?SupportedAlgorithms $supportedAlgorithms = null,
        ?SupportedSerializers $supportedSerializers = null,
        ?DateInterval $maxCacheDuration = null,
        ?DateInterval $timestampValidationLeeway = null,
        ?CacheInterface $cache = null,
        ?LoggerInterface $logger = null,
        ?Client $httpClient = null,
    ): Jwks {
        $supportedAlgorithms ??= $this->supportedAlgorithmsMock;
        $supportedSerializers ??= $this->supportedSerializersMock;
        $maxCacheDuration ??= $this->maxCacheDuration;
        $timestampValidationLeeway ??= $this->timestampValidationLeeway;
        $cache ??= $this->cacheMock;
        $logger ??= $this->loggerMock;
        $httpClient ??= $this->httpClientMock;

        return new Jwks(
            $supportedAlgorithms,
            $supportedSerializers,
            $maxCacheDuration,
            $timestampValidationLeeway,
            $cache,
            $logger,
            $httpClient,
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
