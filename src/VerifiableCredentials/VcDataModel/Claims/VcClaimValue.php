<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use DateTimeImmutable;
use SimpleSAML\OpenID\Claims\ClaimInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

class VcClaimValue implements ClaimInterface
{
    /**
     * @param null|non-empty-string $id
     */
    public function __construct(
        protected readonly VcAtContextClaimValue $atContextClaimValue,
        /** @var null|non-empty-string */
        protected readonly null|string $id,
        protected readonly TypeClaimValue $typeClaimValue,
        protected readonly VcCredentialSubjectClaimBag $credentialSubjectClaimBag,
        protected readonly VcIssuerClaimValue $issuerClaimValue,
        protected readonly DateTimeImmutable $issuanceDate,
        protected readonly ?VcProofClaimValue $proofClaimValue,
        protected readonly ?DateTimeImmutable $expirationDate,
        protected readonly ?VcCredentialStatusClaimValue $credentialStatusClaimValue,
        protected readonly ?VcCredentialSchemaClaimBag $credentialSchemaClaimBag,
        protected readonly ?VcRefreshServiceClaimBag $refreshServiceClaimBag,
        protected readonly ?VcTermsOfUseClaimBag $termsOfUseClaimBag,
        protected readonly ?VcEvidenceClaimBag $evidenceClaimBag,
    ) {
    }


    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return $this->getValue();
    }


    public function getAtContext(): VcAtContextClaimValue
    {
        return $this->atContextClaimValue;
    }


    /**
     * @return non-empty-string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }


    public function getType(): TypeClaimValue
    {
        return $this->typeClaimValue;
    }


    public function getCredentialSubject(): VcCredentialSubjectClaimBag
    {
        return $this->credentialSubjectClaimBag;
    }


    public function getIssuer(): VcIssuerClaimValue
    {
        return $this->issuerClaimValue;
    }


    public function getIssuanceDate(): DateTimeImmutable
    {
        return $this->issuanceDate;
    }


    public function getProof(): ?VcProofClaimValue
    {
        return $this->proofClaimValue;
    }


    public function getExpirationDate(): ?DateTimeImmutable
    {
        return $this->expirationDate;
    }


    public function getCredentialStatus(): ?VcCredentialStatusClaimValue
    {
        return $this->credentialStatusClaimValue;
    }


    public function getCredentialSchema(): ?VcCredentialSchemaClaimBag
    {
        return $this->credentialSchemaClaimBag;
    }


    public function getName(): string
    {
        return ClaimsEnum::Vc->value;
    }


    /**
     * @return mixed[]
     */
    public function getValue(): array
    {
        // TODO: Implement getValue() method.
        return [];
    }
}
