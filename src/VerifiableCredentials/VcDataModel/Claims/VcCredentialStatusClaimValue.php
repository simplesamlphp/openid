<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

class VcCredentialStatusClaimValue extends AbstractIdentifiedTypedClaimValue
{
    public function getName(): string
    {
        return ClaimsEnum::Credential_Status->value;
    }
}
