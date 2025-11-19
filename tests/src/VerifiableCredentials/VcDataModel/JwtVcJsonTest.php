<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel;

use DateTimeImmutable;
use DateTimeInterface;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\CredentialFormatIdentifiersEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\TypeClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcAtContextClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSchemaClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialStatusClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSubjectClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcEvidenceClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcIssuerClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcProofClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcRefreshServiceClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcTermsOfUseClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\JwtVcJson;

#[\PHPUnit\Framework\Attributes\CoversClass(JwtVcJson::class)]
final class JwtVcJsonTest extends TestCase
{
    protected MockObject $signatureMock;

    protected MockObject $jwsDecoratorMock;

    protected MockObject $jwsVerifierDecoratorMock;

    protected MockObject $jwksDecoratorFactoryMock;

    protected MockObject $jwsSerializerManagerDecoratorMock;

    protected MockObject $dateIntervalDecoratorMock;

    protected MockObject $helpersMock;

    protected MockObject $jsonHelperMock;

    protected MockObject $dateTimeHelperMock;

    protected MockObject $claimFactoryMock;

    protected array $expiredPayload = [
        "vc" => [
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
            "issuanceDate" => "2025-01-01T00:00:00Z",
            "expirationDate" => "2025-01-01T00:00:00Z",
            "credentialSubject" => [
                // phpcs:ignore Generic.Files.LineLength.TooLong
                "id" => "did:jwk:eyJraWQiOiJ1cm46aWV0ZjpwYXJhbXM6b2F1dGg6andrLXRodW1icHJpbnQ6c2hhLTI1NjpWYkpPU3ZqeFU2TDhDN0dVTzRkc2hJWVYzemJ2RndrWUI0M1lKNUt0dDhFIiwia3R5IjoiRUMiLCJjcnYiOiJQLTI1NiIsImFsZyI6IkVTMjU2IiwieCI6Ik1kQy1PS3E0QVFKZlZDWDV6cFFvTDhqNFZFZnZQWDk4dFU5aHhjTlhHcm8iLCJ5IjoibnNXbmZiNk5Xc0szOUJILWhBYVNrQ1NlNEJ5bWVOc2NKRV9zYUQzRDNiTSJ9",
                "degree" => [
                    "type" => "BachelorDegree",
                    "name" => "Bachelor of Science and Arts",
                ],
            ],
        ],
        "iss" => "https://credential-issuer.example.com",
        "nbf" => 1735689600,
        "jti" => "https://credential-issuer.example.com/credentials/3732",
        // phpcs:ignore Generic.Files.LineLength.TooLong
        "sub" => "did:jwk:eyJraWQiOiJ1cm46aWV0ZjpwYXJhbXM6b2F1dGg6andrLXRodW1icHJpbnQ6c2hhLTI1NjpWYkpPU3ZqeFU2TDhDN0dVTzRkc2hJWVYzemJ2RndrWUI0M1lKNUt0dDhFIiwia3R5IjoiRUMiLCJjcnYiOiJQLTI1NiIsImFsZyI6IkVTMjU2IiwieCI6Ik1kQy1PS3E0QVFKZlZDWDV6cFFvTDhqNFZFZnZQWDk4dFU5aHhjTlhHcm8iLCJ5IjoibnNXbmZiNk5Xc0szOUJILWhBYVNrQ1NlNEJ5bWVOc2NKRV9zYUQzRDNiTSJ9",
    ];

    protected array $sampleHeader = [
        'alg' => 'ES256',
        'typ' => 'JWT',
        'kid' => 'F4VFObNusj3PHmrHxpqh4GNiuFHlfh-2s6xMJ95fLYA',
    ];

    protected array $validPayload;


    protected function setUp(): void
    {
        $this->signatureMock = $this->createMock(Signature::class);

        $jwsMock = $this->createMock(JWS::class);
        $jwsMock->method('getPayload')
            ->willReturn('json-payload-string'); // Just so we have non-empty value.
        $jwsMock->method('getSignature')->willReturn($this->signatureMock);

        $this->jwsDecoratorMock = $this->createMock(JwsDecorator::class);
        $this->jwsDecoratorMock->method('jws')->willReturn($jwsMock);

        $this->jwsVerifierDecoratorMock = $this->createMock(JwsVerifierDecorator::class);
        $this->jwksDecoratorFactoryMock = $this->createMock(JwksDecoratorFactory::class);
        $this->jwsSerializerManagerDecoratorMock = $this->createMock(JwsSerializerManagerDecorator::class);
        $this->dateIntervalDecoratorMock = $this->createMock(DateIntervalDecorator::class);

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

        $arrHelperMock->method('getNestedValue')
            ->willReturnCallback(fn(
                array $array,
                string $key,
                string $key2,
            ): mixed => $array[$key][$key2] ?? null);

        $this->claimFactoryMock = $this->createMock(ClaimFactory::class);

        $this->validPayload = $this->expiredPayload;
        $this->validPayload['exp'] = time() + 3600;
        $this->validPayload['vc']['expirationDate'] = (new DateTimeImmutable())->modify('+1 hour')
            ->format(DateTimeInterface::ATOM);
    }


