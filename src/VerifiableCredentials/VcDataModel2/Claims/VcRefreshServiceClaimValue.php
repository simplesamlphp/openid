<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\AbstractTypedClaimValue;

class VcRefreshServiceClaimValue extends AbstractTypedClaimValue
{
    public function getName(): string
    {
        return ClaimsEnum::Refresh_Service->value;
    }
}
