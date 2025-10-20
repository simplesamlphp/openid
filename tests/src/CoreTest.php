<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID;

use DateInterval;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Core;
use SimpleSAML\OpenID\Core\Factories\ClientAssertionFactory;
use SimpleSAML\OpenID\Core\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\AlgorithmManagerDecoratorFactory;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsParserFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\SupportedAlgorithms;
use SimpleSAML\OpenID\SupportedSerializers;

#[CoversClass(Core::class)]
#[UsesClass(ParsedJwsFactory::class)]
#[UsesClass(RequestObjectFactory::class)]
#[UsesClass(ClientAssertionFactory::class)]
#[UsesClass(DateIntervalDecorator::class)]
#[UsesClass(DateIntervalDecoratorFactory::class)]
#[UsesClass(AlgorithmManagerDecoratorFactory::class)]
#[UsesClass(JwsSerializerManagerDecoratorFactory::class)]
#[UsesClass(JwsParserFactory::class)]
#[UsesClass(JwsVerifierDecoratorFactory::class)]
#[UsesClass(JwsParser::class)]
#[UsesClass(AlgorithmManagerDecorator::class)]
#[UsesClass(JwsVerifierDecorator::class)]
#[UsesClass(JwsSerializerManagerDecorator::class)]
#[UsesClass(ClaimFactory::class)]
final class CoreTest extends TestCase
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
