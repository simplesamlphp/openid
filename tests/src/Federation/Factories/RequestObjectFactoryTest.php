<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\Factories;

use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Federation\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Federation\RequestObject;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(RequestObjectFactory::class)]
#[UsesClass(ParsedJwsFactory::class)]
#[UsesClass(ParsedJws::class)]
#[UsesClass(RequestObject::class)]
final class RequestObjectFactoryTest extends TestCase
{
    protected MockObject $signatureMock;

    protected MockObject $jwsDecoratorBuilderMock;

    protected \PHPUnit\Framework\MockObject\Stub $jwsVerifierDecoratorMock;

    protected \PHPUnit\Framework\MockObject\Stub $jwksDecoratorFactoryMock;

    protected \PHPUnit\Framework\MockObject\Stub $jwsSerializerManagerDecoratorMock;

    protected \PHPUnit\Framework\MockObject\Stub $dateIntervalDecoratorMock;

    protected MockObject $helpersMock;

    protected MockObject $jsonHelperMock;

    protected \PHPUnit\Framework\MockObject\Stub $claimFactoryMock;

    protected array $sampleHeader = [
        'alg' => 'RS256',
        'typ' => 'jwt',
        'kid' => 'LfgZECDYkSTHmbllBD5_Tkwvy3CtOpNYQ7-DfQawTww',
    ];

    protected array $expiredPayload = [
        'iat' => 1734010504,
        'nbf' => 1734010504,
        'exp' => 1734010504,
        'aud' => [
            0 => 'https://82-dap.localhost.markoivancic.from.hr',
        ],
        'iss' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/',
        'jti' => '1734010504',
        'client_id' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/',
    ];

    protected array $validPayload;


    protected function setUp(): void
    {
        $this->signatureMock = $this->createMock(Signature::class);

        $jwsMock = $this->createMock(JWS::class);
        $jwsMock->method('getPayload')
            ->willReturn('json-payload-string'); // Just so we have non-empty value.
        $jwsMock->method('getSignature')->willReturn($this->signatureMock);

        $jwsDecoratorMock = $this->createMock(JwsDecorator::class);
        $jwsDecoratorMock->method('jws')->willReturn($jwsMock);

        $this->jwsDecoratorBuilderMock = $this->createMock(JwsDecoratorBuilder::class);
        $this->jwsDecoratorBuilderMock->method('fromToken')->willReturn($jwsDecoratorMock);
        $this->jwsDecoratorBuilderMock->method('fromData')->willReturn($jwsDecoratorMock);

        $this->jwsVerifierDecoratorMock = $this->createStub(JwsVerifierDecorator::class);
        $this->jwksDecoratorFactoryMock = $this->createStub(JwksDecoratorFactory::class);
        $this->jwsSerializerManagerDecoratorMock = $this->createStub(JwsSerializerManagerDecorator::class);
        $this->dateIntervalDecoratorMock = $this->createStub(DateIntervalDecorator::class);

        $this->helpersMock = $this->createMock(Helpers::class);
        $this->jsonHelperMock = $this->createMock(Helpers\Json::class);
        $this->helpersMock->method('json')->willReturn($this->jsonHelperMock);
        $typeHelperMock = $this->createMock(Helpers\Type::class);
        $this->helpersMock->method('type')->willReturn($typeHelperMock);

        $typeHelperMock->method('ensureInt')->willReturnArgument(0);

        $this->claimFactoryMock = $this->createStub(ClaimFactory::class);

        $this->validPayload = $this->expiredPayload;
        $this->validPayload['exp'] = time() + 3600;
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
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $this->assertInstanceOf(
            RequestObject::class,
            $this->sut()->fromToken('token'),
        );
    }


    public function testCanBuildFromData(): void
    {
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $this->assertInstanceOf(
            RequestObject::class,
            $this->sut()->fromData(
                $this->createStub(\SimpleSAML\OpenID\Jwk\JwkDecorator::class),
                SignatureAlgorithmEnum::ES256,
                $this->validPayload,
                $this->sampleHeader,
            ),
        );
    }
}
