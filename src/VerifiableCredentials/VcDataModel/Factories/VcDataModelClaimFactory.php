<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Factories;

use DateTimeImmutable;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\VcDataModelException;
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

class VcDataModelClaimFactory
{
    public function __construct(
        protected readonly Helpers $helpers,
        protected readonly ClaimFactory $claimFactory,
    ) {
    }


    /**
     * @param non-empty-string|null $vcId
     */
    public function buildVcClaimValue(
        VcAtContextClaimValue $vcAtContextClaimValue,
        null|string $vcId,
        TypeClaimValue $vcTypeClaimValue,
        VcCredentialSubjectClaimBag $vcCredentialSubjectClaimBag,
        VcIssuerClaimValue $vcIssuerClaimValue,
        DateTimeImmutable $vcIssuanceDate,
        ?VcProofClaimValue $vcProofClaimValue,
        ?DateTimeImmutable $vcExpirationDate,
        ?VcCredentialStatusClaimValue $vcCredentialStatusClaimValue,
        ?VcCredentialSchemaClaimBag $vcCredentialSchemaClaimBag,
        ?VcRefreshServiceClaimBag $vcRefreshServiceClaimBag,
        ?VcTermsOfUseClaimBag $vcTermsOfUseClaimBag,
        ?VcEvidenceClaimBag $vcEvidenceClaimBag,
    ): VcClaimValue {
        return new VcClaimValue(
            $vcAtContextClaimValue,
            $vcId,
            $vcTypeClaimValue,
            $vcCredentialSubjectClaimBag,
            $vcIssuerClaimValue,
            $vcIssuanceDate,
            $vcProofClaimValue,
            $vcExpirationDate,
            $vcCredentialStatusClaimValue,
            $vcCredentialSchemaClaimBag,
            $vcRefreshServiceClaimBag,
            $vcTermsOfUseClaimBag,
            $vcEvidenceClaimBag,
        );
    }


    /**
     * @param mixed[] $otherContexts
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function buildVcAtContextClaimValue(string $baseContext, array $otherContexts): VcAtContextClaimValue
    {
        return new VcAtContextClaimValue($baseContext, $otherContexts);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function buildTypeClaimValue(mixed $data): TypeClaimValue
    {
        if (is_string($data)) {
            $data = [$data];
        }

        if (!is_array($data)) {
            throw new VcDataModelException('Invalid Type claim value.');
        }

        return new TypeClaimValue(
            $this->helpers->type()->enforceNonEmptyArrayWithValuesAsNonEmptyStrings(
                $data,
            ),
        );
    }


    /**
     * @param non-empty-array<mixed> $data
     */
    public function buildVcCredentialSubjectClaimValue(array $data, ?string $id = null): VcCredentialSubjectClaimValue
    {
        return new VcCredentialSubjectClaimValue($data);
    }


