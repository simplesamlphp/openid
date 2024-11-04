<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID;

use DateInterval;
use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\CoversClass;
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
use SimpleSAML\OpenID\Federation\EntityStatementFetcher;
use SimpleSAML\OpenID\Federation\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Federation\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustChainFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkFactory;
use SimpleSAML\OpenID\Federation\MetadataPolicyResolver;
use SimpleSAML\OpenID\Federation\TrustChainResolver;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsParserFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierFactory;
use SimpleSAML\OpenID\SupportedAlgorithms;
use SimpleSAML\OpenID\SupportedSerializers;

#[CoversClass(Federation::class)]
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
    protected MockObject $entityStatementFactoryMock;
    protected MockObject $jwksFactoryMock;
    protected MockObject $trustChainFactoryMock;
    protected MockObject $requestObjectFactoryMock;
    protected MockObject $metadataPolicyResolverMock;
    protected MockObject $trustMarkFactoryMock;
    protected MockObject $entityStatementFetcherMock;
    protected MockObject $trustChainResolverMock;
    protected MockObject $dateIntervalDecoratorFactoryMock;
    protected MockObject $cacheDecoratorFactoryMock;
    protected MockObject $httpClientDecoratorFactoryMock;

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
        $this->entityStatementFactoryMock = $this->createMock(EntityStatementFactory::class);
        $this->jwksFactoryMock = $this->createMock(JwksFactory::class);
        $this->trustChainFactoryMock = $this->createMock(TrustChainFactory::class);
        $this->requestObjectFactoryMock = $this->createMock(RequestObjectFactory::class);
        $this->metadataPolicyResolverMock = $this->createMock(MetadataPolicyResolver::class);
        $this->trustMarkFactoryMock = $this->createMock(TrustMarkFactory::class);
        $this->entityStatementFetcherMock = $this->createMock(EntityStatementFetcher::class);
        $this->trustChainResolverMock = $this->createMock(TrustChainResolver::class);
        $this->dateIntervalDecoratorFactoryMock = $this->createMock(DateIntervalDecoratorFactory::class);
        $this->cacheDecoratorFactoryMock = $this->createMock(CacheDecoratorFactory::class);
        $this->httpClientDecoratorFactoryMock = $this->createMock(HttpClientDecoratorFactory::class);
    }

    protected function sut(
        SupportedAlgorithms|MockObject|null $supportedAlgorithms = null,
        SupportedSerializers|MockObject|null $supportedSerializers = null,
        DateInterval|MockObject|null $maxCacheDuration = null,
        DateInterval|MockObject|null $timestampValidationLeeway = null,
        ?int $maxTrustChainDepth = null,
        CacheInterface|MockObject|null $cache = null,
        LoggerInterface|MockObject|null $logger = null,
        Client|MockObject|null $client = null,
        Helpers|MockObject|null $helpers = null,
        AlgorithmManagerFactory|MockObject|null $algorithmManagerFactory = null,
        JwsSerializerManagerFactory|MockObject|null $jwsSerializerManagerFactory = null,
        JwsParserFactory|MockObject|null $jwsParserFactory = null,
        JwsVerifierFactory|MockObject|null $jwsVerifierFactory = null,
        EntityStatementFactory|MockObject|null $entityStatementFactory = null,
        JwksFactory|MockObject|null $jwksFactory = null,
        TrustChainFactory|MockObject|null $trustChainFactory = null,
        RequestObjectFactory|MockObject|null $requestObjectFactory = null,
        MetadataPolicyResolver|MockObject|null $metadataPolicyResolver = null,
        TrustMarkFactory|MockObject|null $trustMarkFactory = null,
        EntityStatementFetcher|MockObject|null $entityStatementFetcher = null,
        TrustChainResolver|MockObject|null $trustChainResolver = null,
        DateIntervalDecoratorFactory|MockObject|null $dateIntervalDecoratorFactory = null,
        CacheDecoratorFactory|MockObject|null $cacheDecoratorFactory = null,
        HttpClientDecoratorFactory|MockObject|null $httpClientDecoratorFactory = null,
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
        $entityStatementFactory ??= $this->entityStatementFactoryMock;
        $jwksFactory ??= $this->jwksFactoryMock;
        $trustChainFactory ??= $this->trustChainFactoryMock;
        $requestObjectFactory ??= $this->requestObjectFactoryMock;
        $metadataPolicyResolver ??= $this->metadataPolicyResolverMock;
        $trustMarkFactory ??= $this->trustMarkFactoryMock;
        $entityStatementFetcher ??= $this->entityStatementFetcherMock;
        $trustChainResolver ??= $this->trustChainResolverMock;
        $dateIntervalDecoratorFactory ??= $this->dateIntervalDecoratorFactoryMock;
        $cacheDecoratorFactory ??= $this->cacheDecoratorFactoryMock;
        $httpClientDecoratorFactory ??= $this->httpClientDecoratorFactoryMock;

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
            $entityStatementFactory,
            $jwksFactory,
            $trustChainFactory,
            $requestObjectFactory,
            $metadataPolicyResolver,
            $trustMarkFactory,
            $entityStatementFetcher,
            $trustChainResolver,
            $dateIntervalDecoratorFactory,
            $cacheDecoratorFactory,
            $httpClientDecoratorFactory,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Federation::class, $this->sut());
    }

    public function testCanGetProperties(): void
    {
        $sut = $this->sut();

        $this->assertInstanceOf(EntityStatementFetcher::class, $sut->entityStatementFetcher());

        // TODO mivanci continue checking properties.
    }
}
