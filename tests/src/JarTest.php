<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID;

use DateInterval;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\AlgorithmManagerDecoratorFactory;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerDecoratorFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jar;
use SimpleSAML\OpenID\Jar\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsDecoratorBuilderFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerBag;
use SimpleSAML\OpenID\Serializers\JwsSerializerEnum;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\SupportedAlgorithms;
use SimpleSAML\OpenID\SupportedSerializers;

#[CoversClass(Jar::class)]
#[UsesClass(DateIntervalDecoratorFactory::class)]
#[UsesClass(DateIntervalDecorator::class)]
#[UsesClass(JwsSerializerManagerDecoratorFactory::class)]
#[UsesClass(JwsSerializerManagerDecorator::class)]
#[UsesClass(SupportedSerializers::class)]
#[UsesClass(JwsDecoratorBuilderFactory::class)]
#[UsesClass(JwsDecoratorBuilder::class)]
#[UsesClass(AlgorithmManagerDecoratorFactory::class)]
#[UsesClass(AlgorithmManagerDecorator::class)]
#[UsesClass(SupportedAlgorithms::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(JwsVerifierDecoratorFactory::class)]
#[UsesClass(JwsVerifierDecorator::class)]
#[UsesClass(JwksDecoratorFactory::class)]
#[UsesClass(ClaimFactory::class)]
#[UsesClass(RequestObjectFactory::class)]
#[UsesClass(SignatureAlgorithmBag::class)]
#[UsesClass(SignatureAlgorithmEnum::class)]
#[UsesClass(JwsSerializerBag::class)]
#[UsesClass(JwsSerializerEnum::class)]
final class JarTest extends TestCase
{
    protected function sut(): Jar
    {
        return new Jar();
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Jar::class, $this->sut());
    }


    public function testGettersReturnCorrectInstances(): void
    {
        $jar = $this->sut();

        $this->assertInstanceOf(DateIntervalDecoratorFactory::class, $jar->dateIntervalDecoratorFactory());
        $this->assertInstanceOf(
            JwsSerializerManagerDecoratorFactory::class,
            $jar->jwsSerializerManagerDecoratorFactory(),
        );
        $this->assertInstanceOf(SupportedSerializers::class, $jar->supportedSerializers());
        $this->assertInstanceOf(JwsDecoratorBuilderFactory::class, $jar->jwsDecoratorBuilderFactory());
        $this->assertInstanceOf(JwsSerializerManagerDecorator::class, $jar->jwsSerializerManagerDecorator());
        $this->assertInstanceOf(AlgorithmManagerDecoratorFactory::class, $jar->algorithmManagerDecoratorFactory());
        $this->assertInstanceOf(AlgorithmManagerDecorator::class, $jar->algorithmManagerDecorator());
        $this->assertInstanceOf(Helpers::class, $jar->helpers());
        $this->assertInstanceOf(JwsDecoratorBuilder::class, $jar->jwsDecoratorBuilder());
        $this->assertInstanceOf(JwsVerifierDecoratorFactory::class, $jar->jwsVerifierDecoratorFactory());
        $this->assertInstanceOf(JwsVerifierDecorator::class, $jar->jwsVerifierDecorator());
        $this->assertInstanceOf(JwksDecoratorFactory::class, $jar->jwksDecoratorFactory());
        $this->assertInstanceOf(DateIntervalDecorator::class, $jar->timestampValidationLeewayDecorator());
        $this->assertInstanceOf(ClaimFactory::class, $jar->claimFactory());
        $this->assertInstanceOf(RequestObjectFactory::class, $jar->requestObjectFactory());
    }


    public function testGettersReturnCachedInstances(): void
    {
        $jar = $this->sut();

        $this->assertSame($jar->dateIntervalDecoratorFactory(), $jar->dateIntervalDecoratorFactory());
        $this->assertSame(
            $jar->jwsSerializerManagerDecoratorFactory(),
            $jar->jwsSerializerManagerDecoratorFactory(),
        );
        $this->assertSame($jar->jwsDecoratorBuilderFactory(), $jar->jwsDecoratorBuilderFactory());
        $this->assertSame($jar->jwsSerializerManagerDecorator(), $jar->jwsSerializerManagerDecorator());
        $this->assertSame($jar->algorithmManagerDecoratorFactory(), $jar->algorithmManagerDecoratorFactory());
        $this->assertSame($jar->algorithmManagerDecorator(), $jar->algorithmManagerDecorator());
        $this->assertSame($jar->helpers(), $jar->helpers());
        $this->assertSame($jar->jwsDecoratorBuilder(), $jar->jwsDecoratorBuilder());
        $this->assertSame($jar->jwsVerifierDecoratorFactory(), $jar->jwsVerifierDecoratorFactory());
        $this->assertSame($jar->jwsVerifierDecorator(), $jar->jwsVerifierDecorator());
        $this->assertSame($jar->jwksDecoratorFactory(), $jar->jwksDecoratorFactory());
        $this->assertSame($jar->timestampValidationLeewayDecorator(), $jar->timestampValidationLeewayDecorator());
        $this->assertSame($jar->claimFactory(), $jar->claimFactory());
        $this->assertSame($jar->requestObjectFactory(), $jar->requestObjectFactory());
    }


    public function testCanInstantiateWithCustomLeeway(): void
    {
        $customLeeway = new DateInterval('PT5M');
        $jar = new Jar(new SupportedSerializers(), new SupportedAlgorithms(), $customLeeway);

        $this->assertInstanceOf(Jar::class, $jar);
        $this->assertSame(300, $jar->timestampValidationLeewayDecorator()->getInSeconds());
    }
}