    /**
     * @param mixed[] $data
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function buildVcCredentialSubjectClaimBag(
        array $data,
        ?string $subClaimValue = null,
    ): VcCredentialSubjectClaimBag {
        if ($this->helpers->arr()->isAssociative($data)) {
            $data = [$data];
        }

        $data = $this->helpers->type()->enforceNonEmptyArrayOfNonEmptyArrays($data);

        if (is_string($subClaimValue) && (count($data) !== 1)) {
            throw new VcDataModelException(
                'Refusing to set credentialSubject ID claim value for multiple subjects.',
            );
        }

        $vcCredentialSubjectClaimValueData =  array_shift($data);

        // If we have the 'sub' claim in JWT, we must use it as the credentialSubject ID value. However, we can't do
        // that if we have more than one credentialSubject.
        if (is_string($subClaimValue)) {
            if ($data === []) {
                $vcCredentialSubjectClaimValueData[ClaimsEnum::Id->value] = $subClaimValue;
            } else {
                throw new VcDataModelException(
                    'Refusing to set credentialSubject ID claim value for multiple subjects.',
                );
            }
        }

        $vcCredentialSubjectClaimValue = $this->buildVcCredentialSubjectClaimValue($vcCredentialSubjectClaimValueData);

        $vcCredentialSubjectClaimValues = array_map(
            fn (array $data): VcCredentialSubjectClaimValue => $this->buildVcCredentialSubjectClaimValue($data),
            $data,
        );

        return new VcCredentialSubjectClaimBag(
            $vcCredentialSubjectClaimValue,
            ...$vcCredentialSubjectClaimValues,
        );
    }


    /**
     * @param mixed[] $data
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function buildVcIssuerClaimValue(array $data): VcIssuerClaimValue
    {
        $id = $data[ClaimsEnum::Id->value] ?? throw new VcDataModelException(
            'No ID claim value available.',
        );

        $id = $this->helpers->type()->enforceUri($id);

        return new VcIssuerClaimValue($id, $data);
    }


    /**
     * @param mixed[] $data
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function buildVcProofClaimValue(array $data): VcProofClaimValue
    {
        $type = $data[ClaimsEnum::Type->value] ?? throw new VcDataModelException(
            'No Type claim value available.',
        );

        $typeClaimValue = $this->buildTypeClaimValue($type);

        return new VcProofClaimValue($typeClaimValue, $data);
    }


    /**
     * @param mixed[] $data
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function buildVcCredentialStatusClaimValue(array $data): VcCredentialStatusClaimValue
    {
        $id = $data[ClaimsEnum::Id->value] ?? throw new VcDataModelException(
            'No ID claim value available.',
        );
        $id = $this->helpers->type()->enforceUri($id);

        $type = $data[ClaimsEnum::Type->value] ?? throw new VcDataModelException(
            'No Type claim value available.',
        );
        $typeClaimValue = $this->buildTypeClaimValue($type);

        return new VcCredentialStatusClaimValue($id, $typeClaimValue, $data);
    }


    /**
     * @param non-empty-array<mixed> $data
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function buildVcCredentialSchemaClaimValue(array $data): VcCredentialSchemaClaimValue
    {
        $id = $data[ClaimsEnum::Id->value] ?? throw new VcDataModelException(
            'No ID claim value available.',
        );
        $id = $this->helpers->type()->enforceUri($id);

        $type = $data[ClaimsEnum::Type->value] ?? throw new VcDataModelException(
            'No Type claim value available.',
        );
        $typeClaimValue = $this->buildTypeClaimValue($type);

        return new VcCredentialSchemaClaimValue($id, $typeClaimValue, $data);
    }


    /**
     * @param mixed[] $data
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function buildVcCredentialSchemaClaimBag(array $data): VcCredentialSchemaClaimBag
    {
        if ($this->helpers->arr()->isAssociative($data)) {
            $data = [$data];
        }

        $data = $this->helpers->type()->enforceNonEmptyArrayOfNonEmptyArrays($data);

        $vcCredentialSchemaClaimValueData =  array_shift($data);

        $vcCredentialSchemaClaimValue = $this->buildVcCredentialSchemaClaimValue($vcCredentialSchemaClaimValueData);

        $vcCredentialSchemaClaimValues = array_map(
            $this->buildVcCredentialSchemaClaimValue(...),
            $data,
        );

        return new VcCredentialSchemaClaimBag(
            $vcCredentialSchemaClaimValue,
            ...$vcCredentialSchemaClaimValues,
        );
    }


    /**
     * @param non-empty-array<mixed> $data
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function buildVcRefreshServiceClaimValue(array $data): VcRefreshServiceClaimValue
    {
        $id = $data[ClaimsEnum::Id->value] ?? throw new VcDataModelException(
            'No ID claim value available.',
        );
        $id = $this->helpers->type()->enforceUri($id);

        $type = $data[ClaimsEnum::Type->value] ?? throw new VcDataModelException(
            'No Type claim value available.',
        );
        $typeClaimValue = $this->buildTypeClaimValue($type);

        return new VcRefreshServiceClaimValue($id, $typeClaimValue, $data);
    }


    /**
     * @param mixed[] $data
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function buildVcRefreshServiceClaimBag(array $data): VcRefreshServiceClaimBag
    {
        if ($this->helpers->arr()->isAssociative($data)) {
            $data = [$data];
        }

        $data = $this->helpers->type()->enforceNonEmptyArrayOfNonEmptyArrays($data);

        $vcRefreshServiceClaimValueData =  array_shift($data);

        $vcRefreshServiceClaimValue = $this->buildVcRefreshServiceClaimValue($vcRefreshServiceClaimValueData);

        $vcRefreshServiceClaimValues = array_map(
            $this->buildVcRefreshServiceClaimValue(...),
            $data,
        );

        return new VcRefreshServiceClaimBag(
            $vcRefreshServiceClaimValue,
            ...$vcRefreshServiceClaimValues,
        );
    }


    /**
     * @param mixed[] $data
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function buildVcTermsOfUseClaimValue(array $data): VcTermsOfUseClaimValue
    {
        $type = $data[ClaimsEnum::Type->value] ?? throw new VcDataModelException(
            'No Type claim value available.',
        );

        $typeClaimValue = $this->buildTypeClaimValue($type);

        return new VcTermsOfUseClaimValue($typeClaimValue, $data);
    }


    /**
     * @param mixed[] $data
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function buildVcTermsOfUseClaimBag(array $data): VcTermsOfUseClaimBag
    {
        if ($this->helpers->arr()->isAssociative($data)) {
            $data = [$data];
        }

        $data = $this->helpers->type()->enforceNonEmptyArrayOfNonEmptyArrays($data);

        $vcTermsOfUseClaimValueData =  array_shift($data);

        $vcTermsOfUseClaimValue = $this->buildVcTermsOfUseClaimValue($vcTermsOfUseClaimValueData);

        $vcTermsOfUseClaimValues = array_map(
            $this->buildVcTermsOfUseClaimValue(...),
            $data,
        );

        return new VcTermsOfUseClaimBag(
            $vcTermsOfUseClaimValue,
            ...$vcTermsOfUseClaimValues,
        );
    }


    /**
     * @param mixed[] $data
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function buildVcEvidenceClaimValue(array $data): VcEvidenceClaimValue
    {
        $type = $data[ClaimsEnum::Type->value] ?? throw new VcDataModelException(
            'No Type claim value available.',
        );

        $typeClaimValue = $this->buildTypeClaimValue($type);

        return new VcEvidenceClaimValue($typeClaimValue, $data);
    }


    /**
     * @param mixed[] $data
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function buildVcEvidenceClaimBag(array $data): VcEvidenceClaimBag
    {
        if ($this->helpers->arr()->isAssociative($data)) {
            $data = [$data];
        }

        $data = $this->helpers->type()->enforceNonEmptyArrayOfNonEmptyArrays($data);

        $vcEvidenceClaimValueData =  array_shift($data);

        $vcEvidenceClaimValue = $this->buildVcEvidenceClaimValue($vcEvidenceClaimValueData);

        $vcEvidenceClaimValues = array_map(
            $this->buildVcEvidenceClaimValue(...),
            $data,
        );

        return new VcEvidenceClaimBag(
            $vcEvidenceClaimValue,
            ...$vcEvidenceClaimValues,
        );
    }
}
