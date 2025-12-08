<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Core;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\UriPattern;
use SimpleSAML\OpenID\Exceptions\IdTokenException;
use SimpleSAML\OpenID\Jws\ParsedJws;

/**
 * ID Token abstraction from
 * https://openid.net/specs/openid-connect-core-1_0.html#IDToken
 */
class IdToken extends ParsedJws
{
    public function getIssuer(): string
    {
        // REQUIRED. Issuer Identifier for the Issuer of the response. The iss
        // value is a case-sensitive URL using the https scheme that contains
        // scheme, host, and optionally, port number and path components and
        // no query or fragment components.
        $iss = parent::getIssuer() ?? throw new IdTokenException('No Issuer claim found.');

        // We will leave the possibility of http usage for local testing purposes.
        return $this->helpers->type()->enforceUri($iss, 'Issuer claim', UriPattern::HttpNoQueryNoFragment->value);
    }


    public function getSubject(): string
    {
        // REQUIRED. Subject Identifier. A locally unique and never reassigned
        // identifier within the Issuer for the End-User, which is intended to
        // be consumed by the Client, e.g., 24400320 or
        // AItOawmwtWwcT0k51BayewNvutrJUqsvl6qs7A4. It MUST NOT exceed 255
        // ASCII [RFC20] characters in length. The sub value is a
        // case-sensitive string.
        $sub = parent::getSubject() ?? throw new IdTokenException('No Subject claim found.');

        if (!mb_check_encoding($sub, 'ASCII')) {
            throw new IdTokenException('Subject claim contains non-ASCII characters.');
        }

        if (strlen($sub) > 255) {
            throw new IdTokenException('Subject claim exceeds 255 ASCII characters limit.');
        }

        return $sub;
    }


    public function getAudience(): array
    {
        // REQUIRED. Audience(s) that this ID Token is intended for. It MUST
        // contain the OAuth 2.0 client_id of the Relying Party as an audience
        // value. It MAY also contain identifiers for other audiences. In the
        // general case, the aud value is an array of case-sensitive strings.
        // In the common special case when there is one audience, the aud value
        // MAY be a single case-sensitive string.
        return parent::getAudience() ?? throw new IdTokenException('No Audience claim found.');
    }


    public function getExpirationTime(): int
    {
        return parent::getExpirationTime() ?? throw new IdTokenException('No Expiration Time claim found.');
    }


