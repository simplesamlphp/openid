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
use SimpleSAML\OpenID\Federation;
use SimpleSAML\OpenID\Federation\EntityStatement\Factories\TrustMarkClaimFactory;
use SimpleSAML\OpenID\Federation\EntityStatementFetcher;
use SimpleSAML\OpenID\Federation\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Federation\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustChainBagFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustChainFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkFactory;
use SimpleSAML\OpenID\Federation\MetadataPolicyResolver;
use SimpleSAML\OpenID\Federation\TrustChainResolver;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsParserFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
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
class FederationTest extends TestCase
{
    protected MockObject $supportedAlgorithmsMock;
    protected MockObject $supportedSerializersMock;
    protected MockObject $maxCacheDurationMock;
    protected MockObject $timestampValidationLeewayMock;
    protected int $maxTrustChainDepth;
    protected MockObject $cacheMock;
    protected MockObject $loggerMock;
    protected MockObject $clientMock;
    protected MockObject $helpersMock;
    protected MockObject $algorithmManagerFactoryMock;
    protected MockObject $jwsSerializerManagerFactoryMock;
    protected MockObject $jwsParserFactoryMock;
    protected MockObject $jwsVerifierFactoryMock;
    protected MockObject $jwksFactoryMock;
    protected MockObject $dateIntervalDecoratorFactoryMock;
    protected MockObject $cacheDecoratorFactoryMock;
    protected MockObject $httpClientDecoratorFactoryMock;
    protected MockObject $trustChainBagFactoryMock;

    protected function setUp(): void
    {
        $this->supportedAlgorithmsMock = $this->createMock(SupportedAlgorithms::class);
        $this->supportedSerializersMock = $this->createMock(SupportedSerializers::class);
        $this->maxCacheDurationMock = $this->createMock(DateInterval::class);
        $this->timestampValidationLeewayMock = $this->createMock(DateInterval::class);
        $this->maxTrustChainDepth = 9;
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->clientMock = $this->createMock(Client::class);
        $this->helpersMock = $this->createMock(Helpers::class);
        $this->algorithmManagerFactoryMock = $this->createMock(AlgorithmManagerFactory::class);
        $this->jwsSerializerManagerFactoryMock = $this->createMock(JwsSerializerManagerFactory::class);
        $this->jwsParserFactoryMock = $this->createMock(JwsParserFactory::class);
        $this->jwsVerifierFactoryMock = $this->createMock(JwsVerifierFactory::class);
        $this->jwksFactoryMock = $this->createMock(JwksFactory::class);
        $this->dateIntervalDecoratorFactoryMock = $this->createMock(DateIntervalDecoratorFactory::class);
        $this->cacheDecoratorFactoryMock = $this->createMock(CacheDecoratorFactory::class);
        $this->httpClientDecoratorFactoryMock = $this->createMock(HttpClientDecoratorFactory::class);
        $this->trustChainBagFactoryMock = $this->createMock(TrustChainBagFactory::class);
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
        ?Helpers $helpers = null,
        ?AlgorithmManagerFactory $algorithmManagerFactory = null,
        ?JwsSerializerManagerFactory $jwsSerializerManagerFactory = null,
        ?JwsParserFactory $jwsParserFactory = null,
        ?JwsVerifierFactory $jwsVerifierFactory = null,
        ?JwksFactory $jwksFactory = null,
        ?DateIntervalDecoratorFactory $dateIntervalDecoratorFactory = null,
        ?CacheDecoratorFactory $cacheDecoratorFactory = null,
        ?HttpClientDecoratorFactory $httpClientDecoratorFactory = null,
        ?TrustChainBagFactory $trustChainBagFactory = null,
    ): Federation {
        $supportedAlgorithms ??= $this->supportedAlgorithmsMock;
        $supportedSerializers ??= $this->supportedSerializersMock;
        $maxCacheDuration ??= $this->maxCacheDurationMock;
        $timestampValidationLeeway ??= $this->timestampValidationLeewayMock;
        $maxTrustChainDepth ??= $this->maxTrustChainDepth;
        $cache ??= $this->cacheMock;
        $logger ??= $this->loggerMock;
        $client ??= $this->clientMock;
        $helpers ??= $this->helpersMock;
        $algorithmManagerFactory ??= $this->algorithmManagerFactoryMock;
        $jwsSerializerManagerFactory ??= $this->jwsSerializerManagerFactoryMock;
        $jwsParserFactory ??= $this->jwsParserFactoryMock;
        $jwsVerifierFactory ??= $this->jwsVerifierFactoryMock;
        $jwksFactory ??= $this->jwksFactoryMock;
        $dateIntervalDecoratorFactory ??= $this->dateIntervalDecoratorFactoryMock;
        $cacheDecoratorFactory ??= $this->cacheDecoratorFactoryMock;
        $httpClientDecoratorFactory ??= $this->httpClientDecoratorFactoryMock;
        $trustChainBagFactory ??= $this->trustChainBagFactoryMock;

        return new Federation(
            $supportedAlgorithms,
            $supportedSerializers,
            $maxCacheDuration,
            $timestampValidationLeeway,
            $maxTrustChainDepth,
            $cache,
            $logger,
            $client,
            $helpers,
            $algorithmManagerFactory,
            $jwsSerializerManagerFactory,
            $jwsParserFactory,
            $jwsVerifierFactory,
            $jwksFactory,
            $dateIntervalDecoratorFactory,
            $cacheDecoratorFactory,
            $httpClientDecoratorFactory,
            $trustChainBagFactory,
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
    }
}
