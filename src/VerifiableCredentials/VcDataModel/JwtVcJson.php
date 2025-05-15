<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel;

use DateTimeImmutable;
use Exception;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\CredentialFormatIdentifiersEnum;
use SimpleSAML\OpenID\Exceptions\VcDataModelException;
use SimpleSAML\OpenID\Jws\ParsedJws;
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
use SimpleSAML\OpenID\VerifiableCredentials\VerifiableCredentialInterface;

class JwtVcJson extends ParsedJws implements VerifiableCredentialInterface
{
    protected ?VcClaimValue $vcClaimValue = null;

    protected ?VcAtContextClaimValue $vcAtContextClaimValue = null;

    /** @var null|false|non-empty-string */
    protected null|false|string $vcId = null;

    protected ?TypeClaimValue $vcTypeClaimValue = null;

    protected ?VcCredentialSubjectClaimBag $vcCredentialSubjectClaimBag = null;

    protected ?VcIssuerClaimValue $vcIssuerClaimValue = null;

    protected ?DateTimeImmutable $vcIssuanceDate = null;

    protected null|false|VcProofClaimValue $vcProofClaimValue = null;

    protected null|false|DateTimeImmutable $vcExpirationDate = null;

    protected null|false|VcCredentialStatusClaimValue $vcCredentialStatusClaimValue = null;

    protected null|false|VcCredentialSchemaClaimBag $vcCredentialSchemaClaimBag = null;

    protected null|false|VcRefreshServiceClaimBag $vcRefreshServiceClaimBag = null;

    protected null|false|VcTermsOfUseClaimBag $vcTermsOfUseClaimBag = null;

    protected null|false|VcEvidenceClaimBag $vcEvidenceClaimBag = null;

