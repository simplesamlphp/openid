<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel2;

use DateTimeImmutable;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\CredentialFormatIdentifiersEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\VcDataModelException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\TypeClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcAtContextClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSubjectClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcIssuerClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Factories\VcDataModelClaimFactory;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\SdJwtVcJson;

#[\PHPUnit\Framework\Attributes\CoversClass(SdJwtVcJson::class)]
final class SdJwtVcJsonTest extends TestCase
{
    /** @var \Jose\Component\Signature\Signature&\PHPUnit\Framework\MockObject\MockObject */
    protected MockObject $signatureMock;

    /** @var \SimpleSAML\OpenID\Jws\JwsDecorator&\PHPUnit\Framework\MockObject\MockObject */
    protected MockObject $jwsDecoratorMock;

    /** @var \SimpleSAML\OpenID\Jws\JwsVerifierDecorator&\PHPUnit\Framework\MockObject\Stub */
    protected \PHPUnit\Framework\MockObject\Stub $jwsVerifierDecoratorMock;

    /** @var \SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory&\PHPUnit\Framework\MockObject\Stub */
    protected \PHPUnit\Framework\MockObject\Stub $jwksDecoratorFactoryMock;

    /** @var \SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator&\PHPUnit\Framework\MockObject\Stub */
    protected \PHPUnit\Framework\MockObject\Stub $jwsSerializerManagerDecoratorMock;

    /** @var \SimpleSAML\OpenID\Decorators\DateIntervalDecorator&\PHPUnit\Framework\MockObject\Stub */
    protected \PHPUnit\Framework\MockObject\Stub $dateIntervalDecoratorMock;

    /** @var \SimpleSAML\OpenID\Helpers&\PHPUnit\Framework\MockObject\MockObject */
    protected MockObject $helpersMock;

    /** @var \SimpleSAML\OpenID\Helpers\Json&\PHPUnit\Framework\MockObject\MockObject */
    protected MockObject $jsonHelperMock;

    /** @var \SimpleSAML\OpenID\Helpers\DateTime&\PHPUnit\Framework\MockObject\MockObject */
    protected MockObject $dateTimeHelperMock;

    /** @var \SimpleSAML\OpenID\Factories\ClaimFactory&\PHPUnit\Framework\MockObject\Stub */
    protected \PHPUnit\Framework\MockObject\Stub $claimFactoryMock;

    protected array $validPayload = [
        "@context" => [
            "https://www.w3.org/2018/credentials/v1",
            "https://www.w3.org/2018/credentials/examples/v1",
        ],
        "id" => "https://credential-issuer.example.com/credentials/3732",
        "type" => [
            "VerifiableCredential",
            "UniversityDegreeCredential",
        ],
        "issuer" => "https://credential-issuer.example.com",
        "validFrom" => "2025-01-01T00:00:00Z",
        "validUntil" => "2026-01-01T00:00:00Z",
        "credentialSubject" => [
            "id" => "did:example:123",
            "degree" => [
                "type" => "BachelorDegree",
                "name" => "Bachelor of Science and Arts",
            ],
        ],
        "iss" => "https://credential-issuer.example.com",
        "nbf" => 1735689600,
        "exp" => 1767225600,
        "jti" => "https://credential-issuer.example.com/credentials/3732",
        "sub" => "did:example:123",
    ];

    protected array $sampleHeader = [
        'alg' => 'ES256',
        'typ' => 'vc+sd-jwt',
        'kid' => 'F4VFObNusj3PHmrHxpqh4GNiuFHlfh-2s6xMJ95fLYA',
    ];


