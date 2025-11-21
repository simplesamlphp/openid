<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

class VcEvidenceClaimValue extends AbstractTypedClaimValue
{
    public function getName(): string
    {
        return ClaimsEnum::Evidence->value;
    }
}
