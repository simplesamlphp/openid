<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

class VcProofClaimValue extends AbstractTypedClaimValue
{
    public function getName(): string
    {
        return ClaimsEnum::Proof->value;
    }
}
