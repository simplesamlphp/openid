<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Factories;

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
use SimpleSAML\OpenID\Helpers\Arr;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Factories\JwtVcJsonFactory;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\JwtVcJson;

#[CoversClass(JwtVcJsonFactory::class)]
#[UsesClass(Arr::class)]
#[UsesClass(ParsedJws::class)]
#[UsesClass(JwtVcJson::class)]
final class JwtVcJsonFactoryTest extends TestCase
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

    // https://www.w3.org/TR/vc-data-model/#example-jwt-header-of-a-jwt-based-verifiable-credential-using-jws-as-a-proof-non-normative
    protected array $sampleHeader = [
        'alg' => 'RS256',
        'typ' => 'JWT',
        'kid' => 'did:example:abfe13f712120431c276e12ecab#keys-1',
    ];

    // https://www.w3.org/TR/vc-data-model/#example-jwt-payload-of-a-jwt-based-verifiable-credential-using-jws-as-a-proof-non-normative
    protected array $expiredPayload = [
        'sub' => 'did:example:ebfeb1f712ebc6f1c276e12ec21',
        'jti' => 'http://example.edu/credentials/3732',
        'iss' => 'https://example.com/keys/foo.jwk',
        'nbf' => 1541493724,
        'iat' => 1541493724,
        'exp' => 1573029723,
        'nonce' => '660!6345FSer',
        'vc' => [
            '@context' => [
                'https://www.w3.org/2018/credentials/v1',
                'https://www.w3.org/2018/credentials/examples/v1',
            ],
            'type' => ['VerifiableCredential', 'UniversityDegreeCredential'],
            'credentialSubject' => [
                'degree' => [
                    'type' => 'BachelorDegree',
                    'name' => 'Bachelor of Science and Arts',
                ],
            ],
        ],
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

        $this->helpersMock->method('arr')->willReturn(new Helpers\Arr());

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
    ): JwtVcJsonFactory {
        $jwsDecoratorBuilder ??= $this->jwsDecoratorBuilderMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksDecoratorFactory ??= $this->jwksDecoratorFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;
        $claimFactory ??= $this->claimFactoryMock;

        return new JwtVcJsonFactory(
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
        $this->assertInstanceOf(JwtVcJsonFactory::class, $this->sut());
    }


    public function testCanBuildFromToken(): void
    {
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $this->assertInstanceOf(
            JwtVcJson::class,
            $this->sut()->fromToken('token'),
        );
    }


    public function testCanBuildFromData(): void
    {
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $this->assertInstanceOf(
            JwtVcJson::class,
            $this->sut()->fromData(
                $this->jwkDecoratorMock,
                SignatureAlgorithmEnum::ES256,
                $this->validPayload,
                $this->sampleHeader,
            ),
        );
    }
}
