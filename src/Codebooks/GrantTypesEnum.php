<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

enum GrantTypesEnum: string
{
    case AuthorizationCode = 'authorization_code';
    case Implicit = 'implicit';
    case PreAuthorizedCode = 'urn:ietf:params:oauth:grant-type:pre-authorized_code';
    case RefreshToken = 'refresh_token';


    public function canBeUsedForVerifiableCredentialIssuance(): bool
    {
        return match ($this) {
            self::AuthorizationCode, self::PreAuthorizedCode => true,
            default => false,
        };
    }
}
