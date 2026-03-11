<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Factories;

use SimpleSAML\OpenID\Codebooks\AtContextsEnum;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\VcDataModelException;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcAtContextClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Factories\VcDataModelClaimFactory;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims\VcCredentialStatusClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims\VcRefreshServiceClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims\VcRefreshServiceClaimValue;

class VcDataModel2ClaimFactory extends VcDataModelClaimFactory
{
    /**
     * @param mixed[] $otherContexts
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function buildVcAtContextClaimValue(
        string $baseContext,
        array $otherContexts,
    ): VcAtContextClaimValue {
        return new VcAtContextClaimValue(
            $baseContext,
            $otherContexts,
            AtContextsEnum::W3OrgNsCredentialsV2,
        );
    }


    /**
     * @param mixed[] $data
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function buildVcCredentialStatusClaimBag(array $data): VcCredentialStatusClaimBag
    {
        // If this is a single credential status claim, wrap it in a
        // CredentialStatusClaimBag.
        if ($this->helpers->arr()->isAssociative($data)) {
            return new VcCredentialStatusClaimBag(
                $this->buildVcCredentialStatusClaimValue($data),
            );
        }

        // We have multiple credential status claims. Wrap them in a
        // CredentialStatusClaimBag.
        $data = $this->helpers->type()->enforceNonEmptyArrayOfNonEmptyArrays($data);

        $vcCredentialStatusClaims = [];
        foreach ($data as $credentialStatusClaimValueData) {
            $vcCredentialStatusClaims[] = $this->buildVcCredentialStatusClaimValue($credentialStatusClaimValueData);
        }

        $firstCredentialStatusClaim = array_shift($vcCredentialStatusClaims);

        return new VcCredentialStatusClaimBag(
            $firstCredentialStatusClaim,
            ...$vcCredentialStatusClaims,
        );
    }


    /**
     * @param non-empty-array<mixed> $data
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function buildVcRefreshServiceClaimValue2(array $data): VcRefreshServiceClaimValue
    {
        $type = $data[ClaimsEnum::Type->value] ?? throw new VcDataModelException(
            'No Type claim value available.',
        );
        $typeClaimValue = $this->buildTypeClaimValue($type);

        return new VcRefreshServiceClaimValue($typeClaimValue, $data);
    }


    /**
     * @param mixed[] $data
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function buildVcRefreshServiceClaimBag2(array $data): VcRefreshServiceClaimBag
    {
        if ($this->helpers->arr()->isAssociative($data)) {
            $data = [$data];
        }

        $data = $this->helpers->type()->enforceNonEmptyArrayOfNonEmptyArrays($data);

        $vcRefreshServiceClaimValueData =  array_shift($data);

        $vcRefreshServiceClaimValue = $this->buildVcRefreshServiceClaimValue2($vcRefreshServiceClaimValueData);

        $vcRefreshServiceClaimValues = array_map(
            $this->buildVcRefreshServiceClaimValue2(...),
            $data,
        );

        return new VcRefreshServiceClaimBag(
            $vcRefreshServiceClaimValue,
            ...$vcRefreshServiceClaimValues,
        );
    }
}
