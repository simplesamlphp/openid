<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\OAuth2;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\ContentTypesEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;
use SimpleSAML\OpenID\Exceptions\JwtAccessTokenException;
use SimpleSAML\OpenID\Jws\ParsedJws;

/**
 * JWT access token abstraction from RFC 9068, JSON Web Token (JWT) Profile for OAuth 2.0 Access Tokens.
 *
 * https://www.rfc-editor.org/rfc/rfc9068
 *
 * Claims are validated on construction, so an instance carries the header and the claims set the profile requires
 * (sections 2.1 and 2.2), each with the JSON shape the specification it points to gives it -- a string claim has to
 * be a JSON string and a NumericDate a JSON number, not something a cast would turn into one (the enforce*()
 * methods of Helpers\Type, as against the casting ensure*() ones) -- and the timestamps hold against the clock.
 * What it does not establish is that the token is the right one for whoever holds it. Section 4 leaves a resource
 * server three checks which depend on what it knows about itself: that the signature verifies against the
 * authorization server's keys (verifyWithKeySet()), that "iss" is exactly the issuer identifier it expects, and
 * that "aud" contains a resource indicator it recognizes as its own. Those stay with the caller.
 *
 * A failed check surfaces as a JwtAccessTokenException from the getter concerned; the constructor runs them all and
 * reports every failure at once in one JwsException, as the base class does for every token type.
 *
 * @see \SimpleSAML\Test\OpenID\OAuth2\JwtAccessTokenTest
 */
