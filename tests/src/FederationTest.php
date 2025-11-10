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
use SimpleSAML\OpenID\Federation;
use SimpleSAML\OpenID\Federation\EntityStatementFetcher;
use SimpleSAML\OpenID\Federation\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Federation\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustChainFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkDelegationFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkFactory;
use SimpleSAML\OpenID\Federation\MetadataPolicyApplicator;
use SimpleSAML\OpenID\Federation\MetadataPolicyResolver;
use SimpleSAML\OpenID\Federation\TrustChainResolver;
use SimpleSAML\OpenID\Federation\TrustMarkFetcher;
use SimpleSAML\OpenID\Federation\TrustMarkStatusFetcher;
use SimpleSAML\OpenID\Federation\TrustMarkValidator;
use SimpleSAML\OpenID\Jws\AbstractJwsFetcher;
use SimpleSAML\OpenID\Jws\Factories\JwsParserFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsFetcher;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\SupportedAlgorithms;
use SimpleSAML\OpenID\SupportedSerializers;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

#[CoversClass(Federation::class)]
#[UsesClass(ParsedJwsFactory::class)]
#[UsesClass(EntityStatementFetcher::class)]
#[UsesClass(MetadataPolicyResolver::class)]
#[UsesClass(MetadataPolicyApplicator::class)]
#[UsesClass(TrustChainFactory::class)]
#[UsesClass(TrustChainResolver::class)]
#[UsesClass(EntityStatementFactory::class)]
#[UsesClass(RequestObjectFactory::class)]
#[UsesClass(TrustMarkFactory::class)]
#[UsesClass(AlgorithmManagerDecoratorFactory::class)]
#[UsesClass(JwsSerializerManagerDecoratorFactory::class)]
#[UsesClass(JwsParserFactory::class)]
#[UsesClass(JwsParser::class)]
#[UsesClass(JwsVerifierDecoratorFactory::class)]
#[UsesClass(DateIntervalDecorator::class)]
#[UsesClass(DateIntervalDecoratorFactory::class)]
#[UsesClass(CacheDecorator::class)]
#[UsesClass(CacheDecoratorFactory::class)]
#[UsesClass(HttpClientDecorator::class)]
#[UsesClass(HttpClientDecoratorFactory::class)]
#[UsesClass(ArtifactFetcher::class)]
#[UsesClass(AbstractJwsFetcher::class)]
#[UsesClass(JwsFetcher::class)]
#[UsesClass(AlgorithmManagerDecorator::class)]
#[UsesClass(JwsVerifierDecorator::class)]
#[UsesClass(JwsSerializerManagerDecorator::class)]
#[UsesClass(ClaimFactory::class)]
#[UsesClass(TrustMarkDelegationFactory::class)]
#[UsesClass(TrustMarkValidator::class)]
#[UsesClass(TrustMarkFetcher::class)]
#[UsesClass(TrustMarkStatusFetcher::class)]
final class FederationTest extends TestCase
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
        $this->assertNotEmpty($sut->maxTrustChainDepth());
        $this->assertInstanceOf(TrustMarkDelegationFactory::class, $sut->trustMarkDelegationFactory());
        $this->assertInstanceOf(TrustMarkValidator::class, $sut->trustMarkValidator());
        $this->assertInstanceOf(TrustMarkFetcher::class, $sut->trustMarkFetcher());
    }
}
