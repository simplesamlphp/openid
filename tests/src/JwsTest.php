<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID;

use DateInterval;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\AlgorithmManagerDecoratorFactory;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerDecoratorFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws;
use SimpleSAML\OpenID\Jws\Factories\JwsDecoratorBuilderFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\SupportedAlgorithms;
use SimpleSAML\OpenID\SupportedSerializers;

#[CoversClass(Jws::class)]
#[UsesClass(AlgorithmManagerDecoratorFactory::class)]
#[UsesClass(ClaimFactory::class)]
#[UsesClass(DateIntervalDecoratorFactory::class)]
#[UsesClass(JwsSerializerManagerDecoratorFactory::class)]
#[UsesClass(JwksDecoratorFactory::class)]
#[UsesClass(JwsDecoratorBuilderFactory::class)]
#[UsesClass(JwsVerifierDecoratorFactory::class)]
#[UsesClass(ParsedJwsFactory::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(SupportedAlgorithms::class)]
#[UsesClass(SupportedSerializers::class)]
#[UsesClass(AlgorithmManagerDecorator::class)]
#[UsesClass(JwsDecoratorBuilder::class)]
#[UsesClass(JwsVerifierDecorator::class)]
#[UsesClass(JwsSerializerManagerDecorator::class)]
#[UsesClass(DateIntervalDecorator::class)]
final class JwsTest extends TestCase
{
    protected \PHPUnit\Framework\MockObject\Stub $supportedAlgorithmsMock;

    protected \PHPUnit\Framework\MockObject\Stub $supportedSerializerMock;

    protected DateInterval $timestampValidationLeeway;

    protected \PHPUnit\Framework\MockObject\Stub $loggerMock;


    protected function setUp(): void
    {
        $this->supportedAlgorithmsMock = $this->createStub(SupportedAlgorithms::class);
        $this->supportedSerializerMock = $this->createStub(SupportedSerializers::class);
        $this->timestampValidationLeeway = new DateInterval('PT1M');
        $this->loggerMock = $this->createStub(LoggerInterface::class);
    }


    protected function sut(
        ?SupportedAlgorithms $supportedAlgorithms = null,
        ?SupportedSerializers $supportedSerializers = null,
        ?DateInterval $timestampValidationLeeway = null,
        ?LoggerInterface $logger = null,
    ): Jws {
        $supportedAlgorithms ??= $this->supportedAlgorithmsMock;
        $supportedSerializers ??= $this->supportedSerializerMock;
        $timestampValidationLeeway ??= $this->timestampValidationLeeway;
        $logger ??= $this->loggerMock;

        return new Jws(
            $supportedAlgorithms,
            $supportedSerializers,
            $timestampValidationLeeway,
            $logger,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            Jws::class,
            $this->sut(),
        );
    }


    public function testCanBuildTools(): void
    {
        $sut = $this->sut();

        $this->assertInstanceOf(
            DateIntervalDecoratorFactory::class,
            $sut->dateIntervalDecoratorFactory(),
        );

        $this->assertInstanceOf(
            ParsedJwsFactory::class,
            $sut->parsedJwsFactory(),
        );

        $this->assertInstanceOf(
            JwsDecoratorBuilder::class,
            $sut->jwsDecoratorBuilder(),
        );

        $this->assertInstanceOf(
            JwsDecoratorBuilderFactory::class,
            $sut->jwsDecoratorBuilderFactory(),
        );

        $this->assertInstanceOf(
            JwsSerializerManagerDecorator::class,
            $sut->jwsSerializerManagerDecorator(),
        );

        $this->assertInstanceOf(
            JwsSerializerManagerDecoratorFactory::class,
            $sut->jwsSerializerManagerDecoratorFactory(),
        );

        $this->assertInstanceOf(
            AlgorithmManagerDecorator::class,
            $sut->algorithmManagerDecorator(),
        );

        $this->assertInstanceOf(
            AlgorithmManagerDecoratorFactory::class,
            $sut->algorithmManagerDecoratorFactory(),
        );

        $this->assertInstanceOf(
            Helpers::class,
            $sut->helpers(),
        );

        $this->assertInstanceOf(
            JwsVerifierDecorator::class,
            $sut->jwsVerifierDecorator(),
        );

        $this->assertInstanceOf(
            JwsVerifierDecoratorFactory::class,
            $sut->jwsVerifierDecoratorFactory(),
        );

        $this->assertInstanceOf(
            JwksDecoratorFactory::class,
            $sut->jwksDecoratorFactory(),
        );

        $this->assertInstanceOf(
            ClaimFactory::class,
            $sut->claimFactory(),
        );
    }


    public function testLazyInitialization(): void
    {
        $sut = $this->sut();

        $factory1 = $sut->dateIntervalDecoratorFactory();
        $factory2 = $sut->dateIntervalDecoratorFactory();
        $this->assertSame($factory1, $factory2);

        $factory1 = $sut->parsedJwsFactory();
        $factory2 = $sut->parsedJwsFactory();
        $this->assertSame($factory1, $factory2);

        $builder1 = $sut->jwsDecoratorBuilder();
        $builder2 = $sut->jwsDecoratorBuilder();
        $this->assertSame($builder1, $builder2);

        $factory1 = $sut->jwsDecoratorBuilderFactory();
        $factory2 = $sut->jwsDecoratorBuilderFactory();
        $this->assertSame($factory1, $factory2);

        $decorator1 = $sut->jwsSerializerManagerDecorator();
        $decorator2 = $sut->jwsSerializerManagerDecorator();
        $this->assertSame($decorator1, $decorator2);

        $factory1 = $sut->jwsSerializerManagerDecoratorFactory();
        $factory2 = $sut->jwsSerializerManagerDecoratorFactory();
        $this->assertSame($factory1, $factory2);

        $decorator1 = $sut->algorithmManagerDecorator();
        $decorator2 = $sut->algorithmManagerDecorator();
        $this->assertSame($decorator1, $decorator2);

        $factory1 = $sut->algorithmManagerDecoratorFactory();
        $factory2 = $sut->algorithmManagerDecoratorFactory();
        $this->assertSame($factory1, $factory2);

        $helpers1 = $sut->helpers();
        $helpers2 = $sut->helpers();
        $this->assertSame($helpers1, $helpers2);

        $verifier1 = $sut->jwsVerifierDecorator();
        $verifier2 = $sut->jwsVerifierDecorator();
        $this->assertSame($verifier1, $verifier2);

        $factory1 = $sut->jwsVerifierDecoratorFactory();
        $factory2 = $sut->jwsVerifierDecoratorFactory();
        $this->assertSame($factory1, $factory2);

        $factory1 = $sut->jwksDecoratorFactory();
        $factory2 = $sut->jwksDecoratorFactory();
        $this->assertSame($factory1, $factory2);

        $factory1 = $sut->claimFactory();
        $factory2 = $sut->claimFactory();
        $this->assertSame($factory1, $factory2);
    }
}
