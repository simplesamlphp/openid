<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Factories;

use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\AtContextsEnum;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\TypeClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcAtContextClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSchemaClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSchemaClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialStatusClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSubjectClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSubjectClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcEvidenceClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcEvidenceClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcIssuerClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcProofClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcRefreshServiceClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcRefreshServiceClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcTermsOfUseClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcTermsOfUseClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Factories\VcDataModelClaimFactory;

#[\PHPUnit\Framework\Attributes\CoversClass(VcDataModelClaimFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcClaimValue::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcAtContextClaimValue::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TypeClaimValue::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcCredentialSubjectClaimValue::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcCredentialSubjectClaimBag::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcIssuerClaimValue::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcProofClaimValue::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcCredentialStatusClaimValue::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcCredentialSchemaClaimValue::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcCredentialSchemaClaimBag::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcRefreshServiceClaimValue::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcRefreshServiceClaimBag::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcTermsOfUseClaimValue::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcTermsOfUseClaimBag::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcEvidenceClaimValue::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcEvidenceClaimBag::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Helpers::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Helpers\Arr::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Helpers\Type::class)]
final class VcDataModelClaimFactoryTest extends TestCase
{
    protected Helpers $helpers;

    protected MockObject $claimFactoryMock;


    protected function setUp(): void
    {
        $this->helpers = new Helpers();
        $this->claimFactoryMock = $this->createMock(ClaimFactory::class);
    }


    public function sut(
        ?Helpers $helpers = null,
        ?VcDataModelClaimFactory $claimFactory = null,
    ): VcDataModelClaimFactory {
        $helpers ??= $this->helpers;
        $claimFactory ??= $this->claimFactoryMock;

        return new VcDataModelClaimFactory($helpers, $claimFactory);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VcDataModelClaimFactory::class, $this->sut());
    }


    public function testCanBuildVcClaimValue(): void
    {
        $this->assertInstanceOf(
            VcClaimValue::class,
            $this->sut()->buildVcClaimValue(
                $this->createMock(VcAtContextClaimValue::class),
                'id',
                $this->createMock(TypeClaimValue::class),
                $this->createMock(VcCredentialSubjectClaimBag::class),
                $this->createMock(VcIssuerClaimValue::class),
                $this->createMock(DateTimeImmutable::class),
                null,
                null,
                null,
                null,
                null,
                null,
                null,
            ),
        );
    }


    public function testCanBuildVcAtContextClaimValue(): void
    {
        $this->assertInstanceOf(
            VcAtContextClaimValue::class,
            $this->sut()->buildVcAtContextClaimValue(AtContextsEnum::W3Org2018CredentialsV1->value, []),
        );
    }


    public function testCanBuildTypeClaimValue(): void
    {
        $this->assertInstanceOf(TypeClaimValue::class, $this->sut()->buildTypeClaimValue('type'));
    }


    public function testBuildTypeClaimValueThrowsIfDataNotArray(): void
    {
        $this->expectException(\SimpleSAML\OpenID\Exceptions\VcDataModelException::class);
        $this->expectExceptionMessage('Type');

        $this->sut()->buildTypeClaimValue(1);
    }


    public function testBuildVcCredentialSubjectClaimValue(): void
    {
        $this->assertInstanceOf(
            VcCredentialSubjectClaimValue::class,
            $this->sut()->buildVcCredentialSubjectClaimValue([]),
        );
    }


    public function testBuildVcCredentialSubjectClaimBag(): void
    {
        $this->assertInstanceOf(
            VcCredentialSubjectClaimBag::class,
            $this->sut()->buildVcCredentialSubjectClaimBag(['id' => 'subject']),
        );
    }


    public function testBuildVcCredentialSubjectClaimBagThrowsIfSubProvidedForMultipleSubjects(): void
    {
        $this->expectException(\SimpleSAML\OpenID\Exceptions\VcDataModelException::class);
        $this->expectExceptionMessage('multiple subjects');

        $this->sut()->buildVcCredentialSubjectClaimBag(
            [['id' => 'subject'],['id' => 'subject2']],
            'sub',
        );
    }


    public function testBuildVcCredentialSubjectClaimBagSetsSubForSingleSubject(): void
    {
        $this->assertInstanceOf(
            VcCredentialSubjectClaimBag::class,
            $this->sut()->buildVcCredentialSubjectClaimBag(['id' => 'subject'], 'sub'),
        );
    }


    public function testBuildVcIssuerClaimValue(): void
    {
        $this->assertInstanceOf(
            VcIssuerClaimValue::class,
            $this->sut()->buildVcIssuerClaimValue(['id' => 'urn:example:issuer']),
        );
    }


    public function testBuildVcProofClaimValue(): void
    {
        $this->assertInstanceOf(
            VcProofClaimValue::class,
            $this->sut()->buildVcProofClaimValue(['type' => 'type']),
        );
    }


    public function testBuildVcCredentialStatusClaimValue(): void
    {
        $this->assertInstanceOf(
            VcCredentialStatusClaimValue::class,
            $this->sut()->buildVcCredentialStatusClaimValue(['id' => 'urn:example:status', 'type' => 'type']),
        );
    }


    public function testBuildVcCredentialSchemaClaimValue(): void
    {
        $this->assertInstanceOf(
            VcCredentialSchemaClaimValue::class,
            $this->sut()->buildVcCredentialSchemaClaimValue(['id' => 'urn:example:schema', 'type' => 'type']),
        );
    }


    public function testBuildVcCredentialSchemaClaimBag(): void
    {
        $this->assertInstanceOf(
            VcCredentialSchemaClaimBag::class,
            $this->sut()->buildVcCredentialSchemaClaimBag(['id' => 'urn:example:schema', 'type' => 'type']),
        );
    }


    public function testBuildVcRefreshServiceClaimValue(): void
    {
        $this->assertInstanceOf(
            VcRefreshServiceClaimValue::class,
            $this->sut()->buildVcRefreshServiceClaimValue(['id' => 'urn:example:refresh', 'type' => 'type']),
        );
    }


    public function testBuildVcRefreshServiceClaimBag(): void
    {
        $this->assertInstanceOf(
            VcRefreshServiceClaimBag::class,
            $this->sut()->buildVcRefreshServiceClaimBag(['id' => 'urn:example:refresh', 'type' => 'type']),
        );
    }


    public function testBuildVcTermsOfUseClaimValue(): void
    {
        $this->assertInstanceOf(
            VcTermsOfUseClaimValue::class,
            $this->sut()->buildVcTermsOfUseClaimValue(['type' => 'type']),
        );
    }


    public function testBuildVcTermsOfUseClaimBag(): void
    {
        $this->assertInstanceOf(
            VcTermsOfUseClaimBag::class,
            $this->sut()->buildVcTermsOfUseClaimBag(['type' => 'type']),
        );
    }


    public function testBuildVcEvidenceClaimValue(): void
    {
        $this->assertInstanceOf(
            VcEvidenceClaimValue::class,
            $this->sut()->buildVcEvidenceClaimValue(['type' => 'type']),
        );
    }


    public function testBuildVcEvidenceClaimBag(): void
    {
        $this->assertInstanceOf(
            VcEvidenceClaimBag::class,
            $this->sut()->buildVcEvidenceClaimBag(['type' => 'type']),
        );
    }
}
