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
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\AlgorithmManagerFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsParserFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\SupportedAlgorithms;
use SimpleSAML\OpenID\SupportedSerializers;

#[CoversClass(Core::class)]
#[UsesClass(ParsedJwsFactory::class)]
#[UsesClass(RequestObjectFactory::class)]
#[UsesClass(ClientAssertionFactory::class)]
#[UsesClass(DateIntervalDecorator::class)]
#[UsesClass(DateIntervalDecoratorFactory::class)]
#[UsesClass(AlgorithmManagerFactory::class)]
#[UsesClass(JwsSerializerManagerFactory::class)]
#[UsesClass(JwsParserFactory::class)]
#[UsesClass(JwsVerifierFactory::class)]
#[UsesClass(JwsParser::class)]
class CoreTest extends TestCase
{
    protected MockObject $supportedAlgorithmsMock;
    protected MockObject $supportedSerializerMock;
    protected DateInterval $timestampValidationLeeway;
    protected MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->supportedAlgorithmsMock = $this->createMock(SupportedAlgorithms::class);
        $this->supportedSerializerMock = $this->createMock(SupportedSerializers::class);
        $this->timestampValidationLeeway = new DateInterval('PT1M');
        $this->loggerMock = $this->createMock(LoggerInterface::class);
    }

    protected function sut(
        ?SupportedAlgorithms $supportedAlgorithms = null,
        ?SupportedSerializers $supportedSerializers = null,
        ?DateInterval $timestampValidationLeeway = null,
        ?LoggerInterface $logger = null,
    ): Core {
        $supportedAlgorithms ??= $this->supportedAlgorithmsMock;
        $supportedSerializers ??= $this->supportedSerializerMock;
        $timestampValidationLeeway ??= $this->timestampValidationLeeway;
        $logger ??= $this->loggerMock;

        return new Core(
            $supportedAlgorithms,
            $supportedSerializers,
            $timestampValidationLeeway,
            $logger,
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
