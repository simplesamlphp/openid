<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\Factories;

use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\VerifiableCredentials\Factories\OpenId4VciProofFactory;
use SimpleSAML\OpenID\VerifiableCredentials\OpenId4VciProof;

#[\PHPUnit\Framework\Attributes\CoversClass(OpenId4VciProofFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(SignatureAlgorithmEnum::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ParsedJws::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(OpenId4VciProof::class)]
final class OpenId4VciProofFactoryTest extends TestCase
{
    protected MockObject $signatureMock;

    protected MockObject $jwsDecoratorBuilderMock;

    protected MockObject $jwsVerifierDecoratorMock;

    protected MockObject $jwksDecoratorFactoryMock;

    protected MockObject $jwsSerializerManagerDecoratorMock;

    protected MockObject $dateIntervalDecoratorMock;

    protected MockObject $helpersMock;

    protected MockObject $jsonHelperMock;

    protected MockObject $claimFactoryMock;

    protected array $sampleHeader = [
        "typ" => "openid4vci-proof+jwt",
        "alg" => "ES256",
        "jwk" => [
            "kty" => "EC",
            "crv" => "P-256",
            "x" => "nUWAoAv3XZith8E7i19OdaxOLYFOwM-Z2EuM02TirT4",
            "y" => "HskHU8BjUi1U9Xqi7Swmj8gwAK_0xkcDjEW_71SosEY",
        ],
    ];

    protected array $expiredPayload = [
        'iat' => 1734009487,
        'nbf' => 1734009487,
        'exp' => 1734009487,
        "iss" => "s6BhdRkqt3",
        "aud" => "https://server.example.com",
        "nonce" => "tZignsnFbp",
    ];

    protected array $validPayload;

    protected MockObject $jwkDecoratorMock;


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

        $this->jwsVerifierDecoratorMock = $this->createMock(JwsVerifierDecorator::class);
        $this->jwksDecoratorFactoryMock = $this->createMock(JwksDecoratorFactory::class);
        $this->jwsSerializerManagerDecoratorMock = $this->createMock(JwsSerializerManagerDecorator::class);
        $this->dateIntervalDecoratorMock = $this->createMock(DateIntervalDecorator::class);

        $this->helpersMock = $this->createMock(Helpers::class);
        $this->jsonHelperMock = $this->createMock(Helpers\Json::class);
        $this->helpersMock->method('json')->willReturn($this->jsonHelperMock);
        $typeHelperMock = $this->createMock(Helpers\Type::class);
        $this->helpersMock->method('type')->willReturn($typeHelperMock);

        $typeHelperMock->method('ensureNonEmptyString')->willReturnArgument(0);
        $typeHelperMock->method('ensureInt')->willReturnArgument(0);

        $this->claimFactoryMock = $this->createMock(ClaimFactory::class);

        $this->validPayload = $this->expiredPayload;
        $this->validPayload['exp'] = time() + 3600;

        $this->jwkDecoratorMock = $this->createMock(JwkDecorator::class);
    }


    protected function sut(
        ?JwsDecoratorBuilder $jwsDecoratorBuilder = null,
        ?JwsVerifierDecorator $jwsVerifierDecorator = null,
        ?JwksDecoratorFactory $jwksDecoratorFactory = null,
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
        ?ClaimFactory $claimFactory = null,
    ): OpenId4VciProofFactory {
        $jwsDecoratorBuilder ??= $this->jwsDecoratorBuilderMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksDecoratorFactory ??= $this->jwksDecoratorFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;
        $claimFactory ??= $this->claimFactoryMock;

        return new OpenId4VciProofFactory(
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
        $this->assertInstanceOf(OpenId4VciProofFactory::class, $this->sut());
    }


    public function testCanBuildFromToken(): void
    {
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $this->assertInstanceOf(
            OpenId4VciProof::class,
            $this->sut()->fromToken('token'),
        );
    }


    public function testCanBuildFromData(): void
    {
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $this->assertInstanceOf(
            OpenId4VciProof::class,
            $this->sut()->fromData(
                $this->jwkDecoratorMock,
                SignatureAlgorithmEnum::ES256,
                $this->validPayload,
                $this->sampleHeader,
            ),
        );
    }
}
