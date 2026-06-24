<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

/**
 * @see \SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialStatusClaimValueTest
 */
class VcCredentialStatusClaimValue extends AbstractIdentifiedTypedClaimValue
{
    public function getName(): string
    {
        return ClaimsEnum::Credential_Status->value;
    }
}
