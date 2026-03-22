<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel2;

use DateTimeImmutable;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\CredentialFormatIdentifiersEnum;
use SimpleSAML\OpenID\Exceptions\InvalidValueException;
use SimpleSAML\OpenID\Exceptions\VcDataModelException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\LocalizableStringValueBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\TypeClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcAtContextClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSchemaClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSubjectClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcEvidenceClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcIssuerClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcProofClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcTermsOfUseClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims\VcCredentialStatusClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims\VcRefreshServiceClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Factories\VcDataModel2ClaimFactory;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\VcSdJwt;

#[\PHPUnit\Framework\Attributes\CoversClass(VcSdJwt::class)]
#[UsesClass(\SimpleSAML\OpenID\Helpers\DateTime::class)]
final class VcSdJwtTest extends TestCase
{
    /** @var \Jose\Component\Signature\Signature&\PHPUnit\Framework\MockObject\MockObject */
    protected MockObject $signatureMock;

    /** @var \SimpleSAML\OpenID\Jws\JwsDecorator&\PHPUnit\Framework\MockObject\MockObject */
    protected MockObject $jwsDecoratorMock;

    /** @var \SimpleSAML\OpenID\Helpers&\PHPUnit\Framework\MockObject\MockObject */
    protected MockObject $helpersMock;

    /** @var \SimpleSAML\OpenID\Helpers\Json&\PHPUnit\Framework\MockObject\MockObject */
    protected MockObject $jsonHelperMock;

    /** @var \SimpleSAML\OpenID\Factories\ClaimFactory&\PHPUnit\Framework\MockObject\Stub */
    protected \PHPUnit\Framework\MockObject\Stub $claimFactoryMock;

    /** @var \SimpleSAML\OpenID\Helpers\Type&\PHPUnit\Framework\MockObject\MockObject */
    protected MockObject $typeHelperMock;

    /** @var \SimpleSAML\OpenID\Helpers\DateTime&\PHPUnit\Framework\MockObject\MockObject */
    protected MockObject $dateTimeHelperMock;

