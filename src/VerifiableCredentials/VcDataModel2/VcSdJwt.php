<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2;

use DateTimeImmutable;
use Exception;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\CredentialFormatIdentifiersEnum;
use SimpleSAML\OpenID\Exceptions\VcDataModelException;
use SimpleSAML\OpenID\SdJwt\SdJwt;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\LocalizableStringValueBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\TypeClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcAtContextClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSchemaClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialStatusClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSubjectClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcEvidenceClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcIssuerClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcProofClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcRefreshServiceClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcTermsOfUseClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VerifiableCredentialInterface;

class VcSdJwt extends SdJwt implements VerifiableCredentialInterface
{
    protected ?VcAtContextClaimValue $vcAtContextClaimValue = null;

    protected null|false|string $vcId = null;

    protected ?TypeClaimValue $vcTypeClaimValue = null;

    protected ?VcCredentialSubjectClaimBag $vcCredentialSubjectClaimBag = null;

    protected ?VcIssuerClaimValue $vcIssuerClaimValue = null;

    protected null|false|DateTimeImmutable $validFrom = null;

    protected null|false|DateTimeImmutable $validUntil = null;

    protected null|false|VcProofClaimValue $vcProofClaimValue = null;

    protected null|false|VcCredentialStatusClaimValue $vcCredentialStatusClaimValue = null;

    protected null|false|VcCredentialSchemaClaimBag $vcCredentialSchemaClaimBag = null;

    protected null|false|VcRefreshServiceClaimBag $vcRefreshServiceClaimBag = null;

    protected null|false|VcTermsOfUseClaimBag $vcTermsOfUseClaimBag = null;

    protected null|false|VcEvidenceClaimBag $vcEvidenceClaimBag = null;

    protected null|false|LocalizableStringValueBag $vcName = null;

    protected null|false|LocalizableStringValueBag $vcDescription = null;


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    protected function validate(): void
    {
        parent::validate();

        $payload = $this->getPayload();

        if (array_key_exists(ClaimsEnum::Vc->value, $payload)) {
            throw new VcDataModelException('SD-JWT VC MUST NOT contain a "vc" claim.');
        }

        if (array_key_exists('vp', $payload)) {
            throw new VcDataModelException('SD-JWT VC MUST NOT contain a "vp" claim.');
        }
    }


