<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Core;

/**
 * ID Token Hint abstraction.
 *
 * An ID Token Hint is an ID Token previously issued by the Authorization Server, passed as a hint about the
 * End-User's current or past authenticated session (for example the id_token_hint parameter from
 * https://openid.net/specs/openid-connect-core-1_0.html#AuthRequest or the RP-Initiated Logout specification).
 *
 * It behaves exactly like a regular {@see IdToken}, except that the Expiration Time (exp) claim is not validated
 * against the current time. This is because an ID Token Hint is commonly sent after it has already expired. The
 * Not Before (nbf) and Issued At (iat) claims are still validated: a previously issued token always has those in
 * the past, so a future value would still indicate an invalid token.
 *
 * @see \SimpleSAML\Test\OpenID\Core\IdTokenHintTest
 */
class IdTokenHint extends IdToken
{
    protected function shouldValidateExpirationTime(): bool
    {
        return false;
    }
}