    protected function sut(
        ?JwsDecorator $jwsDecorator = null,
        ?JwsVerifierDecorator $jwsVerifierDecorator = null,
        ?JwksDecoratorFactory $jwksDecoratorFactory = null,
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
        ?ClaimFactory $claimFactory = null,
    ): JwtVcJson {
        $jwsDecorator ??= $this->jwsDecoratorMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksDecoratorFactory ??= $this->jwksDecoratorFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;
        $claimFactory ??= $this->claimFactoryMock;

        return new JwtVcJson(
            $jwsDecorator,
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
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->assertInstanceOf(
            JwtVcJson::class,
            $this->sut(),
        );
    }


    public function testCanGetProperties(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $sut = $this->sut();
        $this->assertSame(CredentialFormatIdentifiersEnum::JwtVcJson, $sut->getCredentialFormatIdentifier());
        $this->assertInstanceOf(VcClaimValue::class, $sut->getVc());
        $this->assertInstanceOf(VcAtContextClaimValue::class, $sut->getVcAtContext());
        $this->assertIsString($sut->getVcId());
        $this->assertInstanceOf(TypeClaimValue::class, $sut->getVcType());
        $this->assertInstanceOf(VcCredentialSubjectClaimBag::class, $sut->getVcCredentialSubject());
        $this->assertInstanceOf(VcIssuerClaimValue::class, $sut->getVcIssuer());
        $this->assertInstanceOf(\DateTimeImmutable::class, $sut->getVcIssuanceDate());
        $this->assertInstanceOf(\DateTimeImmutable::class, $sut->getVcExpirationDate());
    }


    public function testThrowsIfNoVcClaimInPayload(): void
    {
        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('VC');

        $payload = $this->validPayload;
        unset($payload['vc']);

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->sut();
    }


    public function testThrowsForInvalidBaseContext(): void
    {
        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('context');

        $payload = $this->validPayload;
        $payload['vc']['@context'] = [123];

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->sut();
    }


    public function testJwtIdCanBeNull(): void
    {
        $payload = $this->validPayload;
        unset($payload['jti']);

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $sut = $this->sut();
        $this->assertNull($sut->getJwtId());
        $this->assertIsString($sut->getVcId());
    }


    public function testVcIdCanBeNull(): void
    {
        $payload = $this->validPayload;
        unset($payload['jti']);
        unset($payload['vc']['id']);

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $sut = $this->sut();
        $this->assertNull($sut->getVcId());
    }


    public function testIssCanBeNull(): void
    {
        $payload = $this->validPayload;
        unset($payload['iss']);

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $sut = $this->sut();
        $this->assertNull($sut->getIssuer());
        $this->assertInstanceOf(VcIssuerClaimValue::class, $sut->getVcIssuer());
    }


    public function testThrowsOnMissingIssuer(): void
    {
        $payload = $this->validPayload;
        unset($payload['iss']);
        unset($payload['vc']['issuer']);

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Issuer');

        $this->sut();
    }


    public function testCanHaveMultipleIssuers(): void
    {
        $payload = $this->validPayload;
        unset($payload['iss']);
        $payload['vc']['issuer'] = ['https://issuer1.example.com', 'https://issuer2.example.com'];

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->assertInstanceOf(VcIssuerClaimValue::class, $this->sut()->getVcIssuer());
    }


    public function testThrowsOnMalformedIssuer(): void
    {
        $payload = $this->validPayload;
        unset($payload['iss']);
        $payload['vc']['issuer'] = 123;

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Issuer');

        $this->sut();
    }


    public function testNbfCanBeNull(): void
    {
        $payload = $this->validPayload;
        unset($payload['nbf']);

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->assertNull($this->sut()->getNotBefore());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->sut()->getVcIssuanceDate());
    }


    public function testThrowsOnInvalidNbf(): void
    {
        $this->dateTimeHelperMock->method('fromTimestamp')
            ->willThrowException(new \Exception('Error'));

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Not Before');

        $this->sut();
    }


