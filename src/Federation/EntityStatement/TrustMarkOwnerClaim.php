<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityStatement;

class TrustMarkOwnerClaim
{
    public function __construct(
        protected readonly string $subject,
    ) {
    }
}