    public function getIssuedAt(): int
    {
        return parent::getIssuedAt() ?? throw new IdTokenException('No Issued At claim found.');
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getAuthTime(): ?int
    {
        // Time when the End-User authentication occurred. Its value is a JSON
        // number representing the number of seconds from 1970-01-01T00:00:00Z
        // as measured in UTC until the date/time. When a max_age request is
        // made or when auth_time is requested as an Essential Claim, then
        // this Claim is REQUIRED; otherwise, its inclusion is OPTIONAL.
        // (The auth_time Claim semantically corresponds to the OpenID 2.0 PAPE
        // [OpenID.PAPE] auth_time response parameter.)

        $authTime = $this->getPayloadClaim(ClaimsEnum::AuthTime->value);

        if (is_null($authTime)) {
            return null;
        }

        return $this->helpers->type()->ensureInt($authTime, ClaimsEnum::AuthTime->value);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getNonce(): ?string
    {
        // String value used to associate a Client session with an ID Token,
        // and to mitigate replay attacks. The value is passed through
        // unmodified from the Authentication Request to the ID Token. If
        // present in the ID Token, Clients MUST verify that the nonce Claim
        // Value is equal to the value of the nonce parameter sent in the
        // Authentication Request. If present in the Authentication Request,
        // Authorization Servers MUST include a nonce Claim in the ID Token
        // with the Claim Value being the nonce value sent in the Authentication
        // Request. Authorization Servers SHOULD perform no other processing
        // on nonce values used. The nonce value is a case-sensitive string.
        $nonce = $this->getPayloadClaim(ClaimsEnum::Nonce->value);

        if (is_null($nonce)) {
            return null;
        }

        return $this->helpers->type()->ensureNonEmptyString($nonce, ClaimsEnum::Nonce->value);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getAuthenticationContextClassReference(): ?string
    {
        // OPTIONAL. Authentication Context Class Reference. String specifying
        // an Authentication Context Class Reference value that identifies the
        // Authentication Context Class that the authentication performed
        // satisfied. The value "0" indicates the End-User authentication did
        // not meet the requirements of ISO/IEC 29115 [ISO29115] level 1.
        // For historic reasons, the value "0" is used to indicate that there
        // is no confidence that the same person is actually there.
        // Authentications with level 0 SHOULD NOT be used to authorize access
        // to any resource of any monetary value. (This corresponds to the
        // OpenID 2.0 PAPE [OpenID.PAPE] nist_auth_level 0.) An absolute URI
        // or an RFC 6711 [RFC6711] registered name SHOULD be used as the acr
        // value; registered names MUST NOT be used with a different meaning
        // than that which is registered. Parties using this claim will need to
        // agree upon the meanings of the values used, which may be context -
        // specific. The acr value is a case-sensitive string.

        $acr = $this->getPayloadClaim(ClaimsEnum::Acr->value);

        if (is_null($acr)) {
            return null;
        }

        return $this->helpers->type()->ensureNonEmptyString($acr, ClaimsEnum::Acr->value);
    }


    /**
     * @return ?string[]
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getAuthenticationMethodsReferences(): ?array
    {
        // OPTIONAL. Authentication Methods References. JSON array of strings
        // that are identifiers for authentication methods used in the
        // authentication. For instance, values might indicate that both
        // password and OTP authentication methods were used. The amr value is
        // an array of case-sensitive strings. Values used in the amr Claim
        // SHOULD be from those registered in the IANA Authentication Method
        // Reference Values registry [IANA.AMR] established by [RFC8176];
        // parties using this claim will need to agree upon the meanings of any
        // unregistered values used, which may be context-specific.

        $amr = $this->getPayloadClaim(ClaimsEnum::Amr->value);

        if (is_null($amr)) {
            return null;
        }

        return $this->helpers->type()->ensureArrayWithValuesAsNonEmptyStrings($amr, ClaimsEnum::Amr->value);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getAuthorizedParty(): ?string
    {
        // OPTIONAL. Authorized party - the party to which the ID Token was
        // issued. If present, it MUST contain the OAuth 2.0 Client ID of this
        // party. The azp value is a case-sensitive string containing a
        // StringOrURI value. Note that in practice, the azp Claim only occurs
        // when extensions beyond the scope of this specification are used;
        // therefore, implementations not using such extensions are encouraged
        // to not use azp and to ignore it when it does occur.

        $azp = $this->getPayloadClaim(ClaimsEnum::Azp->value);

        if (is_null($azp)) {
            return null;
        }

        return $this->helpers->type()->ensureNonEmptyString($azp, ClaimsEnum::Azp->value);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getAccessTokenHash(): ?string
    {
        // OPTIONAL. Access Token hash value. Its value is the base64url
        // encoding of the left-most half of the hash of the octets of the ASCII
        // representation of the access_token value, where the hash algorithm
        // used is the hash algorithm used in the alg Header Parameter of the
        // ID Token's JOSE Header. For instance, if the alg is RS256, hash the
        // access_token value with SHA-256, then take the left-most 128 bits
        // and base64url-encode them. The at_hash value is a case-sensitive
        // string.
        $aTHash = $this->getPayloadClaim(ClaimsEnum::ATHash->value);

        if (is_null($aTHash)) {
            return null;
        }

        return $this->helpers->type()->ensureNonEmptyString($aTHash, ClaimsEnum::ATHash->value);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getCodeHash(): ?string
    {
        // Code hash value. Its value is the base64url encoding of the left-most
        // half of the hash of the octets of the ASCII representation of the
        // code value, where the hash algorithm used is the hash algorithm used
        // in the alg Header Parameter of the ID Token's JOSE Header. For
        // instance, if the alg is HS512, hash the code value with SHA-512,
        // then take the left-most 256 bits and base64url-encode them. The
        // c_hash value is a case-sensitive string.
        //If the ID Token is issued from the Authorization Endpoint with a code,
        // which is the case for the response_type values code id_token and
        // code id_token token, this is REQUIRED; otherwise, its inclusion is
        // OPTIONAL.

        $cHash = $this->getPayloadClaim(ClaimsEnum::CHash->value);

        if (is_null($cHash)) {
            return null;
        }

        return $this->helpers->type()->ensureNonEmptyString($cHash, ClaimsEnum::CHash->value);
    }


    /**
     * @return ?array<string, string>
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getSubJwk(): ?array
    {
        // Public key used to check the signature of an ID Token issued by a
        // Self-Issued OpenID Provider, as specified in Section 7. The key is a
        // bare key in JWK [JWK] format (not an X.509 certificate value).
        // The sub_jwk value is a JSON object. Use of the sub_jwk Claim is NOT
        // RECOMMENDED when the OP is not Self-Issued.
        $subJwk = $this->getPayloadClaim(ClaimsEnum::SubJwk->value);

        if (is_null($subJwk)) {
            return null;
        }

        return $this->helpers->type()
            ->ensureArrayWithKeysAndValuesAsNonEmptyStrings($subJwk, ClaimsEnum::SubJwk->value);
    }


    protected function validate(): void
    {
        $this->validateByCallbacks(
            $this->getIssuer(...),
            $this->getSubject(...),
            $this->getAudience(...),
            $this->getExpirationTime(...),
            $this->getIssuedAt(...),
            $this->getAuthTime(...),
            $this->getNonce(...),
            $this->getAuthenticationContextClassReference(...),
            $this->getAuthenticationMethodsReferences(...),
            $this->getAuthorizedParty(...),
            $this->getAccessTokenHash(...),
            $this->getCodeHash(...),
            $this->getSubJwk(...),
        );
    }
}