    public function testThrowsOnInvalidIssuanceDate(): void
    {
        $payload = $this->validPayload;
        unset($payload['nbf']);
        unset($payload['vc']['issuanceDate']);

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Issuance Date');

        $this->sut();
    }


    public function testThrowsOnParseIssuanceDateError(): void
    {
        $payload = $this->validPayload;
        unset($payload['nbf']);

        $this->dateTimeHelperMock->method('fromXsDateTime')
            ->willThrowException(new \Exception('Error'));

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);

        $this->sut()->getVcIssuanceDate();
    }


    public function testCanGetProof(): void
    {
        $payload = $this->validPayload;
        $payload['vc']['proof'] = ['type' => 'type'];

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->assertInstanceOf(
            VcProofClaimValue::class,
            $this->sut()->getVcProof(),
        );
    }


    public function testThrowsOnMalformedProof(): void
    {
        $payload = $this->validPayload;
        $payload['vc']['proof'] = 123;

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Proof');

        $this->sut();
    }


    public function testExpCanBeNull(): void
    {
        $payload = $this->validPayload;
        unset($payload['exp']);

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $sut = $this->sut();
        $this->assertNull($sut->getExpirationTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $sut->getVcExpirationDate());
    }


    public function testExpirationDateCanBeNull(): void
    {
        $payload = $this->validPayload;
        unset($payload['exp']);
        unset($payload['vc']['expirationDate']);

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->assertNotInstanceOf(\DateTimeImmutable::class, $this->sut()->getVcExpirationDate());
    }


    public function testThrowsOnParseExpirationDateError(): void
    {
        $payload = $this->validPayload;
        unset($payload['exp']);


        $this->dateTimeHelperMock->method('fromXsDateTime')
            ->willThrowException(new \Exception('Error'));

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);

        $this->sut()->getVcExpirationDate();
    }


    public function testThrowsOnMalformedExpirationDate(): void
    {
        $payload = $this->validPayload;
        unset($payload['exp']);
        $payload['vc']['expirationDate'] = 123;

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);

        $this->sut()->getVcExpirationDate();
    }


    public function testCanGetCredentialStatus(): void
    {
        $payload = $this->validPayload;
        $payload['vc']['credentialStatus'] = ['id' => 'id', 'type' => 'type'];

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->assertInstanceOf(VcCredentialStatusClaimValue::class, $this->sut()->getVcCredentialStatus());
    }


    public function testThrowsOnMalformedCredentialStatus(): void
    {
        $payload = $this->validPayload;
        $payload['vc']['credentialStatus'] = 123;

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Credential Status');

        $this->sut();
    }


    public function testCanGetCredentialSchema(): void
    {
        $payload = $this->validPayload;
        $payload['vc']['credentialSchema'] = ['id' => 'id', 'type' => 'type'];

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->assertInstanceOf(VcCredentialSchemaClaimBag::class, $this->sut()->getVcCredentialSchema());
    }


    public function testThrowsOnMalformedCredentialSchema(): void
    {
        $payload = $this->validPayload;
        $payload['vc']['credentialSchema'] = 123;

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Credential Schema');

        $this->sut();
    }


    public function testCanGetRefreshService(): void
    {
        $payload = $this->validPayload;
        $payload['vc']['refreshService'] = ['id' => 'id', 'type' => 'type'];

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->assertInstanceOf(VcRefreshServiceClaimBag::class, $this->sut()->getVcRefreshService());
    }


    public function testThrowsOnMalformedRefreshService(): void
    {
        $payload = $this->validPayload;
        $payload['vc']['refreshService'] = 123;

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Refresh Service');

        $this->sut();
    }


    public function testCanGetTermsOfUse(): void
    {
        $payload = $this->validPayload;
        $payload['vc']['termsOfUse'] = ['type' => 'type'];

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->assertInstanceOf(VcTermsOfUseClaimBag::class, $this->sut()->getVcTermsOfUse());
    }


    public function testThrowsOnMalformedTermsOfUse(): void
    {
        $payload = $this->validPayload;
        $payload['vc']['termsOfUse'] = 123;

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Terms Of Use');

        $this->sut();
    }


    public function testCanGetEvidence(): void
    {
        $payload = $this->validPayload;
        $payload['vc']['evidence'] = ['type' => 'type'];

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->assertInstanceOf(VcEvidenceClaimBag::class, $this->sut()->getVcEvidence());
    }


    public function testThrowsOnMalformedEvidence(): void
    {
        $payload = $this->validPayload;
        $payload['vc']['evidence'] = 123;

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Evidence');

        $this->sut()->getVcEvidence();
    }
}
