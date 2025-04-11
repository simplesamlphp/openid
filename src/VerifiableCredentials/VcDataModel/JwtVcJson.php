<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\CredentialFormatIdentifiersEnum;
use SimpleSAML\OpenID\Exceptions\VcDataModelException;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcAtContextClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSubjectClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcIssuerClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VerifiableCredentialInterface;

class JwtVcJson extends ParsedJws implements VerifiableCredentialInterface
{
    protected ?VcClaimValue $vcClaimValue = null;

    protected ?VcAtContextClaimValue $vcAtContextClaimValue = null;

    /** @var null|false|non-empty-string */
    protected null|false|string $vcId = null;

    /** @var ?non-empty-array<non-empty-string>  */
    protected ?array $vcType = null;

    protected ?VcCredentialSubjectClaimBag $vcCredentialSubjectClaimBag = null;

    protected ?VcIssuerClaimValue $vcIssuerClaimValue = null;

    public function getCredentialFormatIdentifier(): CredentialFormatIdentifiersEnum
    {
        return CredentialFormatIdentifiersEnum::JwtVcJson;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getVc(): VcClaimValue
    {
        if ($this->vcClaimValue instanceof \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcClaimValue) {
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
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getVcId(): ?string
    {
        if ($this->vcId === false) {
            return null;
        }

        $vcId = $this->getNestedPayloadClaim(ClaimsEnum::Vc->value, ClaimsEnum::Id->value);

        if (is_null($vcId)) {
            $this->vcId = false;
            return null;
        }

        return $this->vcId = $this->helpers->type()->enforceUri($vcId);
    }

    /**
     * @return non-empty-array<non-empty-string>
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getVcType(): array
    {
        if ($this->vcType !== null) {
            return $this->vcType;
        }

        $claimKeys = [ClaimsEnum::Vc->value, ClaimsEnum::Type->value];
        $claimKeys2 = [ClaimsEnum::Vc->value, ClaimsEnum::AtType->value];

        $vcType = $this->getNestedPayloadClaim(...$claimKeys) ?? $this->getNestedPayloadClaim(...$claimKeys2);

        if ((!is_array($vcType)) || $vcType === []) {
            throw new VcDataModelException('Invalid VC Type claim.');
        }

        return $this->vcType = $this->helpers->type()->enforceNonEmptyArrayWithValuesAsNonEmptyStrings($vcType);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getVcCredentialSubject(): VcCredentialSubjectClaimBag
    {
        if ($this->vcCredentialSubjectClaimBag instanceof VcCredentialSubjectClaimBag) {
            return $this->vcCredentialSubjectClaimBag;
        }

        $claimKeys = [ClaimsEnum::Vc->value, ClaimsEnum::CredentialSubject->value];

        $vcCredentialSubject = $this->getNestedPayloadClaim(...$claimKeys);

        if ((!is_array($vcCredentialSubject)) || $vcCredentialSubject === []) {
            throw new VcDataModelException('Invalid VC Credential Subject claim.');
        }

        if ($this->helpers->arr()->isAssociative($vcCredentialSubject)) {
            return $this->vcCredentialSubjectClaimBag = $this->claimFactory->forVcDataModel()
                ->buildVcCredentialSubjectClaimBag([$vcCredentialSubject]);
        }

        return $this->vcCredentialSubjectClaimBag = $this->claimFactory->forVcDataModel()
            ->buildVcCredentialSubjectClaimBag(
                $this->helpers->type()->enforceNonEmptyArrayOfNonEmptyArrays($vcCredentialSubject),
            );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function getVcIssuer(): VcIssuerClaimValue
    {
        if ($this->vcIssuerClaimValue instanceof VcIssuerClaimValue) {
            return $this->vcIssuerClaimValue;
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
                $this->helpers->type()->enforceNonEmptyArray($vcIssuer),
            );
        }

        throw new VcDataModelException('Invalid VC Issuer claim.');
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
        );
    }
}
