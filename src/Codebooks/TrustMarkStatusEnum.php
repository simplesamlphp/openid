<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

/**
 * https://openid.net/specs/openid-federation-1_0.html#name-trust-mark-status-response
 */
enum TrustMarkStatusEnum: string
{
    case Active = 'active';

    case Expired = 'expired';

    case Revoked = 'revoked';

    case Invalid = 'invalid';


    public function isValid(): bool
    {
        return match ($this) {
            self::Active => true,
            default => false,
        };
    }
}