    protected function setUp(): void
    {
        $this->signatureMock = $this->createMock(Signature::class);

        $jwsMock = $this->createMock(JWS::class);
        $jwsMock->method('getPayload')->willReturn('json-payload-string');
        $jwsMock->method('getSignature')->willReturn($this->signatureMock);

        $this->jwsDecoratorMock = $this->createMock(JwsDecorator::class);
        $this->jwsDecoratorMock->method('jws')->willReturn($jwsMock);

        $this->jwsVerifierDecoratorMock = $this->createStub(JwsVerifierDecorator::class);
        $this->jwksDecoratorFactoryMock = $this->createStub(JwksDecoratorFactory::class);
        $this->jwsSerializerManagerDecoratorMock = $this->createStub(JwsSerializerManagerDecorator::class);
        $this->dateIntervalDecoratorMock = $this->createStub(DateIntervalDecorator::class);

        $this->helpersMock = $this->createMock(Helpers::class);
        $this->jsonHelperMock = $this->createMock(Helpers\Json::class);
        $this->helpersMock->method('json')->willReturn($this->jsonHelperMock);
        $typeHelperMock = $this->createMock(Helpers\Type::class);
        $this->helpersMock->method('type')->willReturn($typeHelperMock);
        $arrHelperMock = $this->createMock(Helpers\Arr::class);
        $this->helpersMock->method('arr')->willReturn($arrHelperMock);
        $this->dateTimeHelperMock = $this->createMock(Helpers\DateTime::class);
        $this->helpersMock->method('dateTime')->willReturn($this->dateTimeHelperMock);

        $typeHelperMock->method('ensureNonEmptyString')->willReturnArgument(0);
        $typeHelperMock->method('ensureInt')->willReturnArgument(0);
        $typeHelperMock->method('ensureArray')->willReturnArgument(0);
        $typeHelperMock->method('enforceUri')->willReturnArgument(0);
        $typeHelperMock->method('enforceNonEmptyArrayOfNonEmptyArrays')->willReturnArgument(0);

        $arrHelperMock->method('getNestedValue')
            ->willReturnCallback(
                fn(array $array, string $key, string $key2): mixed => $array[$key][$key2] ?? null,
            );

        $this->claimFactoryMock = $this->createStub(ClaimFactory::class);

        $this->validPayload['exp'] = time() + 3600;
        $this->validPayload['validUntil'] = (new \DateTimeImmutable())
            ->modify('+1 hour')
            ->format(\DateTimeInterface::ATOM);
    }


    protected function sut(): SdJwtVcJson
    {
        return new SdJwtVcJson(
            $this->jwsDecoratorMock,
            $this->jwsVerifierDecoratorMock,
            $this->jwksDecoratorFactoryMock,
            $this->jwsSerializerManagerDecoratorMock,
            $this->dateIntervalDecoratorMock,
            $this->helpersMock,
            $this->claimFactoryMock,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->assertInstanceOf(SdJwtVcJson::class, $this->sut());
    }


    public function testGetPropertiesReturnTypes(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $sut = $this->sut();

        $vcDataModelClaimFactoryMock = $this->createStub(VcDataModelClaimFactory::class);
        $vcDataModelClaimFactoryMock->method('buildVcAtContextClaimValue')
            ->willReturn($this->createStub(VcAtContextClaimValue::class));
        $vcDataModelClaimFactoryMock->method('buildTypeClaimValue')
            ->willReturn($this->createStub(TypeClaimValue::class));
        $vcDataModelClaimFactoryMock->method('buildVcCredentialSubjectClaimBag')
            ->willReturn($this->createStub(VcCredentialSubjectClaimBag::class));
        $vcDataModelClaimFactoryMock->method('buildVcIssuerClaimValue')
            ->willReturn($this->createStub(VcIssuerClaimValue::class));

        $this->claimFactoryMock->method('forVcDataModel')->willReturn($vcDataModelClaimFactoryMock);

        $this->dateTimeHelperMock->method('fromXsDateTime')->willReturn(new DateTimeImmutable());

        $this->assertInstanceOf(VcAtContextClaimValue::class, $sut->getVcAtContext());
        $this->assertIsString($sut->getVcId());
        $this->assertInstanceOf(TypeClaimValue::class, $sut->getVcType());
        $this->assertInstanceOf(VcCredentialSubjectClaimBag::class, $sut->getVcCredentialSubject());
        $this->assertInstanceOf(VcIssuerClaimValue::class, $sut->getVcIssuer());
        $this->assertInstanceOf(DateTimeImmutable::class, $sut->getValidFrom());
        $this->assertInstanceOf(DateTimeImmutable::class, $sut->getValidUntil());

        // Assert aliases
        $this->assertInstanceOf(DateTimeImmutable::class, $sut->getVcIssuanceDate());
        $this->assertInstanceOf(DateTimeImmutable::class, $sut->getVcExpirationDate());

        $this->assertSame(CredentialFormatIdentifiersEnum::VcSdJwt, $sut->getCredentialFormatIdentifier());
    }


    public function testThrowsIfVcClaimExists(): void
    {
        $this->expectException(VcDataModelException::class);
        $this->expectExceptionMessage('MUST NOT contain a "vc" claim');

        $payload = $this->validPayload;
        $payload['vc'] = ['test'];

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->sut();
    }


    public function testThrowsIfVpClaimExists(): void
    {
        $this->expectException(VcDataModelException::class);
        $this->expectExceptionMessage('MUST NOT contain a "vp" claim');

        $payload = $this->validPayload;
        $payload['vp'] = ['test'];

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->sut();
    }
}