    public function getCredentialFormatIdentifier(): CredentialFormatIdentifiersEnum
    {
        return CredentialFormatIdentifiersEnum::JwtVcJson;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function getVc(): VcClaimValue
    {
        if ($this->vcClaimValue instanceof VcClaimValue) {
            return  $this->vcClaimValue;
        }

        $claimKey = ClaimsEnum::Vc->value;

        $vc = $this->getPayloadClaim($claimKey) ?? throw new VcDataModelException('No VC claim found.');

        if (!is_array($vc)) {
            throw new VcDataModelException('Invalid VC claim.');
        }

        return $this->vcClaimValue = $this->claimFactory->forVcDataModel()->buildVcClaimValue(
            $this->getVcAtContext(),
            $this->getVcId(),
            $this->getVcType(),
            $this->getVcCredentialSubject(),
            $this->getVcIssuer(),
            $this->getVcIssuanceDate(),
            $this->getVcProof(),
            $this->getVcExpirationDate(),
            $this->getVcCredentialStatus(),
            $this->getVcCredentialSchema(),
            $this->getVcRefreshService(),
            $this->getVcTermsOfUse(),
            $this->getVcEvidence(),
        );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function getVcAtContext(): VcAtContextClaimValue
    {
        if ($this->vcAtContextClaimValue instanceof VcAtContextClaimValue) {
            return $this->vcAtContextClaimValue;
        }

        $vcContext = $this->getNestedPayloadClaim(ClaimsEnum::Vc->value, ClaimsEnum::AtContext->value);

        if (!is_array($vcContext)) {
            throw new VcDataModelException('Invalid VC @context claim.');
        }

        if (!is_string($baseContext = array_shift($vcContext))) {
            throw new VcDataModelException('Invalid VC @context claim.');
        }

        return $this->vcAtContextClaimValue = $this->claimFactory->forVcDataModel()->buildVcAtContextClaimValue(
            $baseContext,
            $vcContext,
        );
    }

    /**
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\ClientAssertionException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getVcId(): ?string
    {
        if ($this->vcId === false) {
            return null;
        }

        $jti = $this->getJwtId();

        if (is_string($jti)) {
            return $this->vcId = $this->helpers->type()->enforceUri($jti);
        }

        $vcId = $this->getNestedPayloadClaim(ClaimsEnum::Vc->value, ClaimsEnum::Id->value);

        if (is_null($vcId)) {
            $this->vcId = false;
            return null;
        }

//        return $this->vcId = $this->helpers->type()->enforceUri($vcId);
        return $this->vcId = $this->helpers->type()->ensureNonEmptyString($vcId);
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

        $claimKeys = [ClaimsEnum::Vc->value, ClaimsEnum::Type->value];
        $claimKeys2 = [ClaimsEnum::Vc->value, ClaimsEnum::AtType->value];

        $vcType = $this->getNestedPayloadClaim(...$claimKeys) ?? $this->getNestedPayloadClaim(...$claimKeys2);

        if (is_null($vcType)) {
            throw new VcDataModelException('Invalid VC Type claim.');
        }

        return $this->vcTypeClaimValue = $this->claimFactory->forVcDataModel()->buildTypeClaimValue($vcType);
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

        $claimKeys = [ClaimsEnum::Vc->value, ClaimsEnum::Credential_Subject->value];

        $vcCredentialSubject = $this->getNestedPayloadClaim(...$claimKeys);

        if ((!is_array($vcCredentialSubject)) || $vcCredentialSubject === []) {
            throw new VcDataModelException('Invalid VC Credential Subject claim.');
        }

        return $this->vcCredentialSubjectClaimBag = $this->claimFactory->forVcDataModel()
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

        if (is_string($iss)) {
            return $this->vcIssuerClaimValue = $this->claimFactory->forVcDataModel()->buildVcIssuerClaimValue(
                [ClaimsEnum::Id->value => $iss],
            );
        }

        $vcIssuer = $this->getNestedPayloadClaim(ClaimsEnum::Vc->value, ClaimsEnum::Issuer->value);

        if (is_null($vcIssuer)) {
            throw new VcDataModelException('Invalid VC Issuer claim.');
        }

        if (is_string($vcIssuer)) {
            return $this->vcIssuerClaimValue = $this->claimFactory->forVcDataModel()->buildVcIssuerClaimValue(
                [ClaimsEnum::Id->value => $vcIssuer],
            );
        }

        if (is_array($vcIssuer)) {
            return $this->vcIssuerClaimValue = $this->claimFactory->forVcDataModel()->buildVcIssuerClaimValue(
                $vcIssuer,
            );
        }

        throw new VcDataModelException('Invalid VC Issuer claim.');
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function getVcIssuanceDate(): DateTimeImmutable
    {
        if ($this->vcIssuanceDate instanceof DateTimeImmutable) {
            return $this->vcIssuanceDate;
        }

        $nbf = $this->getNotBefore();

        if (is_int($nbf)) {
            try {
                return $this->vcIssuanceDate = $this->helpers->dateTime()->fromTimestamp($nbf);
            } catch (Exception $e) {
                throw new VcDataModelException('Error parsing Not Before claim: ' . $e->getMessage());
            }
        }

        $issuanceDate = $this->getNestedPayloadClaim(ClaimsEnum::Vc->value, ClaimsEnum::Issuance_Date->value);

        if (!is_string($issuanceDate)) {
            throw new VcDataModelException('Invalid VC Issuance Date claim.');
        }

        try {
            return $this->vcIssuanceDate = $this->helpers->dateTime()->fromXsDateTime($issuanceDate);
        } catch (Exception $exception) {
            throw new VcDataModelException('Error parsing VC Issuance Date claim: ' . $exception->getMessage());
        }
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
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

        $vcProof = $this->getNestedPayloadClaim(ClaimsEnum::Vc->value, ClaimsEnum::Proof->value);

        if (is_null($vcProof)) {
            $this->vcProofClaimValue = false;
            return null;
        }

        if (is_array($vcProof)) {
            return $this->vcProofClaimValue = $this->claimFactory->forVcDataModel()->buildVcProofClaimValue(
                $vcProof,
            );
        }

        throw new VcDataModelException('Invalid VC Proof claim.');
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function getVcExpirationDate(): ?DateTimeImmutable
    {
        if ($this->vcExpirationDate === false) {
            return null;
        }

        if ($this->vcExpirationDate instanceof DateTimeImmutable) {
            return $this->vcExpirationDate;
        }

        // Try to get it from the exp claim.
        $exp = $this->getExpirationTime();

        if (is_int($exp)) {
            try {
                return $this->vcExpirationDate = $this->helpers->dateTime()->fromTimestamp($exp);
            } catch (Exception $e) {
                throw new VcDataModelException('Error parsing Expiration Time date claim: ' . $e->getMessage());
            }
        }

        // Try to get it from the vc claim.
        $expirationDate = $this->getNestedPayloadClaim(ClaimsEnum::Vc->value, ClaimsEnum::Expiration_Date->value);

        if (is_null($expirationDate)) {
            $this->vcExpirationDate = false;
            return null;
        }

        if (is_string($expirationDate)) {
            try {
                return $this->vcExpirationDate = $this->helpers->dateTime()->fromXsDateTime($expirationDate);
            } catch (Exception $exception) {
                throw new VcDataModelException('Error parsing VC Expiration Date claim: ' . $exception->getMessage());
            }
        }

        throw new VcDataModelException('Invalid VC Expiration Date claim.');
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getVcCredentialStatus(): ?VcCredentialStatusClaimValue
    {
        if ($this->vcCredentialStatusClaimValue === false) {
            return null;
        }

        if ($this->vcCredentialStatusClaimValue instanceof VcCredentialStatusClaimValue) {
            return $this->vcCredentialStatusClaimValue;
        }

        $credentialStatus = $this->getNestedPayloadClaim(ClaimsEnum::Vc->value, ClaimsEnum::Credential_Status->value);

        if (is_null($credentialStatus)) {
            $this->vcCredentialStatusClaimValue = false;
            return null;
        }

        if (!is_array($credentialStatus)) {
            throw new VcDataModelException('Invalid VC Credential Status Claim.');
        }

        return $this->vcCredentialStatusClaimValue = $this->claimFactory->forVcDataModel()
            ->buildVcCredentialStatusClaimValue(
                $credentialStatus,
            );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getVcCredentialSchema(): ?VcCredentialSchemaClaimBag
    {
        if ($this->vcCredentialSchemaClaimBag === false) {
            return null;
        }

        if ($this->vcCredentialSchemaClaimBag instanceof VcCredentialSchemaClaimBag) {
            return $this->vcCredentialSchemaClaimBag;
        }

        $credentialSchema = $this->getNestedPayloadClaim(ClaimsEnum::Vc->value, ClaimsEnum::Credential_Schema->value);

        if (is_null($credentialSchema)) {
            $this->vcCredentialSchemaClaimBag = false;
            return null;
        }

        if (!is_array($credentialSchema)) {
            throw new VcDataModelException('Invalid VC Credential Schema claim.');
        }

        return $this->vcCredentialSchemaClaimBag = $this->claimFactory->forVcDataModel()
            ->buildVcCredentialSchemaClaimBag(
                $credentialSchema,
            );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getVcRefreshService(): ?VcRefreshServiceClaimBag
    {
        if ($this->vcRefreshServiceClaimBag === false) {
            return null;
        }

        if ($this->vcRefreshServiceClaimBag instanceof VcRefreshServiceClaimBag) {
            return $this->vcRefreshServiceClaimBag;
        }

        $refreshService = $this->getNestedPayloadClaim(ClaimsEnum::Vc->value, ClaimsEnum::Refresh_Service->value);

        if (is_null($refreshService)) {
            $this->vcRefreshServiceClaimBag = false;
            return null;
        }

        if (!is_array($refreshService)) {
            throw new VcDataModelException('Invalid VC Refresh Service claim.');
        }

        return $this->vcRefreshServiceClaimBag = $this->claimFactory->forVcDataModel()
            ->buildVcRefreshServiceClaimBag(
                $refreshService,
            );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getVcTermsOfUse(): ?VcTermsOfUseClaimBag
    {
        if ($this->vcTermsOfUseClaimBag === false) {
            return null;
        }

        if ($this->vcTermsOfUseClaimBag instanceof VcTermsOfUseClaimBag) {
            return $this->vcTermsOfUseClaimBag;
        }

        $termsOfUse = $this->getNestedPayloadClaim(ClaimsEnum::Vc->value, ClaimsEnum::Terms_Of_Use->value);

        if (is_null($termsOfUse)) {
            $this->vcTermsOfUseClaimBag = false;
            return null;
        }

        if (!is_array($termsOfUse)) {
            throw new VcDataModelException('Invalid VC Terms Of Use claim.');
        }

        return $this->vcTermsOfUseClaimBag = $this->claimFactory->forVcDataModel()
            ->buildVcTermsOfUseClaimBag(
                $termsOfUse,
            );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getVcEvidence(): ?VcEvidenceClaimBag
    {
        if ($this->vcEvidenceClaimBag === false) {
            return null;
        }

        if ($this->vcEvidenceClaimBag instanceof VcEvidenceClaimBag) {
            return $this->vcEvidenceClaimBag;
        }

        $evidence = $this->getNestedPayloadClaim(ClaimsEnum::Vc->value, ClaimsEnum::Evidence->value);

        if (is_null($evidence)) {
            $this->vcEvidenceClaimBag = false;
            return null;
        }

        if (!is_array($evidence)) {
            throw new VcDataModelException('Invalid VC Evidence claim.');
        }

        return $this->vcEvidenceClaimBag = $this->claimFactory->forVcDataModel()
            ->buildVcEvidenceClaimBag(
                $evidence,
            );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function validate(): void
    {
        $this->validateByCallbacks(
            $this->getVc(...),
            $this->getVcAtContext(...),
            $this->getVcId(...),
            $this->getVcType(...),
            $this->getVcCredentialSubject(...),
            $this->getVcIssuer(...),
            $this->getVcIssuanceDate(...),
            $this->getVcProof(...),
            $this->getVcExpirationDate(...),
            $this->getVcCredentialStatus(...),
            $this->getVcCredentialSchema(...),
            $this->getVcRefreshService(...),
            $this->getVcTermsOfUse(...),
            $this->getVcEvidence(...),
            $this->getExpirationTime(...),
            $this->getIssuer(...),
            $this->getNotBefore(...),
            $this->getJwtId(...),
            $this->getSubject(...),
            $this->getAudience(...),
        );
    }
}