    public function getCredentialFormatIdentifier(): CredentialFormatIdentifiersEnum
    {
        return CredentialFormatIdentifiersEnum::VcSdJwt;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function getVcAtContext(): VcAtContextClaimValue
    {
        if ($this->vcAtContextClaimValue instanceof VcAtContextClaimValue) {
            return $this->vcAtContextClaimValue;
        }

        $vcContext = $this->getPayloadClaim(ClaimsEnum::AtContext->value);

        if (!is_array($vcContext)) {
            throw new VcDataModelException('Invalid @context claim.');
        }

        if (!is_string($baseContext = array_shift($vcContext))) {
            throw new VcDataModelException('Invalid @context claim.');
        }

        return $this->vcAtContextClaimValue = $this->claimFactory->forVcDataModel2()->buildVcAtContextClaimValue(
            $baseContext,
            $vcContext,
        );
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getVcId(): ?string
    {
        if ($this->vcId === false) {
            return null;
        }

        $vcId = $this->getPayloadClaim(ClaimsEnum::Id->value);

        if (is_null($vcId)) {
            $this->vcId = false;
            return null;
        }

        return $this->vcId = $this->helpers->type()->enforceUri($vcId);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getVcType(): TypeClaimValue
    {
        if ($this->vcTypeClaimValue instanceof TypeClaimValue) {
            return $this->vcTypeClaimValue;
        }

        $vcType = $this->getPayloadClaim(ClaimsEnum::Type->value) ?? $this->getPayloadClaim(ClaimsEnum::AtType->value);

        if (is_null($vcType)) {
            throw new VcDataModelException('Invalid Type claim.');
        }

        return $this->vcTypeClaimValue = $this->claimFactory->forVcDataModel2()->buildTypeClaimValue($vcType);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getVcCredentialSubject(): VcCredentialSubjectClaimBag
    {
        if ($this->vcCredentialSubjectClaimBag instanceof VcCredentialSubjectClaimBag) {
            return $this->vcCredentialSubjectClaimBag;
        }

        $vcCredentialSubject = $this->getPayloadClaim(ClaimsEnum::Credential_Subject->value);

        if ((!is_array($vcCredentialSubject)) || $vcCredentialSubject === []) {
            throw new VcDataModelException('Invalid Credential Subject claim.');
        }

        return $this->vcCredentialSubjectClaimBag = $this->claimFactory->forVcDataModel2()
            ->buildVcCredentialSubjectClaimBag(
                $vcCredentialSubject,
                $this->getSubject(),
            );
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getVcIssuer(): VcIssuerClaimValue
    {
        if ($this->vcIssuerClaimValue instanceof VcIssuerClaimValue) {
            return $this->vcIssuerClaimValue;
        }

        $iss = $this->getIssuer();
        $vcIssuer = $this->getPayloadClaim(ClaimsEnum::Issuer->value);

        if (is_null($vcIssuer) && is_string($iss)) {
            $vcIssuer = $iss;
        }

        if (is_null($vcIssuer)) {
            throw new VcDataModelException('Invalid Issuer claim.');
        }

        if (is_string($vcIssuer)) {
            return $this->vcIssuerClaimValue = $this->claimFactory->forVcDataModel2()->buildVcIssuerClaimValue(
                [ClaimsEnum::Id->value => $vcIssuer],
            );
        }

        if (is_array($vcIssuer)) {
            return $this->vcIssuerClaimValue = $this->claimFactory->forVcDataModel2()->buildVcIssuerClaimValue(
                $vcIssuer,
            );
        }

        throw new VcDataModelException('Invalid Issuer claim.');
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getValidFrom(): DateTimeImmutable
    {
        if ($this->validFrom instanceof DateTimeImmutable) {
            return $this->validFrom;
        }

        $validFrom = $this->getPayloadClaim(ClaimsEnum::ValidFrom->value);

        if (is_null($validFrom)) {
            $validFrom = $this->getPayloadClaim(ClaimsEnum::Issuance_Date->value);
        }

        if (is_null($validFrom)) {
            $nbf = $this->getNotBefore() ?? $this->getIssuedAt();
            if (is_int($nbf)) {
                try {
                    return $this->validFrom = $this->helpers->dateTime()->fromTimestamp($nbf);
                } catch (Exception $e) {
                    throw new VcDataModelException('Invalid Not Before or Issued At claim.', $e->getCode(), $e);
                }
            }

            throw new VcDataModelException('Valid From claim is missing.');
        }

        try {
            $validFromStr = $this->helpers->type()->ensureNonEmptyString($validFrom, ClaimsEnum::ValidFrom->value);
            return $this->validFrom = $this->helpers->dateTime()->fromXsDateTime($validFromStr);
        } catch (Exception $exception) {
            throw new VcDataModelException('Invalid Valid From claim.', (int) $exception->getCode(), $exception);
        }
    }


    /**
     * Alias for getValidFrom to remain fully backwards compatible with consumers expecting getVcIssuanceDate
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getVcIssuanceDate(): DateTimeImmutable
    {
        return $this->getValidFrom();
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getValidUntil(): ?DateTimeImmutable
    {
        if ($this->validUntil === false) {
            return null;
        }

        if ($this->validUntil instanceof DateTimeImmutable) {
            return $this->validUntil;
        }

        $validUntil = $this->getPayloadClaim(ClaimsEnum::ValidUntil->value);

        if (is_null($validUntil)) {
            $validUntil = $this->getPayloadClaim(ClaimsEnum::Expiration_Date->value);
        }

        if (is_null($validUntil)) {
            $exp = $this->getExpirationTime();
            if (is_int($exp)) {
                try {
                    return $this->validUntil = $this->helpers->dateTime()->fromTimestamp($exp);
                } catch (Exception $e) {
                    throw new VcDataModelException('Invalid Expiration Time claim.', $e->getCode(), $e);
                }
            }

            $this->validUntil = false;
            return null;
        }

        try {
            $validUntilStr = $this->helpers->type()->ensureNonEmptyString($validUntil, ClaimsEnum::ValidUntil->value);
            return $this->validUntil = $this->helpers->dateTime()->fromXsDateTime($validUntilStr);
        } catch (Exception $exception) {
            throw new VcDataModelException('Invalid Valid Until claim.', (int) $exception->getCode(), $exception);
        }
    }


    /**
     * Alias for getValidUntil
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getVcExpirationDate(): ?DateTimeImmutable
    {
        return $this->getValidUntil();
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function getVcProof(): ?VcProofClaimValue
    {
        if ($this->vcProofClaimValue === false) {
            return null;
        }

        if ($this->vcProofClaimValue instanceof VcProofClaimValue) {
            return $this->vcProofClaimValue;
        }

        $vcProof = $this->getPayloadClaim(ClaimsEnum::Proof->value);

        if (is_null($vcProof)) {
            $this->vcProofClaimValue = false;
            return null;
        }

        if (!is_array($vcProof)) {
            throw new VcDataModelException('Invalid Proof claim.');
        }

        return $this->vcProofClaimValue = $this->claimFactory->forVcDataModel2()->buildVcProofClaimValue($vcProof);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function getVcCredentialStatus(): ?VcCredentialStatusClaimValue
    {
        if ($this->vcCredentialStatusClaimValue === false) {
            return null;
        }

        if ($this->vcCredentialStatusClaimValue instanceof VcCredentialStatusClaimValue) {
            return $this->vcCredentialStatusClaimValue;
        }

        $vcCredentialStatus = $this->getPayloadClaim(ClaimsEnum::Credential_Status->value);

        if (is_null($vcCredentialStatus)) {
            $this->vcCredentialStatusClaimValue = false;
            return null;
        }

        if (!is_array($vcCredentialStatus)) {
            throw new VcDataModelException('Invalid Credential Status claim.');
        }

        return $this->vcCredentialStatusClaimValue = $this->claimFactory->forVcDataModel2()
            ->buildVcCredentialStatusClaimValue($vcCredentialStatus);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function getVcCredentialSchema(): ?VcCredentialSchemaClaimBag
    {
        if ($this->vcCredentialSchemaClaimBag === false) {
            return null;
        }

        if ($this->vcCredentialSchemaClaimBag instanceof VcCredentialSchemaClaimBag) {
            return $this->vcCredentialSchemaClaimBag;
        }

        $vcCredentialSchema = $this->getPayloadClaim(ClaimsEnum::Credential_Schema->value);

        if (is_null($vcCredentialSchema)) {
            $this->vcCredentialSchemaClaimBag = false;
            return null;
        }

        if (!is_array($vcCredentialSchema)) {
            throw new VcDataModelException('Invalid Credential Schema claim.');
        }

        return $this->vcCredentialSchemaClaimBag = $this->claimFactory->forVcDataModel2()
            ->buildVcCredentialSchemaClaimBag($vcCredentialSchema);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function getVcRefreshService(): ?VcRefreshServiceClaimBag
    {
        if ($this->vcRefreshServiceClaimBag === false) {
            return null;
        }

        if ($this->vcRefreshServiceClaimBag instanceof VcRefreshServiceClaimBag) {
            return $this->vcRefreshServiceClaimBag;
        }

        $vcRefreshService = $this->getPayloadClaim(ClaimsEnum::Refresh_Service->value);

        if (is_null($vcRefreshService)) {
            $this->vcRefreshServiceClaimBag = false;
            return null;
        }

        if (!is_array($vcRefreshService)) {
            throw new VcDataModelException('Invalid Refresh Service claim.');
        }

        return $this->vcRefreshServiceClaimBag = $this->claimFactory->forVcDataModel2()
            ->buildVcRefreshServiceClaimBag($vcRefreshService);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function getVcTermsOfUse(): ?VcTermsOfUseClaimBag
    {
        if ($this->vcTermsOfUseClaimBag === false) {
            return null;
        }

        if ($this->vcTermsOfUseClaimBag instanceof VcTermsOfUseClaimBag) {
            return $this->vcTermsOfUseClaimBag;
        }

        $vcTermsOfUse = $this->getPayloadClaim(ClaimsEnum::Terms_Of_Use->value);

        if (is_null($vcTermsOfUse)) {
            $this->vcTermsOfUseClaimBag = false;
            return null;
        }

        if (!is_array($vcTermsOfUse)) {
            throw new VcDataModelException('Invalid Terms Of Use claim.');
        }

        return $this->vcTermsOfUseClaimBag = $this->claimFactory->forVcDataModel2()
            ->buildVcTermsOfUseClaimBag($vcTermsOfUse);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function getVcEvidence(): ?VcEvidenceClaimBag
    {
        if ($this->vcEvidenceClaimBag === false) {
            return null;
        }

        if ($this->vcEvidenceClaimBag instanceof VcEvidenceClaimBag) {
            return $this->vcEvidenceClaimBag;
        }

        $vcEvidence = $this->getPayloadClaim(ClaimsEnum::Evidence->value);

        if (is_null($vcEvidence)) {
            $this->vcEvidenceClaimBag = false;
            return null;
        }

        if (!is_array($vcEvidence)) {
            throw new VcDataModelException('Invalid Evidence claim.');
        }

        return $this->vcEvidenceClaimBag = $this->claimFactory->forVcDataModel2()
            ->buildVcEvidenceClaimBag($vcEvidence);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getVcName(): null|LocalizableStringValueBag
    {
        if ($this->vcName === false) {
            return null;
        }

        if ($this->vcName instanceof LocalizableStringValueBag) {
            return $this->vcName;
        }

        $vcName = $this->getPayloadClaim(ClaimsEnum::Name->value);

        if (is_null($vcName)) {
            $this->vcName = false;
            return null;
        }

        try {
            return $this->vcName = $this->claimFactory->forVcDataModel2()
                ->buildLocalizableStringValueBag($vcName, ClaimsEnum::Name->value);
        } catch (Exception $exception) {
            throw new VcDataModelException('Invalid Name claim.', (int) $exception->getCode(), $exception);
        }
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getVcDescription(): null|LocalizableStringValueBag
    {
        if ($this->vcDescription === false) {
            return null;
        }

        if ($this->vcDescription instanceof LocalizableStringValueBag) {
            return $this->vcDescription;
        }

        $vcDescription = $this->getPayloadClaim(ClaimsEnum::Description->value);

        if (is_null($vcDescription)) {
            $this->vcDescription = false;
            return null;
        }

        try {
            return $this->vcDescription = $this->claimFactory->forVcDataModel2()
                ->buildLocalizableStringValueBag($vcDescription, ClaimsEnum::Description->value);
        } catch (Exception $exception) {
            throw new VcDataModelException('Invalid Description claim.', (int) $exception->getCode(), $exception);
        }
    }
}
