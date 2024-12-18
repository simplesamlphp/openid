<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID;

use DateInterval;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Core;
use SimpleSAML\OpenID\Core\Factories\ClientAssertionFactory;
use SimpleSAML\OpenID\Core\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Factories\AlgorithmManagerFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsParserFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\SupportedAlgorithms;
use SimpleSAML\OpenID\SupportedSerializers;

#[CoversClass(Core::class)]
#[UsesClass(ParsedJwsFactory::class)]
#[UsesClass(RequestObjectFactory::class)]
#[UsesClass(ClientAssertionFactory::class)]
class CoreTest extends TestCase
{
    protected MockObject $supportedAlgorithmsMock;
    protected MockObject $supportedSerializerMock;
    protected MockObject $timestampValidationLeewayMock;
    protected MockObject $loggerMock;
    protected MockObject $helpersMock;
    protected MockObject $algorithmManagerFactoryMock;
    protected MockObject $jwsSerializerManagerFactoryMock;
    protected MockObject $jwsParserFactoryMock;
    protected MockObject $jwsVerifierFactoryMock;
    protected MockObject $jwksFactoryMock;
    protected MockObject $dateIntervalDecoratorFactoryMock;

    protected function setUp(): void
    {
        $this->supportedAlgorithmsMock = $this->createMock(SupportedAlgorithms::class);
        $this->supportedSerializerMock = $this->createMock(SupportedSerializers::class);
        $this->timestampValidationLeewayMock = $this->createMock(DateInterval::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->helpersMock = $this->createMock(Helpers::class);
        $this->algorithmManagerFactoryMock = $this->createMock(AlgorithmManagerFactory::class);
        $this->jwsSerializerManagerFactoryMock = $this->createMock(JwsSerializerManagerFactory::class);
        $this->jwsParserFactoryMock = $this->createMock(JwsParserFactory::class);
        $this->jwsVerifierFactoryMock = $this->createMock(JwsVerifierFactory::class);
        $this->jwksFactoryMock = $this->createMock(JwksFactory::class);
        $this->dateIntervalDecoratorFactoryMock = $this->createMock(DateIntervalDecoratorFactory::class);
    }

    protected function sut(
        ?SupportedAlgorithms $supportedAlgorithms = null,
        ?SupportedSerializers $supportedSerializers = null,
        ?DateInterval $timestampValidationLeeway = null,
        ?LoggerInterface $logger = null,
        ?Helpers $helpers = null,
        ?AlgorithmManagerFactory $algorithmManagerFactory = null,
        ?JwsSerializerManagerFactory $jwsSerializerManagerFactory = null,
        ?JwsParserFactory $jwsParserFactory = null,
        ?JwsVerifierFactory $jwsVerifierFactory = null,
        ?JwksFactory $jwksFactory = null,
        ?DateIntervalDecoratorFactory $dateIntervalDecoratorFactory = null,
    ): Core {
        $supportedAlgorithms ??= $this->supportedAlgorithmsMock;
        $supportedSerializers ??= $this->supportedSerializerMock;
        $timestampValidationLeeway ??= $this->timestampValidationLeewayMock;
        $logger ??= $this->loggerMock;
        $helpers ??= $this->helpersMock;
        $algorithmManagerFactory ??= $this->algorithmManagerFactoryMock;
        $jwsSerializerManagerFactory ??= $this->jwsSerializerManagerFactoryMock;
        $jwsParserFactory ??= $this->jwsParserFactoryMock;
        $jwsVerifierFactory ??= $this->jwsVerifierFactoryMock;
        $jwksFactory ??= $this->jwksFactoryMock;
        $dateIntervalDecoratorFactory ??= $this->dateIntervalDecoratorFactoryMock;

        return new Core(
            $supportedAlgorithms,
            $supportedSerializers,
            $timestampValidationLeeway,
            $logger,
            $helpers,
            $algorithmManagerFactory,
            $jwsSerializerManagerFactory,
            $jwsParserFactory,
            $jwsVerifierFactory,
            $jwksFactory,
            $dateIntervalDecoratorFactory,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            Core::class,
            $this->sut(),
        );
    }

    public function testCanBuildTools(): void
    {
        $sut = $this->sut();

        $this->assertInstanceOf(
            RequestObjectFactory::class,
            $sut->requestObjectFactory(),
        );

        $this->assertInstanceOf(
            ClientAssertionFactory::class,
            $sut->clientAssertionFactory(),
        );
    }
}
