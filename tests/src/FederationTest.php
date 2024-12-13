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
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Factories\AlgorithmManagerFactory;
use SimpleSAML\OpenID\Factories\CacheDecoratorFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\HttpClientDecoratorFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerFactory;
use SimpleSAML\OpenID\Federation;
use SimpleSAML\OpenID\Federation\EntityStatement\Factories\TrustMarkClaimFactory;
use SimpleSAML\OpenID\Federation\EntityStatementFetcher;
use SimpleSAML\OpenID\Federation\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Federation\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustChainFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkFactory;
use SimpleSAML\OpenID\Federation\MetadataPolicyResolver;
use SimpleSAML\OpenID\Federation\TrustChainResolver;
use SimpleSAML\OpenID\Jws\Factories\JwsParserFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\SupportedAlgorithms;
use SimpleSAML\OpenID\SupportedSerializers;

#[CoversClass(Federation::class)]
#[UsesClass(ParsedJwsFactory::class)]
#[UsesClass(EntityStatementFetcher::class)]
#[UsesClass(MetadataPolicyResolver::class)]
#[UsesClass(TrustChainFactory::class)]
#[UsesClass(TrustChainResolver::class)]
#[UsesClass(EntityStatementFactory::class)]
#[UsesClass(RequestObjectFactory::class)]
#[UsesClass(TrustMarkFactory::class)]
#[UsesClass(TrustMarkClaimFactory::class)]
#[UsesClass(AlgorithmManagerFactory::class)]
#[UsesClass(JwsSerializerManagerFactory::class)]
#[UsesClass(JwsParserFactory::class)]
#[UsesClass(JwsParser::class)]
#[UsesClass(JwsVerifierFactory::class)]
#[UsesClass(DateIntervalDecorator::class)]
#[UsesClass(DateIntervalDecoratorFactory::class)]
#[UsesClass(CacheDecorator::class)]
#[UsesClass(CacheDecoratorFactory::class)]
#[UsesClass(HttpClientDecorator::class)]
#[UsesClass(HttpClientDecoratorFactory::class)]
class FederationTest extends TestCase
{
    protected MockObject $supportedAlgorithmsMock;
    protected MockObject $supportedSerializersMock;
    protected DateInterval $maxCacheDuration;
    protected DateInterval $timestampValidationLeeway;
    protected int $maxTrustChainDepth;
    protected MockObject $cacheMock;
    protected MockObject $loggerMock;
    protected MockObject $clientMock;

    protected function setUp(): void
    {
        $this->supportedAlgorithmsMock = $this->createMock(SupportedAlgorithms::class);
        $this->supportedSerializersMock = $this->createMock(SupportedSerializers::class);
        $this->maxCacheDuration = new DateInterval('PT6H');
        $this->timestampValidationLeeway = new DateInterval('PT1M');
        $this->maxTrustChainDepth = 9;
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->clientMock = $this->createMock(Client::class);
    }

    protected function sut(
        ?SupportedAlgorithms $supportedAlgorithms = null,
        ?SupportedSerializers $supportedSerializers = null,
        ?DateInterval $maxCacheDuration = null,
        ?DateInterval $timestampValidationLeeway = null,
        ?int $maxTrustChainDepth = null,
        ?CacheInterface $cache = null,
        ?LoggerInterface $logger = null,
        ?Client $client = null,
    ): Federation {
        $supportedAlgorithms ??= $this->supportedAlgorithmsMock;
        $supportedSerializers ??= $this->supportedSerializersMock;
        $maxCacheDuration ??= $this->maxCacheDuration;
        $timestampValidationLeeway ??= $this->timestampValidationLeeway;
        $maxTrustChainDepth ??= $this->maxTrustChainDepth;
        $cache ??= $this->cacheMock;
        $logger ??= $this->loggerMock;
        $client ??= $this->clientMock;

        return new Federation(
            $supportedAlgorithms,
            $supportedSerializers,
            $maxCacheDuration,
            $timestampValidationLeeway,
            $maxTrustChainDepth,
            $cache,
            $logger,
            $client,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Federation::class, $this->sut());
    }

    public function testCanBuildTools(): void
    {
        $sut = $this->sut();

        $this->assertInstanceOf(EntityStatementFetcher::class, $sut->entityStatementFetcher());
        $this->assertInstanceOf(MetadataPolicyResolver::class, $sut->metadataPolicyResolver());
        $this->assertInstanceOf(TrustChainFactory::class, $sut->trustChainFactory());
        $this->assertInstanceOf(TrustChainResolver::class, $sut->trustChainResolver());
        $this->assertInstanceOf(EntityStatementFactory::class, $sut->entityStatementFactory());
        $this->assertInstanceOf(RequestObjectFactory::class, $sut->requestObjectFactory());
        $this->assertInstanceOf(TrustMarkFactory::class, $sut->trustMarkFactory());
        $this->assertInstanceOf(DateIntervalDecorator::class, $sut->maxCacheDurationDecorator());
        $this->assertInstanceOf(SupportedAlgorithms::class, $sut->supportedAlgorithms());
        $this->assertInstanceOf(SupportedSerializers::class, $sut->supportedSerializers());
        $this->assertIsInt($sut->maxTrustChainDepth());
    }
}
