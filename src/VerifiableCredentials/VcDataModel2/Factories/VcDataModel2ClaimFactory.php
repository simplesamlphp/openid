<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Factories;

use SimpleSAML\OpenID\Codebooks\AtContextsEnum;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcAtContextClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Factories\VcDataModelClaimFactory;

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
}