class JwtAccessToken extends ParsedJws
{
    /**
     * A "scope" claim as RFC 6749 section 3.3 shapes it: "scope = scope-token *( SP scope-token )" and
     * "scope-token = 1*( %x21 / %x23-5B / %x5D-7E )", so one or more tokens of printable ASCII other than space,
     * double quote and backslash, separated by single spaces.
     */
    protected const SCOPE_PATTERN = '#^[\x21\x23-\x5B\x5D-\x7E]+( [\x21\x23-\x5B\x5D-\x7E]+)*\z#';


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getType(): string
    {
        // Section 2.1: "JWT access tokens MUST include this media type in the "typ" header parameter to explicitly
        // declare that the JWT represents an access token complying with this profile." and "Therefore, the "typ"
        // value used SHOULD be "at+jwt"." Section 4: "The resource server MUST verify that the "typ" header value
        // is "at+jwt" or "application/at+jwt" and reject tokens carrying any other value."
        $claimKey = ClaimsEnum::Typ->value;

        $typ = $this->getHeaderClaim($claimKey) ?? throw new JwtAccessTokenException('No Type header claim found.');
        $typ = $this->helpers->type()->enforceNonEmptyString($typ, $claimKey);

        // Compared as a media type, which RFC 7515 section 4.1.9 has case-insensitive with "application/" implied
        // when there is no '/', so the "at+JWT" of the profile's own example (section 3, Figure 2) passes as well.
        if (!$this->helpers->mediaType()->areJwtTypesEqual($typ, JwtTypesEnum::AtJwt->value)) {
            throw new JwtAccessTokenException(
                sprintf(
                    'Invalid Type header claim (%s), expected %s or %s.',
                    $typ,
                    JwtTypesEnum::AtJwt->value,
                    ContentTypesEnum::ApplicationAtJwt->value,
                ),
            );
        }

        return $typ;
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getAlgorithm(): string
    {
        // Section 2.1: "JWT access tokens MUST be signed." and "JWT access tokens MUST NOT use "none" as the
        // signing algorithm." The inherited getter refuses "none" and any algorithm this library does not know.
        $claimKey = ClaimsEnum::Alg->value;

        $this->helpers->type()->enforceString(
            $this->getHeaderClaim($claimKey) ?? throw new JwtAccessTokenException('No Algorithm header claim found.'),
            $claimKey,
        );

        return parent::getAlgorithm() ?? throw new JwtAccessTokenException('No Algorithm header claim found.');
    }


    /**
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getKeyId(): ?string
    {
        // Not one the profile names, but section 4 has the resource server pick a verification key from those the
        // authorization server publishes, and "kid" is how a key is pointed at; a non-string one would be cast into
        // a key identifier that was never in the header.
        $claimKey = ClaimsEnum::Kid->value;

        $kid = $this->getHeaderClaim($claimKey);

        if (is_null($kid)) {
            return null;
        }

        $this->helpers->type()->enforceString($kid, $claimKey);

        return parent::getKeyId();
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getIssuer(): string
    {
        // Section 2.2: "iss REQUIRED - as defined in Section 4.1.1 of [RFC7519]." A StringOrURI there, so a string
        // is all that is asked of its shape. Section 4 has the resource server compare it: "The issuer identifier
        // for the authorization server (which is typically obtained during discovery) MUST exactly match the value
        // of the "iss" claim." That comparison is the caller's, since only it knows which issuer it expects.
        $claimKey = ClaimsEnum::Iss->value;

        $this->helpers->type()->enforceString(
            $this->getPayloadClaim($claimKey) ?? throw new JwtAccessTokenException('No Issuer claim found.'),
            $claimKey,
        );

        return parent::getIssuer() ?? throw new JwtAccessTokenException('No Issuer claim found.');
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getSubject(): string
    {
        // Section 2.2: "sub REQUIRED - as defined in Section 4.1.2 of [RFC7519]. In cases of access tokens
        // obtained through grants where a resource owner is involved, such as the authorization code grant, the
        // value of "sub" SHOULD correspond to the subject identifier of the resource owner. In cases of access
        // tokens obtained through grants where no resource owner is involved, such as the client credentials
        // grant, the value of "sub" SHOULD correspond to an identifier the authorization server uses to indicate
        // the client application."
        $claimKey = ClaimsEnum::Sub->value;

        $this->helpers->type()->enforceString(
            $this->getPayloadClaim($claimKey) ?? throw new JwtAccessTokenException('No Subject claim found.'),
            $claimKey,
        );

        return parent::getSubject() ?? throw new JwtAccessTokenException('No Subject claim found.');
    }


    /**
     * @return non-empty-array<non-empty-string>
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getAudience(): array
    {
        // Section 2.2: "aud REQUIRED - as defined in Section 4.1.3 of [RFC7519]. See Section 3 for indications on
        // how an authorization server should determine the value of "aud" depending on the request." Section 4:
        // "The resource server MUST validate that the "aud" claim contains a resource indicator value
        // corresponding to an identifier the resource server expects for itself." An empty list, or an empty
        // value in it, can never satisfy that, so neither is a valid audience here; which indicator to look for is
        // the caller's knowledge.
        $claimKey = ClaimsEnum::Aud->value;

        $aud = $this->getPayloadClaim($claimKey) ?? throw new JwtAccessTokenException('No Audience claim found.');

        // RFC 7519 section 4.1.3 shapes it as one string or an array of them; an object is neither, and a number
        // is not a string that happens to be spelled with digits.
        return $this->helpers->type()->enforceNonEmptyListOfNonEmptyStrings(
            is_string($aud) ? [$aud] : $aud,
            $claimKey,
        );
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getClientId(): string
    {
        // Section 2.2: "client_id REQUIRED - as defined in Section 4.3 of [RFC8693]." Which says: "The "client_id"
        // claim carries the client identifier of the OAuth 2.0 [RFC6749] client that requested the token."
        $claimKey = ClaimsEnum::ClientId->value;

        $clientId = $this->getPayloadClaim($claimKey) ?? throw new JwtAccessTokenException(
            'No Client ID claim found.',
        );

        return $this->helpers->type()->enforceNonEmptyString($clientId, $claimKey);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getExpirationTime(): int
    {
        // Section 2.2: "exp REQUIRED - as defined in Section 4.1.4 of [RFC7519]." Section 4: "The current time
        // MUST be before the time represented by the "exp" claim. Implementers MAY provide for some small leeway,
        // usually no more than a few minutes, to account for clock skew." The inherited getter applies the
        // configured leeway.
        $claimKey = ClaimsEnum::Exp->value;

        $this->helpers->type()->enforceNumericDate(
            $this->getPayloadClaim($claimKey) ?? throw new JwtAccessTokenException('No Expiration Time claim found.'),
            $claimKey,
        );

        return parent::getExpirationTime() ?? throw new JwtAccessTokenException('No Expiration Time claim found.');
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getIssuedAt(): int
    {
        // Section 2.2: "iat REQUIRED - as defined in Section 4.1.6 of [RFC7519]. This claim identifies the time at
        // which the JWT access token was issued."
        $claimKey = ClaimsEnum::Iat->value;

        $this->helpers->type()->enforceNumericDate(
            $this->getPayloadClaim($claimKey) ?? throw new JwtAccessTokenException('No Issued At claim found.'),
            $claimKey,
        );

        return parent::getIssuedAt() ?? throw new JwtAccessTokenException('No Issued At claim found.');
    }


    /**
     * Not one the profile names, but a token may carry it and the inherited validation acts on it when it is
     * there, so it is held to the same NumericDate shape as the timestamps that are named.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getNotBefore(): ?int
    {
        $claimKey = ClaimsEnum::Nbf->value;

        $nbf = $this->getPayloadClaim($claimKey);

        if (is_null($nbf)) {
            return null;
        }

        $this->helpers->type()->enforceNumericDate($nbf, $claimKey);

        return parent::getNotBefore();
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getJwtId(): string
    {
        // Section 2.2: "jti REQUIRED - as defined in Section 4.1.7 of [RFC7519]."
        $claimKey = ClaimsEnum::Jti->value;

        $this->helpers->type()->enforceString(
            $this->getPayloadClaim($claimKey) ?? throw new JwtAccessTokenException('No JWT ID claim found.'),
            $claimKey,
        );

        return parent::getJwtId() ?? throw new JwtAccessTokenException('No JWT ID claim found.');
    }


    /**
     * The "scope" claim as issued: a space-separated string, see getScopes() for the list.
     *
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getScope(): ?string
    {
        // Section 2.2.3: "If an authorization request includes a scope parameter, the corresponding issued JWT
        // access token SHOULD include a "scope" claim as defined in Section 4.2 of [RFC8693]." Which says: "The
        // value of the "scope" claim is a JSON string containing a space-separated list of scopes associated with
        // the token, in the format described in Section 3.3 of [RFC6749]." Optional, then, but a string of that
        // format once present; RFC 6749 section 3.3 gives it at least one scope token, so an empty string is not
        // one.
        $claimKey = ClaimsEnum::Scope->value;

        $scope = $this->getPayloadClaim($claimKey);

        if (is_null($scope)) {
            return null;
        }

        return $this->helpers->type()->enforceRegex(
            $this->helpers->type()->enforceString($scope, $claimKey),
            self::SCOPE_PATTERN,
            $claimKey,
        );
    }


    /**
     * The scope tokens of the "scope" claim, as RFC 6749 section 3.3 delimits them, in the order issued.
     *
     * @return ?non-empty-array<non-empty-string>
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getScopes(): ?array
    {
        $scope = $this->getScope();

        if (is_null($scope)) {
            return null;
        }

        // The pattern getScope() enforces admits single spaces between non-empty tokens only, so every piece is a
        // non-empty scope token.
        /** @var non-empty-array<non-empty-string> $scopes */
        $scopes = explode(' ', $scope);

        return $scopes;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getAuthTime(): ?int
    {
        // Section 2.2.1: "The claims listed in this section MAY be issued in the context of authorization grants
        // involving the resource owner and reflect the types and strength of authentication in the access token
        // that the authentication server enforced prior to returning the authorization response to the client."
        // "auth_time OPTIONAL - as defined in Section 2 of [OpenID.Core]."
        $claimKey = ClaimsEnum::AuthTime->value;

        $authTime = $this->getPayloadClaim($claimKey);

        if (is_null($authTime)) {
            return null;
        }

        $this->helpers->type()->enforceNumericDate($authTime, $claimKey);

        return $this->helpers->type()->ensureInt($authTime, $claimKey);
    }


    /**
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getAuthenticationContextClassReference(): ?string
    {
        // Section 2.2.1: "acr OPTIONAL - as defined in Section 2 of [OpenID.Core]."
        $claimKey = ClaimsEnum::Acr->value;

        $acr = $this->getPayloadClaim($claimKey);

        if (is_null($acr)) {
            return null;
        }

        return $this->helpers->type()->enforceNonEmptyString($acr, $claimKey);
    }


    /**
     * @return ?non-empty-string[]
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getAuthenticationMethodsReferences(): ?array
    {
        // Section 2.2.1: "amr OPTIONAL - as defined in Section 2 of [OpenID.Core]."
        $claimKey = ClaimsEnum::Amr->value;

        $amr = $this->getPayloadClaim($claimKey);

        if (is_null($amr)) {
            return null;
        }

        return $this->helpers->type()->enforceListOfNonEmptyStrings($amr, $claimKey);
    }


    /**
     * @return ?mixed[]
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getGroups(): ?array
    {
        return $this->getAuthorizationClaim(ClaimsEnum::Groups->value);
    }


    /**
     * @return ?mixed[]
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getRoles(): ?array
    {
        return $this->getAuthorizationClaim(ClaimsEnum::Roles->value);
    }


    /**
     * @return ?mixed[]
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getEntitlements(): ?array
    {
        return $this->getAuthorizationClaim(ClaimsEnum::Entitlements->value);
    }


    /**
     * One of the three authorization claims of section 2.2.3.1, as a list.
     *
     * "An authorization server wanting to include such attributes in a JWT access token SHOULD use the "groups",
     * "roles", and "entitlements" attributes of the "User" resource schema defined by Section 4.1.2 of
     * [RFC7643]) as claim types." and "Authorization servers SHOULD encode the corresponding claim values
     * according to the guidance defined in [RFC7643]." Each of the three is a multi-valued attribute there, so
     * a list is what is held to; what its members look like is the guidance's, and "No specific vocabulary is
     * provided for "roles" and "entitlements"", so the members are returned as issued.
     *
     * @return ?mixed[]
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    protected function getAuthorizationClaim(string $claimKey): ?array
    {
        $values = $this->getPayloadClaim($claimKey);

        if (is_null($values)) {
            return null;
        }

        return $this->helpers->type()->enforceList($values, $claimKey);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function validate(): void
    {
        $this->validateByCallbacks(
            $this->getType(...),
            $this->getAlgorithm(...),
            $this->getKeyId(...),
            $this->enforceNoCriticalHeaderParameters(...),
            $this->enforceNoUnencodedPayloadOption(...),
            function (): void {
                $this->enforceNoNullOptionalClaims(
                    [
                        ClaimsEnum::Nbf->value,
                        ClaimsEnum::Scope->value,
                        ClaimsEnum::AuthTime->value,
                        ClaimsEnum::Acr->value,
                        ClaimsEnum::Amr->value,
                        ClaimsEnum::Groups->value,
                        ClaimsEnum::Roles->value,
                        ClaimsEnum::Entitlements->value,
                    ],
                    [ClaimsEnum::Kid->value],
                );
            },
            $this->getIssuer(...),
            $this->getSubject(...),
            $this->getAudience(...),
            $this->getClientId(...),
            $this->getExpirationTime(...),
            $this->getIssuedAt(...),
            $this->getJwtId(...),
            $this->getScopes(...),
            $this->getAuthTime(...),
            $this->getAuthenticationContextClassReference(...),
            $this->getAuthenticationMethodsReferences(...),
            $this->getGroups(...),
            $this->getRoles(...),
            $this->getEntitlements(...),
        );
    }
}
