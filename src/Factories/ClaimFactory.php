<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Factories;

use SimpleSAML\OpenID\Claims\GenericClaim;
use SimpleSAML\OpenID\Helpers;

class ClaimFactory
{
    public function __construct(
        protected readonly Helpers $helpers,
    ) {
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function buildGeneric(mixed $key, mixed $value): GenericClaim
    {
        return new GenericClaim(
            $this->helpers->type()->ensureString($key, 'ClaimKey'),
            $value,
        );
    }
}
