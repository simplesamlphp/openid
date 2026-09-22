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
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsDecoratorBuilderFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\OAuth2;
use SimpleSAML\OpenID\OAuth2\Factories\JwtAccessTokenFactory;
use SimpleSAML\OpenID\Serializers\JwsSerializerBag;
use SimpleSAML\OpenID\Serializers\JwsSerializerEnum;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\SupportedAlgorithms;
use SimpleSAML\OpenID\SupportedSerializers;

#[CoversClass(OAuth2::class)]
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
#[UsesClass(JwtAccessTokenFactory::class)]
#[UsesClass(SignatureAlgorithmBag::class)]
#[UsesClass(SignatureAlgorithmEnum::class)]
#[UsesClass(JwsSerializerBag::class)]
#[UsesClass(JwsSerializerEnum::class)]
final class OAuth2Test extends TestCase
{
    protected function sut(): OAuth2
    {
        return new OAuth2();
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(OAuth2::class, $this->sut());
    }


    public function testGettersReturnCorrectInstances(): void
    {
        $oAuth2 = $this->sut();

        $this->assertInstanceOf(DateIntervalDecoratorFactory::class, $oAuth2->dateIntervalDecoratorFactory());
        $this->assertInstanceOf(
            JwsSerializerManagerDecoratorFactory::class,
            $oAuth2->jwsSerializerManagerDecoratorFactory(),
        );
        $this->assertInstanceOf(SupportedSerializers::class, $oAuth2->supportedSerializers());
        $this->assertInstanceOf(SupportedAlgorithms::class, $oAuth2->supportedAlgorithms());
        $this->assertInstanceOf(JwsDecoratorBuilderFactory::class, $oAuth2->jwsDecoratorBuilderFactory());
        $this->assertInstanceOf(JwsSerializerManagerDecorator::class, $oAuth2->jwsSerializerManagerDecorator());
        $this->assertInstanceOf(AlgorithmManagerDecoratorFactory::class, $oAuth2->algorithmManagerDecoratorFactory());
        $this->assertInstanceOf(AlgorithmManagerDecorator::class, $oAuth2->algorithmManagerDecorator());
        $this->assertInstanceOf(Helpers::class, $oAuth2->helpers());
        $this->assertInstanceOf(JwsDecoratorBuilder::class, $oAuth2->jwsDecoratorBuilder());
        $this->assertInstanceOf(JwsVerifierDecoratorFactory::class, $oAuth2->jwsVerifierDecoratorFactory());
        $this->assertInstanceOf(JwsVerifierDecorator::class, $oAuth2->jwsVerifierDecorator());
        $this->assertInstanceOf(JwksDecoratorFactory::class, $oAuth2->jwksDecoratorFactory());
        $this->assertInstanceOf(DateIntervalDecorator::class, $oAuth2->timestampValidationLeewayDecorator());
        $this->assertInstanceOf(ClaimFactory::class, $oAuth2->claimFactory());
        $this->assertInstanceOf(JwtAccessTokenFactory::class, $oAuth2->jwtAccessTokenFactory());
    }


    public function testGettersReturnCachedInstances(): void
    {
        $oAuth2 = $this->sut();

        $this->assertSame($oAuth2->dateIntervalDecoratorFactory(), $oAuth2->dateIntervalDecoratorFactory());
        $this->assertSame(
            $oAuth2->jwsSerializerManagerDecoratorFactory(),
            $oAuth2->jwsSerializerManagerDecoratorFactory(),
        );
        $this->assertSame($oAuth2->jwsDecoratorBuilderFactory(), $oAuth2->jwsDecoratorBuilderFactory());
        $this->assertSame($oAuth2->jwsSerializerManagerDecorator(), $oAuth2->jwsSerializerManagerDecorator());
        $this->assertSame($oAuth2->algorithmManagerDecoratorFactory(), $oAuth2->algorithmManagerDecoratorFactory());
        $this->assertSame($oAuth2->algorithmManagerDecorator(), $oAuth2->algorithmManagerDecorator());
        $this->assertSame($oAuth2->helpers(), $oAuth2->helpers());
        $this->assertSame($oAuth2->jwsDecoratorBuilder(), $oAuth2->jwsDecoratorBuilder());
        $this->assertSame($oAuth2->jwsVerifierDecoratorFactory(), $oAuth2->jwsVerifierDecoratorFactory());
        $this->assertSame($oAuth2->jwsVerifierDecorator(), $oAuth2->jwsVerifierDecorator());
        $this->assertSame($oAuth2->jwksDecoratorFactory(), $oAuth2->jwksDecoratorFactory());
        $this->assertSame($oAuth2->timestampValidationLeewayDecorator(), $oAuth2->timestampValidationLeewayDecorator());
        $this->assertSame($oAuth2->claimFactory(), $oAuth2->claimFactory());
        $this->assertSame($oAuth2->jwtAccessTokenFactory(), $oAuth2->jwtAccessTokenFactory());
    }


    public function testCanInstantiateWithCustomLeeway(): void
    {
        $customLeeway = new DateInterval('PT5M');
        $oAuth2 = new OAuth2(new SupportedSerializers(), new SupportedAlgorithms(), $customLeeway);

        $this->assertInstanceOf(OAuth2::class, $oAuth2);
        $this->assertSame(300, $oAuth2->timestampValidationLeewayDecorator()->getInSeconds());
    }
}