    protected array $expiredPayload = [
        "@context" => [
            "https://www.w3.org/ns/credentials/v2",
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

    protected array $validPayload;

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

        $this->helpersMock = $this->createMock(Helpers::class);
        $this->jsonHelperMock = $this->createMock(Helpers\Json::class);
        $this->helpersMock->method('json')->willReturn($this->jsonHelperMock);
        $this->typeHelperMock = $this->createMock(Helpers\Type::class);
        $this->helpersMock->method('type')->willReturn($this->typeHelperMock);
        $arrHelperMock = $this->createMock(Helpers\Arr::class);
        $this->helpersMock->method('arr')->willReturn($arrHelperMock);
        $this->dateTimeHelperMock = $this->createMock(Helpers\DateTime::class);
        $this->helpersMock->method('dateTime')->willReturn($this->dateTimeHelperMock);

        $realDateTimeHelper = new Helpers\DateTime();
        $this->dateTimeHelperMock->method('fromXsDateTime')
            ->willReturnCallback(fn(string $input): \DateTimeImmutable => $realDateTimeHelper->fromXsDateTime($input));
        $this->dateTimeHelperMock->method('fromTimestamp')
            ->willReturnCallback(
                fn(int $timestamp): \DateTimeImmutable => $realDateTimeHelper->fromTimestamp($timestamp),
            );

        $this->typeHelperMock->method('ensureNonEmptyString')->willReturnArgument(0);
        $this->typeHelperMock->method('ensureInt')->willReturnArgument(0);
        $this->typeHelperMock->method('ensureArray')->willReturnArgument(0);
        $this->typeHelperMock->method('enforceUri')->willReturnArgument(0);
        $this->typeHelperMock->method('enforceNonEmptyArrayOfNonEmptyArrays')->willReturnArgument(0);

        $arrHelperMock->method('getNestedValue')
            ->willReturnCallback(
                fn(array $array, string $key, string $key2): mixed => $array[$key][$key2] ?? null,
            );

        $this->claimFactoryMock = $this->createStub(ClaimFactory::class);

        $this->validPayload = $this->expiredPayload;

        $this->validPayload['exp'] = time() + 3600;
        $this->validPayload['validUntil'] = (new \DateTimeImmutable())
            ->modify('+1 hour')
            ->format(\DateTimeInterface::ATOM);
    }


    protected function sut(): VcSdJwt
    {
        $leewayMock = $this->createMock(\SimpleSAML\OpenID\Decorators\DateIntervalDecorator::class);
        $leewayMock->method('getInSeconds')->willReturn(0);

        return new VcSdJwt(
            $this->jwsDecoratorMock,
            $this->createStub(\SimpleSAML\OpenID\Jws\JwsVerifierDecorator::class),
            $this->createStub(\SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory::class),
            $this->createStub(\SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator::class),
            $leewayMock,
            $this->helpersMock,
            $this->claimFactoryMock,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->assertInstanceOf(VcSdJwt::class, $this->sut());
    }


    public function testGetPropertiesReturnTypes(): void
    {
        $vcDataModelClaimFactoryMock = $this->createStub(VcDataModel2ClaimFactory::class);
        $vcDataModelClaimFactoryMock->method('buildVcAtContextClaimValue')
            ->willReturn($this->createStub(VcAtContextClaimValue::class));
        $vcDataModelClaimFactoryMock->method('buildTypeClaimValue')
            ->willReturn($this->createStub(TypeClaimValue::class));
        $vcDataModelClaimFactoryMock->method('buildVcCredentialSubjectClaimBag')
            ->willReturn($this->createStub(VcCredentialSubjectClaimBag::class));
        $vcDataModelClaimFactoryMock->method('buildVcIssuerClaimValue')
            ->willReturn($this->createStub(VcIssuerClaimValue::class));

        $this->claimFactoryMock->method('forVcDataModel2')->willReturn($vcDataModelClaimFactoryMock);

        $vcDataModelClaimFactoryMock->method('buildVcProofClaimValue')
            ->willReturn($this->createStub(VcProofClaimValue::class));
        $vcDataModelClaimFactoryMock->method('buildVcCredentialStatusClaimBag')
            ->willReturn($this->createStub(VcCredentialStatusClaimBag::class));
        $vcDataModelClaimFactoryMock->method('buildVcCredentialSchemaClaimBag')
            ->willReturn($this->createStub(VcCredentialSchemaClaimBag::class));
        $vcDataModelClaimFactoryMock->method('buildVcRefreshServiceClaimBag2')
            ->willReturn($this->createStub(VcRefreshServiceClaimBag::class));
        $vcDataModelClaimFactoryMock->method('buildVcTermsOfUseClaimBag')
            ->willReturn($this->createStub(VcTermsOfUseClaimBag::class));
        $vcDataModelClaimFactoryMock->method('buildVcEvidenceClaimBag')
            ->willReturn($this->createStub(VcEvidenceClaimBag::class));
        $vcDataModelClaimFactoryMock->method('buildLocalizableStringValueBag')
            ->willReturn($this->createStub(LocalizableStringValueBag::class));

        $this->validPayload['proof'] = ['test'];
        $this->validPayload['credentialStatus'] = ['test'];
        $this->validPayload['credentialSchema'] = ['test'];
        $this->validPayload['refreshService'] = ['test'];
        $this->validPayload['termsOfUse'] = ['test'];
        $this->validPayload['evidence'] = ['test'];
        $this->validPayload['name'] = 'test';
        $this->validPayload['description'] = 'test';

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $sut = $this->sut();

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

        $this->assertInstanceOf(VcProofClaimValue::class, $sut->getVcProof());
        $this->assertInstanceOf(VcCredentialStatusClaimBag::class, $sut->getVcCredentialStatus());
        $this->assertInstanceOf(VcCredentialSchemaClaimBag::class, $sut->getVcCredentialSchema());
        $this->assertInstanceOf(VcRefreshServiceClaimBag::class, $sut->getVcRefreshService());
        $this->assertInstanceOf(VcTermsOfUseClaimBag::class, $sut->getVcTermsOfUse());
        $this->assertInstanceOf(VcEvidenceClaimBag::class, $sut->getVcEvidence());
        $this->assertInstanceOf(LocalizableStringValueBag::class, $sut->getVcName());
        $this->assertInstanceOf(LocalizableStringValueBag::class, $sut->getVcDescription());
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


    public function testGetPropertiesReturnNullIfMissing(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $payload = $this->validPayload;
        unset($payload['id']);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $sut = $this->sut();

        $this->assertNull($sut->getVcId());
        $this->assertNotInstanceOf(
            \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcProofClaimValue::class,
            $sut->getVcProof(),
        );
        $this->assertNotInstanceOf(
            \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims\VcCredentialStatusClaimBag::class,
            $sut->getVcCredentialStatus(),
        );
        $this->assertNotInstanceOf(
            \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSchemaClaimBag::class,
            $sut->getVcCredentialSchema(),
        );
        $this->assertNotInstanceOf(
            \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims\VcRefreshServiceClaimBag::class,
            $sut->getVcRefreshService(),
        );
        $this->assertNotInstanceOf(
            \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcTermsOfUseClaimBag::class,
            $sut->getVcTermsOfUse(),
        );
        $this->assertNotInstanceOf(
            \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcEvidenceClaimBag::class,
            $sut->getVcEvidence(),
        );
        $this->assertNotInstanceOf(
            \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\LocalizableStringValueBag::class,
            $sut->getVcName(),
        );
        $this->assertNotInstanceOf(
            \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\LocalizableStringValueBag::class,
            $sut->getVcDescription(),
        );
    }


    public function testGetPropertiesThrowsIfInvalidType(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $invalidPayload = $this->validPayload;
        $invalidPayload['@context'] = 'not-an-array';

        $this->jsonHelperMock->method('decode')->willReturn($invalidPayload);

        $sut = $this->sut();

        $this->expectException(VcDataModelException::class);
        $this->expectExceptionMessage('Invalid @context claim.');
        $sut->getVcAtContext();
    }


    public function testGetVcAtContextThrowsIfBaseContextNotString(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $invalidPayload = $this->validPayload;
        $invalidPayload['@context'] = [['not-a-string']];

        $this->jsonHelperMock->method('decode')->willReturn($invalidPayload);

        $sut = $this->sut();

        $this->expectException(VcDataModelException::class);
        $this->expectExceptionMessage('Invalid @context claim.');
        $sut->getVcAtContext();
    }


    public function testGetVcTypeThrowsIfMissing(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $invalidPayload = $this->validPayload;
        unset($invalidPayload['type']);
        unset($invalidPayload['@type']);

        $this->jsonHelperMock->method('decode')->willReturn($invalidPayload);

        $sut = $this->sut();

        $this->expectException(VcDataModelException::class);
        $this->expectExceptionMessage('Invalid Type claim.');
        $sut->getVcType();
    }


    public function testGetVcCredentialSubjectThrowsIfMissing(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $invalidPayload = $this->validPayload;
        unset($invalidPayload['credentialSubject']);

        $this->jsonHelperMock->method('decode')->willReturn($invalidPayload);

        $sut = $this->sut();

        $this->expectException(VcDataModelException::class);
        $this->expectExceptionMessage('Invalid Credential Subject claim.');
        $sut->getVcCredentialSubject();
    }


    public function testGetVcIssuerThrowsIfMissing(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $invalidPayload = $this->validPayload;
        unset($invalidPayload['issuer']);
        unset($invalidPayload['iss']);

        $this->jsonHelperMock->method('decode')->willReturn($invalidPayload);

        $sut = $this->sut();

        $this->expectException(VcDataModelException::class);
        $this->expectExceptionMessage('Invalid Issuer claim.');
        $sut->getVcIssuer();
    }


    public function testGetVcIssuerThrowsIfInvalidType(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $invalidPayload = $this->validPayload;
        $invalidPayload['issuer'] = 123;

        $this->jsonHelperMock->method('decode')->willReturn($invalidPayload);

        $sut = $this->sut();

        $this->expectException(VcDataModelException::class);
        $this->expectExceptionMessage('Invalid Issuer claim.');
        $sut->getVcIssuer();
    }


    public function testGetValidFromThrowsIfMissing(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $invalidPayload = $this->validPayload;
        unset($invalidPayload['validFrom']);
        unset($invalidPayload['issuanceDate']);
        unset($invalidPayload['nbf']);
        unset($invalidPayload['iat']);

        $this->jsonHelperMock->method('decode')->willReturn($invalidPayload);

        $this->expectException(VcDataModelException::class);
        $this->expectExceptionMessage('Valid From claim is missing.');

        $this->sut();
    }


    public function testGetValidFromThrowsIfFuture(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $invalidPayload = $this->validPayload;
        $invalidPayload['validFrom'] = (new DateTimeImmutable())
            ->modify('+1 day')->format(\DateTimeInterface::ATOM);

        $this->jsonHelperMock->method('decode')->willReturn($invalidPayload);

        $this->expectException(VcDataModelException::class);
        $this->expectExceptionMessage('Credential is not valid yet.');

        $this->sut();
    }


    public function testGetValidUntilThrowsIfExpired(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $invalidPayload = $this->validPayload;
        $invalidPayload['validUntil'] = (new DateTimeImmutable())
            ->modify('-1 day')->format(\DateTimeInterface::ATOM);

        $this->jsonHelperMock->method('decode')->willReturn($invalidPayload);

        $this->expectException(VcDataModelException::class);
        $this->expectExceptionMessage('Credential is expired.');

        $this->sut();
    }


    public function testGetVcProofThrowsIfInvalidType(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $invalidPayload = $this->validPayload;
        $invalidPayload['proof'] = 'not-an-array';

        $this->jsonHelperMock->method('decode')->willReturn($invalidPayload);

        $sut = $this->sut();

        $this->expectException(VcDataModelException::class);
        $this->expectExceptionMessage('Invalid Proof claim.');
        $sut->getVcProof();
    }


    public function testCaching(): void
    {
        $vcDataModelClaimFactoryMock = $this->createStub(VcDataModel2ClaimFactory::class);
        $vcDataModelClaimFactoryMock->method('buildVcAtContextClaimValue')
            ->willReturn($this->createStub(VcAtContextClaimValue::class));
        $vcDataModelClaimFactoryMock->method('buildTypeClaimValue')
            ->willReturn($this->createStub(TypeClaimValue::class));
        $vcDataModelClaimFactoryMock->method('buildVcCredentialSubjectClaimBag')
            ->willReturn($this->createStub(VcCredentialSubjectClaimBag::class));
        $vcDataModelClaimFactoryMock->method('buildVcIssuerClaimValue')
            ->willReturn($this->createStub(VcIssuerClaimValue::class));

        $this->claimFactoryMock->method('forVcDataModel2')->willReturn($vcDataModelClaimFactoryMock);

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $sut = $this->sut();

        // Call twice to trigger caching
        $this->assertSame($sut->getVcAtContext(), $sut->getVcAtContext());
        $this->assertSame($sut->getVcType(), $sut->getVcType());
        $this->assertSame($sut->getVcCredentialSubject(), $sut->getVcCredentialSubject());
        $this->assertSame($sut->getVcIssuer(), $sut->getVcIssuer());
        $this->assertSame($sut->getValidFrom(), $sut->getValidFrom());
        $this->assertSame($sut->getValidUntil(), $sut->getValidUntil());
    }


    public function testGetValidFromFromNbf(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $payload = $this->validPayload;
        unset($payload['validFrom']);
        unset($payload['issuanceDate']);
        $payload['nbf'] = time() - 3600;
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $sut = $this->sut();
        $this->assertInstanceOf(DateTimeImmutable::class, $sut->getValidFrom());
    }


    public function testGetValidUntilFromExp(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $payload = $this->validPayload;
        unset($payload['validUntil']);
        unset($payload['expirationDate']);
        $payload['exp'] = time() + 3600;
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $sut = $this->sut();
        $this->assertInstanceOf(DateTimeImmutable::class, $sut->getValidUntil());
    }


    public function testGetVcIssuerFromArray(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $payload = $this->validPayload;
        $payload['issuer'] = ['id' => 'https://example.com'];
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $vcDataModelClaimFactoryMock = $this->createStub(VcDataModel2ClaimFactory::class);
        $vcDataModelClaimFactoryMock->method('buildVcIssuerClaimValue')
            ->willReturn($this->createStub(VcIssuerClaimValue::class));
        $this->claimFactoryMock->method('forVcDataModel2')->willReturn($vcDataModelClaimFactoryMock);

        $sut = $this->sut();
        $this->assertInstanceOf(VcIssuerClaimValue::class, $sut->getVcIssuer());
    }


    public function testGetVcNameThrowsOnException(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->validPayload['name'] = 'test';
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $vcDataModelClaimFactoryMock = $this->createStub(VcDataModel2ClaimFactory::class);
        $vcDataModelClaimFactoryMock->method('buildLocalizableStringValueBag')
            ->willThrowException(new \Exception('error'));
        $this->claimFactoryMock->method('forVcDataModel2')->willReturn($vcDataModelClaimFactoryMock);

        $sut = $this->sut();
        $this->expectException(VcDataModelException::class);
        $this->expectExceptionMessage('Invalid Name claim.');
        $sut->getVcName();
    }


    public function testGetVcDescriptionThrowsOnException(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->validPayload['description'] = 'test';
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $vcDataModelClaimFactoryMock = $this->createStub(VcDataModel2ClaimFactory::class);
        $vcDataModelClaimFactoryMock->method('buildLocalizableStringValueBag')
            ->willThrowException(new \Exception('error'));
        $this->claimFactoryMock->method('forVcDataModel2')->willReturn($vcDataModelClaimFactoryMock);

        $sut = $this->sut();
        $this->expectException(VcDataModelException::class);
        $this->expectExceptionMessage('Invalid Description claim.');
        $sut->getVcDescription();
    }


    public function testGetValidFromFromIssuanceDate(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $payload = $this->validPayload;
        unset($payload['validFrom']);
        $payload['issuanceDate'] = (new DateTimeImmutable())->format(\DateTimeInterface::ATOM);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $sut = $this->sut();
        $this->assertInstanceOf(DateTimeImmutable::class, $sut->getValidFrom());
    }


    public function testGetValidFromFromIat(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $payload = $this->validPayload;
        unset($payload['validFrom']);
        unset($payload['issuanceDate']);
        unset($payload['nbf']);
        $payload['iat'] = time() - 3600;
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $sut = $this->sut();
        $this->assertInstanceOf(DateTimeImmutable::class, $sut->getValidFrom());
    }


    public function testGetValidUntilFromExpirationDate(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $payload = $this->validPayload;
        unset($payload['validUntil']);
        $payload['expirationDate'] = (new DateTimeImmutable())
            ->modify('+1 hour')->format(\DateTimeInterface::ATOM);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $sut = $this->sut();
        $this->assertInstanceOf(DateTimeImmutable::class, $sut->getValidUntil());
    }


    public function testGetVcIssuerFromJoseIss(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $payload = $this->validPayload;
        unset($payload['issuer']);
        // Keep 'iss' as it is used by getIssuer() fallback

        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $vcDataModelClaimFactoryMock = $this->createStub(VcDataModel2ClaimFactory::class);
        $vcDataModelClaimFactoryMock->method('buildVcIssuerClaimValue')
            ->willReturn($this->createStub(VcIssuerClaimValue::class));
        $this->claimFactoryMock->method('forVcDataModel2')->willReturn($vcDataModelClaimFactoryMock);

        $sut = $this->sut();
        $this->assertInstanceOf(VcIssuerClaimValue::class, $sut->getVcIssuer());
    }


    public function testGetVcTypeFromAtType(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $payload = $this->validPayload;
        unset($payload['type']);
        $payload['@type'] = ['VerifiableCredential'];
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $vcDataModelClaimFactoryMock = $this->createStub(VcDataModel2ClaimFactory::class);
        $vcDataModelClaimFactoryMock->method('buildTypeClaimValue')
            ->willReturn($this->createStub(TypeClaimValue::class));
        $this->claimFactoryMock->method('forVcDataModel2')->willReturn($vcDataModelClaimFactoryMock);

        $sut = $this->sut();
        $this->assertInstanceOf(TypeClaimValue::class, $sut->getVcType());
    }


    public function testGetVcIdThrowsOnInvalidUri(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $payload = $this->validPayload;
        $payload['id'] = 'not-a-uri';
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->typeHelperMock->method('enforceUri')->willThrowException(new InvalidValueException('error'));

        $sut = $this->sut();
        $this->expectException(InvalidValueException::class);
        $sut->getVcId();
    }


    public function testGetValidFromThrowsOnTimestampError(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $payload = $this->validPayload;
        unset($payload['validFrom']);
        unset($payload['issuanceDate']);
        $payload['nbf'] = 123;
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->dateTimeHelperMock->method('fromTimestamp')
            ->willThrowException(new \Exception('error'));

        $this->expectException(VcDataModelException::class);
        $this->expectExceptionMessage('Invalid Not Before or Issued At claim.');

        $this->sut();
    }


    public function testGetValidUntilThrowsOnTimestampError(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $payload = $this->validPayload;
        unset($payload['validUntil']);
        unset($payload['expirationDate']);
        $payload['exp'] = time() + 3600;
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->dateTimeHelperMock->method('fromTimestamp')
            ->willThrowException(new \Exception('error'));

        $this->expectException(VcDataModelException::class);
        $this->expectExceptionMessage('Invalid Expiration Time claim.');

        $this->sut();
    }


    public function testGetValidUntilReturnsNullIfMissing(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $payload = $this->validPayload;
        unset($payload['validUntil']);
        unset($payload['expirationDate']);
        unset($payload['exp']);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $sut = $this->sut();
        $this->assertNotInstanceOf(\DateTimeImmutable::class, $sut->getValidUntil());
        // Test caching of false
        $this->assertNull($sut->getValidUntil());
    }


    public function testGetVcIdCaching(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $payload = $this->validPayload;
        unset($payload['id']);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $sut = $this->sut();
        $this->assertNull($sut->getVcId());
        $this->assertNull($sut->getVcId());
    }


    /**
     * @dataProvider invalidBagProvider
     */
    public function testBagsThrowOnInvalidType(string $claim, string $method): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $payload = $this->validPayload;
        $payload[$claim] = 'not-an-array';
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $sut = $this->sut();
        $this->expectException(VcDataModelException::class);
        $sut->$method();
    }


    public static function invalidBagProvider(): \Iterator
    {
        yield ['credentialStatus', 'getVcCredentialStatus'];
        yield ['credentialSchema', 'getVcCredentialSchema'];
        yield ['refreshService', 'getVcRefreshService'];
        yield ['termsOfUse', 'getVcTermsOfUse'];
        yield ['evidence', 'getVcEvidence'];
    }
}
