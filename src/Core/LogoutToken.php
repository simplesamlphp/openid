<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Core;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\UriPattern;
use SimpleSAML\OpenID\Exceptions\LogoutTokenException;
use SimpleSAML\OpenID\Jws\ParsedJws;

/**
 * Logout Token abstraction from
 * https://openid.net/specs/openid-connect-backchannel-1_0.html#LogoutToken
 */
class LogoutToken extends ParsedJws
{
    public function getIssuer(): string
    {
        // REQUIRED. Issuer Identifier, as specified in Section 2 of
        // [OpenID.Core].
        $iss = parent::getIssuer() ?? throw new LogoutTokenException('No Issuer claim found.');

        // We will leave the possibility of http usage for local testing purposes.
        return $this->helpers->type()->enforceUri($iss, 'Issuer claim', UriPattern::HttpNoQueryNoFragment->value);
    }


    public function getAudience(): array
    {
        // REQUIRED. Audience(s), as specified in Section 2 of [OpenID.Core].
        return parent::getAudience() ?? throw new LogoutTokenException('No Audience claim found.');
    }


    public function getIssuedAt(): int
    {
        // REQUIRED. Issued at time, as specified in Section 2 of [OpenID.Core].
        return parent::getIssuedAt() ?? throw new LogoutTokenException('No Issued At claim found.');
    }


    public function getExpirationTime(): int
    {
        // REQUIRED. Expiration time, as specified in Section 2 of
        // [OpenID.Core].
        return parent::getExpirationTime() ?? throw new LogoutTokenException('No Expiration Time claim found.');
    }


    public function getJwtId(): string
    {
        // REQUIRED. Unique identifier for the token, as specified in Section 9
        // of [OpenID.Core].
        return parent::getJwtId() ?? throw new LogoutTokenException('No JWT ID claim found.');
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\LogoutTokenException
     * @return mixed[]
     */
    public function getEvents(): array
    {
        // REQUIRED. Claim whose value is a JSON object containing the member
        // name http://schemas.openid.net/event/backchannel-logout. This
        // declares that the JWT is a Logout Token. The corresponding member
        // value MUST be a JSON object and SHOULD be the empty JSON object {}.
        $events = $this->getPayloadClaim(ClaimsEnum::Events->value);

        if (is_null($events)) {
            throw new LogoutTokenException('No Events claim found.');
        }

        if (
            (!is_array($events)) ||
            (!array_key_exists('http://schemas.openid.net/event/backchannel-logout', $events))
        ) {
            throw new LogoutTokenException('Malformed events claim.');
        }

        return $events;
    }


    /**
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getSessionId(): ?string
    {
        // OPTIONAL. Session ID - String identifier for a Session. This
        // represents a Session of a User Agent or device for a logged-in
        // End-User at an RP. Different sid values are used to identify
        // distinct sessions at an OP. The sid value need only be unique in
        // the context of a particular issuer. Its contents are opaque to the
        // RP. Its syntax is the same as an OAuth 2.0 Client Identifier.

        $sid = $this->getPayloadClaim(ClaimsEnum::Sid->value);

        if (is_null($sid)) {
            return null;
        }

        return $this->helpers->type()->ensureNonEmptyString($sid);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getNonce(): null
    {
        $nonce = $this->getPayloadClaim(ClaimsEnum::Nonce->value);

        if (!is_null($nonce)) {
            throw new LogoutTokenException('Nonce claim is forbidden in Logout Token.');
        }

        return null;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\LogoutTokenException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function validateSubOrSidOrBoth(): void
    {
        if (
            is_null($this->getSubject()) &&
            is_null($this->getSessionId())
        ) {
            throw new LogoutTokenException('Missing Subject and Session ID claim in Logout Token.');
        }
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function validate(): void
    {
        $this->validateByCallbacks(
            $this->getIssuer(...),
            $this->getAudience(...),
            $this->getIssuedAt(...),
            $this->getExpirationTime(...),
            $this->getJwtId(...),
            $this->getEvents(...),
            $this->getSessionId(...),
            $this->getNonce(...),
            $this->validateSubOrSidOrBoth(...),
            $this->getAlgorithm(...),
        );
    }
}
