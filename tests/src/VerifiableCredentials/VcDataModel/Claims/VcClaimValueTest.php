<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

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
    protected \PHPUnit\Framework\MockObject\Stub $vcAtContextClaimValueMock;

    protected string $id = 'id';

    protected \PHPUnit\Framework\MockObject\Stub $typeClaimValueMock;

    protected \PHPUnit\Framework\MockObject\Stub $vcCredentialSubjectClaimBagMock;

    protected \PHPUnit\Framework\MockObject\Stub $issuerClaimValueMock;

    protected \PHPUnit\Framework\MockObject\Stub $issuanceDateMock;

    protected \PHPUnit\Framework\MockObject\Stub $proofClaimValueMock;

    protected \PHPUnit\Framework\MockObject\Stub $expirationDateMock;

    protected \PHPUnit\Framework\MockObject\Stub $credentialStatusClaimValueMock;

    protected \PHPUnit\Framework\MockObject\Stub $credentialSchemaClaimBagMock;

    protected \PHPUnit\Framework\MockObject\Stub $refreshServiceClaimBagMock;

    protected \PHPUnit\Framework\MockObject\Stub $termsOfUserClaimBagMock;

    protected \PHPUnit\Framework\MockObject\Stub $evidenceClaimBagMock;


    protected function setUp(): void
    {
        $this->vcAtContextClaimValueMock = $this->createStub(VcAtContextClaimValue::class);
        $this->typeClaimValueMock = $this->createStub(TypeClaimValue::class);
        $this->vcCredentialSubjectClaimBagMock = $this->createStub(VcCredentialSubjectClaimBag::class);
        $this->issuerClaimValueMock = $this->createStub(VcIssuerClaimValue::class);
        $this->issuanceDateMock = $this->createStub(\DateTimeImmutable::class);
        $this->proofClaimValueMock = $this->createStub(VcProofClaimValue::class);
        $this->expirationDateMock = $this->createStub(\DateTimeImmutable::class);
        $this->credentialStatusClaimValueMock = $this->createStub(VcCredentialStatusClaimValue::class);
        $this->credentialSchemaClaimBagMock = $this->createStub(VcCredentialSchemaClaimBag::class);
        $this->refreshServiceClaimBagMock = $this->createStub(VcRefreshServiceClaimBag::class);
        $this->termsOfUserClaimBagMock = $this->createStub(VcTermsOfUseClaimBag::class);
        $this->evidenceClaimBagMock = $this->createStub(VcEvidenceClaimBag::class);
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
