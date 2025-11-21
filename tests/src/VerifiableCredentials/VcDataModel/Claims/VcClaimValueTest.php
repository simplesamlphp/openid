<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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

#[\PHPUnit\Framework\Attributes\CoversClass(VcClaimValue::class)]
final class VcClaimValueTest extends TestCase
{
    protected MockObject $vcAtContextClaimValueMock;

    protected string $id = 'id';

    protected MockObject $typeClaimValueMock;

    protected MockObject $vcCredentialSubjectClaimBagMock;

    protected MockObject $issuerClaimValueMock;

    protected MockObject $issuanceDateMock;

    protected MockObject $proofClaimValueMock;

    protected MockObject $expirationDateMock;

    protected MockObject $credentialStatusClaimValueMock;

    protected MockObject $credentialSchemaClaimBagMock;

    protected MockObject $refreshServiceClaimBagMock;

    protected MockObject $termsOfUserClaimBagMock;

    protected MockObject $evidenceClaimBagMock;


    protected function setUp(): void
    {
        $this->vcAtContextClaimValueMock = $this->createMock(VcAtContextClaimValue::class);
        $this->typeClaimValueMock = $this->createMock(TypeClaimValue::class);
        $this->vcCredentialSubjectClaimBagMock = $this->createMock(VcCredentialSubjectClaimBag::class);
        $this->issuerClaimValueMock = $this->createMock(VcIssuerClaimValue::class);
        $this->issuanceDateMock = $this->createMock(\DateTimeImmutable::class);
        $this->proofClaimValueMock = $this->createMock(VcProofClaimValue::class);
        $this->expirationDateMock = $this->createMock(\DateTimeImmutable::class);
        $this->credentialStatusClaimValueMock = $this->createMock(VcCredentialStatusClaimValue::class);
        $this->credentialSchemaClaimBagMock = $this->createMock(VcCredentialSchemaClaimBag::class);
        $this->refreshServiceClaimBagMock = $this->createMock(VcRefreshServiceClaimBag::class);
        $this->termsOfUserClaimBagMock = $this->createMock(VcTermsOfUseClaimBag::class);
        $this->evidenceClaimBagMock = $this->createMock(VcEvidenceClaimBag::class);
    }


    protected function sut(
        ?VcAtContextClaimValue $vcAtContextClaimValue = null,
        ?string $id = null,
        ?TypeClaimValue $typeClaimValue = null,
        ?VcCredentialSubjectClaimBag $vcCredentialSubjectClaimBag = null,
        ?VcIssuerClaimValue $vcIssuerClaimValue = null,
        ?\DateTimeImmutable $issuanceDate = null,
        ?VcProofClaimValue $proofClaimValue = null,
        ?\DateTimeImmutable $expirationDate = null,
        ?VcCredentialStatusClaimValue $credentialStatusClaimValue = null,
        ?VcCredentialSchemaClaimBag $credentialSchemaClaimBag = null,
        ?VcRefreshServiceClaimBag $refreshServiceClaimBag = null,
        ?VcTermsOfUseClaimBag $termsOfUserClaimBag = null,
        ?VcEvidenceClaimBag $evidenceClaimBag = null,
    ): VcClaimValue {
        $vcAtContextClaimValue ??= $this->vcAtContextClaimValueMock;
        $id ??= $this->id;
        $typeClaimValue ??= $this->typeClaimValueMock;
        $vcCredentialSubjectClaimBag ??= $this->vcCredentialSubjectClaimBagMock;
        $vcIssuerClaimValue ??= $this->issuerClaimValueMock;
        $issuanceDate ??= $this->issuanceDateMock;
        $proofClaimValue ??= $this->proofClaimValueMock;
        $expirationDate ??= $this->expirationDateMock;
        $credentialStatusClaimValue ??= $this->credentialStatusClaimValueMock;
        $credentialSchemaClaimBag ??= $this->credentialSchemaClaimBagMock;
        $refreshServiceClaimBag ??= $this->refreshServiceClaimBagMock;
        $termsOfUserClaimBag ??= $this->termsOfUserClaimBagMock;
        $evidenceClaimBag ??= $this->evidenceClaimBagMock;

        return new VcClaimValue(
            $vcAtContextClaimValue,
            $id,
            $typeClaimValue,
            $vcCredentialSubjectClaimBag,
            $vcIssuerClaimValue,
            $issuanceDate,
            $proofClaimValue,
            $expirationDate,
            $credentialStatusClaimValue,
            $credentialSchemaClaimBag,
            $refreshServiceClaimBag,
            $termsOfUserClaimBag,
            $evidenceClaimBag,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VcClaimValue::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertSame($this->vcAtContextClaimValueMock, $sut->getAtContext());
        $this->assertSame($this->id, $sut->getId());
        $this->assertSame($this->typeClaimValueMock, $sut->getType());
        $this->assertSame($this->vcCredentialSubjectClaimBagMock, $sut->getCredentialSubject());
        $this->assertSame($this->issuerClaimValueMock, $sut->getIssuer());
        $this->assertSame($this->issuanceDateMock, $sut->getIssuanceDate());
        $this->assertSame($this->proofClaimValueMock, $sut->getProof());
        $this->assertSame($this->expirationDateMock, $sut->getExpirationDate());
        $this->assertSame($this->credentialStatusClaimValueMock, $sut->getCredentialStatus());
        $this->assertSame($this->credentialSchemaClaimBagMock, $sut->getCredentialSchema());
        $this->assertSame($this->refreshServiceClaimBagMock, $sut->getRefreshService());
        $this->assertSame($this->termsOfUserClaimBagMock, $sut->getTermsOfUse());
        $this->assertSame($this->evidenceClaimBagMock, $sut->getEvidence());
        $this->assertSame('vc', $sut->getName());

        $this->assertArrayHasKey('id', $sut->getValue());
        $this->assertArrayHasKey('id', $sut->jsonSerialize());
    }
}
