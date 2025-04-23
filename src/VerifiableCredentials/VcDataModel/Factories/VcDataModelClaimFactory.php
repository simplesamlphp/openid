<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Factories;

use DateTimeImmutable;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\VcDataModelException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcAtContextClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialStatusClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSubjectClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSubjectClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcIssuerClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcProofClaimValue;

class VcDataModelClaimFactory
{
    public function __construct(
        protected readonly Helpers $helpers,
        protected readonly ClaimFactory $claimFactory,
    ) {
    }

    /**
     * @param non-empty-string|null $vcId
     * @param non-empty-array<non-empty-string> $vcType
     */
    public function buildVcClaimValue(
        VcAtContextClaimValue $vcAtContextClaimValue,
        null|string $vcId,
        array $vcType,
        VcCredentialSubjectClaimBag $vcCredentialSubjectClaimBag,
        VcIssuerClaimValue $vcIssuerClaimValue,
        DateTimeImmutable $vcIssuanceDate,
        ?VcProofClaimValue $vcProofClaimValue,
        ?DateTimeImmutable $vcExpirationDate,
        ?VcCredentialStatusClaimValue $vcCredentialStatusClaimValue,
    ): VcClaimValue {
        return new VcClaimValue(
            $vcAtContextClaimValue,
            $vcId,
            $vcType,
            $vcCredentialSubjectClaimBag,
            $vcIssuerClaimValue,
            $vcIssuanceDate,
            $vcProofClaimValue,
            $vcExpirationDate,
            $vcCredentialStatusClaimValue,
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
     * @param non-empty-array<mixed> $data
     */
    public function buildVcCredentialSubjectClaimValue(array $data): VcCredentialSubjectClaimValue
    {
        return new VcCredentialSubjectClaimValue($data);
    }

    /**
     * @param non-empty-array<non-empty-array<mixed>> $data
     */
    public function buildVcCredentialSubjectClaimBag(array $data): VcCredentialSubjectClaimBag
    {
        $vcCredentialSubjectClaimValueData =  array_shift($data);

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
     * @param non-empty-array<mixed> $data
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function buildVcIssuerClaimValue(array $data): VcIssuerClaimValue
    {
        $id = $data[ClaimsEnum::Id->value] ?? throw new VcDataModelException(
            'No Issuer ID claim value available.',
        );

        $id = $this->helpers->type()->enforceUri($id);

        return new VcIssuerClaimValue($id, $data);
    }

    /**
     * @param non-empty-array<mixed> $data
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function buildVcProofClaimValue(array $data): VcProofClaimValue
    {
        $type = $data[ClaimsEnum::Type->value] ?? throw new VcDataModelException(
            'No Type claim value available.',
        );

        $type = $this->helpers->type()->ensureNonEmptyString($type);

        return new VcProofClaimValue($type, $data);
    }

    /**
     * @param non-empty-array<mixed> $data
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function buildVcCredentialStatusClaimValue(array $data): VcCredentialStatusClaimValue
    {
        $id = $data[ClaimsEnum::Id->value] ?? throw new VcDataModelException(
            'No Issuer ID claim value available.',
        );
        $id = $this->helpers->type()->enforceUri($id);

        $type = $data[ClaimsEnum::Type->value] ?? throw new VcDataModelException(
            'No Type claim value available.',
        );
        $type = $this->helpers->type()->ensureNonEmptyString($type);

        return new VcCredentialStatusClaimValue($id, $type, $data);
    }
}
