<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jar\Factories;

use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jar\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Jar\RequestObject;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(RequestObjectFactory::class)]
#[UsesClass(RequestObject::class)]
#[UsesClass(ParsedJws::class)]
#[UsesClass(SignatureAlgorithmEnum::class)]
final class RequestObjectFactoryTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject&\SimpleSAML\OpenID\Jws\JwsDecoratorBuilder */
    protected MockObject $jwsDecoratorBuilderMock;

    /** @var \PHPUnit\Framework\MockObject\Stub&\SimpleSAML\OpenID\Jws\JwsVerifierDecorator */
    protected \PHPUnit\Framework\MockObject\Stub $jwsVerifierDecoratorMock;

    /** @var \PHPUnit\Framework\MockObject\Stub&\SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory */
    protected \PHPUnit\Framework\MockObject\Stub $jwksDecoratorFactoryMock;

    /** @var \PHPUnit\Framework\MockObject\Stub&\SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator */
    protected \PHPUnit\Framework\MockObject\Stub $jwsSerializerManagerDecoratorMock;

    /** @var \PHPUnit\Framework\MockObject\Stub&\SimpleSAML\OpenID\Decorators\DateIntervalDecorator */
    protected \PHPUnit\Framework\MockObject\Stub $dateIntervalDecoratorMock;

    /** @var \PHPUnit\Framework\MockObject\MockObject&\SimpleSAML\OpenID\Helpers */
    protected MockObject $helpersMock;

    /** @var \PHPUnit\Framework\MockObject\Stub&\SimpleSAML\OpenID\Factories\ClaimFactory */
    protected \PHPUnit\Framework\MockObject\Stub $claimFactoryMock;

    /** @var array<string, mixed> */
    protected array $sampleHeader = [
        'alg' => 'RS256',
        'typ' => 'oauth-authz-req+jwt',
        'kid' => 'request-object-key',
    ];

    /** @var array<string, mixed> */
    protected array $payload = [
        'client_id' => 'client-id-uri',
    ];


    protected function setUp(): void
    {
        $signatureMock = $this->createMock(Signature::class);
        $signatureMock->method('getProtectedHeader')->willReturnCallback(
            fn(): array => $this->sampleHeader,
        );

        $jwsMock = $this->createMock(JWS::class);
        $jwsMock->method('getPayload')
            ->willReturn('json-payload-string');
        $jwsMock->method('getSignature')->willReturn($signatureMock);

        $jwsDecoratorMock = $this->createMock(JwsDecorator::class);
        $jwsDecoratorMock->method('jws')->willReturn($jwsMock);

        $this->jwsDecoratorBuilderMock = $this->createMock(JwsDecoratorBuilder::class);
        $this->jwsDecoratorBuilderMock->method('fromToken')
            ->willReturn($jwsDecoratorMock);
        $this->jwsDecoratorBuilderMock->method('fromData')
            ->willReturn($jwsDecoratorMock);

        $this->jwsVerifierDecoratorMock = $this->createStub(JwsVerifierDecorator::class);
        $this->jwksDecoratorFactoryMock = $this->createStub(JwksDecoratorFactory::class);
        $this->jwsSerializerManagerDecoratorMock = $this->createStub(JwsSerializerManagerDecorator::class);
        $this->dateIntervalDecoratorMock = $this->createStub(DateIntervalDecorator::class);

        $this->helpersMock = $this->createMock(Helpers::class);
        $jsonHelperMock = $this->createMock(Helpers\Json::class);
        $this->helpersMock->method('json')->willReturn($jsonHelperMock);

        $typeHelperMock = $this->createMock(Helpers\Type::class);
        $this->helpersMock->method('type')->willReturn($typeHelperMock);

        $typeHelperMock->method('ensureArrayWithValuesAsStrings')->willReturnArgument(0);
        $typeHelperMock->method('ensureInt')->willReturnArgument(0);
        $typeHelperMock->method('ensureNonEmptyString')->willReturnArgument(0);

        $jsonHelperMock->method('decode')->willReturnCallback(
            fn(): array => $this->payload,
        );

        $this->claimFactoryMock = $this->createStub(ClaimFactory::class);
    }


    protected function sut(
        ?JwsDecoratorBuilder $jwsDecoratorBuilder = null,
        ?JwsVerifierDecorator $jwsVerifierDecorator = null,
        ?JwksDecoratorFactory $jwksDecoratorFactory = null,
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
        ?ClaimFactory $claimFactory = null,
    ): RequestObjectFactory {
        $jwsDecoratorBuilder ??= $this->jwsDecoratorBuilderMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksDecoratorFactory ??= $this->jwksDecoratorFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;
        $claimFactory ??= $this->claimFactoryMock;

        return new RequestObjectFactory(
            $jwsDecoratorBuilder,
            $jwsVerifierDecorator,
            $jwksDecoratorFactory,
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
        $this->assertInstanceOf(
            RequestObject::class,
            $this->sut()->fromToken('token'),
        );
    }


    public function testCanBuildFromData(): void
    {
        $this->assertInstanceOf(
            RequestObject::class,
            $this->sut()->fromData(
                $this->createStub(JwkDecorator::class),
                SignatureAlgorithmEnum::RS256,
                [],
                [],
            ),
        );
    }
}
