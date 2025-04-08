<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Core\Factories;

use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Core\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Core\RequestObject;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(RequestObjectFactory::class)]
#[UsesClass(ParsedJwsFactory::class)]
#[UsesClass(ParsedJws::class)]
final class RequestObjectFactoryTest extends TestCase
{
    protected MockObject $signatureMock;



    protected MockObject $jwsDecoratorBuilderMock;

    protected MockObject $jwsVerifierDecoratorMock;

    protected MockObject $jwksFactoryMock;

    protected MockObject $jwsSerializerManagerDecoratorMock;

    protected MockObject $dateIntervalDecoratorMock;

    protected MockObject $helpersMock;


    protected MockObject $claimFactoryMock;

    protected array $sampleHeader = [
        'alg' => 'RS256',
        'typ' => 'jwt',
        'kid' => 'LfgZECDYkSTHmbllBD5_Tkwvy3CtOpNYQ7-DfQawTww',
    ];

    protected function setUp(): void
    {
        $this->signatureMock = $this->createMock(Signature::class);

        $jwsMock = $this->createMock(JWS::class);
        $jwsMock->method('getSignature')->willReturn($this->signatureMock);

        $jwsDecoratorMock = $this->createMock(JwsDecorator::class);
        $jwsDecoratorMock->method('jws')->willReturn($jwsMock);

        $this->jwsDecoratorBuilderMock = $this->createMock(JwsDecoratorBuilder::class);
        $this->jwsDecoratorBuilderMock->method('fromToken')->willReturn($jwsDecoratorMock);

        $this->jwsVerifierDecoratorMock = $this->createMock(JwsVerifierDecorator::class);
        $this->jwksFactoryMock = $this->createMock(JwksFactory::class);
        $this->jwsSerializerManagerDecoratorMock = $this->createMock(JwsSerializerManagerDecorator::class);
        $this->dateIntervalDecoratorMock = $this->createMock(DateIntervalDecorator::class);

        $this->helpersMock = $this->createMock(Helpers::class);
        $jsonHelperMock = $this->createMock(Helpers\Json::class);
        $this->helpersMock->method('json')->willReturn($jsonHelperMock);

        $this->claimFactoryMock = $this->createMock(ClaimFactory::class);
    }

    protected function sut(
        ?JwsDecoratorBuilder $jwsDecoratorBuilder = null,
        ?JwsVerifierDecorator $jwsVerifierDecorator = null,
        ?JwksFactory $jwksFactory = null,
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
        ?ClaimFactory $claimFactory = null,
    ): RequestObjectFactory {
        $jwsDecoratorBuilder ??= $this->jwsDecoratorBuilderMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksFactory ??= $this->jwksFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;
        $claimFactory ??= $this->claimFactoryMock;

        return new RequestObjectFactory(
            $jwsDecoratorBuilder,
            $jwsVerifierDecorator,
            $jwksFactory,
            $jwsSerializerManagerDecorator,
            $dateIntervalDecorator,
            $helpers,
            $claimFactory,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(RequestObjectFactory::class, $this->sut());
    }


    public function testCanBuildFromToken(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $this->assertInstanceOf(
            RequestObject::class,
            $this->sut()->fromToken('token'),
        );
    }
}
