<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\OAuth2;

use Jose\Component\Core\JWK;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;
use SimpleSAML\OpenID\Exceptions\DpopProofException;
use SimpleSAML\OpenID\Jws\ParsedJws;
use Throwable;

/**
 * A DPoP proof JWT from RFC 9449, OAuth 2.0 Demonstrating Proof of Possession (DPoP).
 *
 * https://www.rfc-editor.org/rfc/rfc9449
 *
 * Construction makes the checks of section 4.3 which need nothing but the proof itself. The header parameters and
 * claims section 4.2 requires are present, with the JSON types it gives them (check 3). "typ" is "dpop+jwt"
 * (check 4). "alg" is not "none" and is an algorithm this library knows, all of which are asymmetric (check 5, as
 * far as it is not local policy). "jwk" holds a public key and nothing else (check 7), in its canonical
 * representation, of a type and a size the algorithm can be used with. A "crit" or a "b64" header parameter is
 * refused, whatever its value. "iat", and "nbf" and "exp" when present, are NumericDates and are held against the
 * fraction of a second they may carry: "iat" and "nbf" may not lie further in the future than the timestamp
 * validation leeway, and "exp" may not have passed even with it.
 *
 * What depends on the request the proof came with stays with the caller:
 * - checks 1 and 2, one DPoP header field holding one JWT: before parsing;
 * - the rest of check 5, an algorithm "supported by the application, and [...] acceptable per local policy":
 *   getAlgorithm() against the caller's list;
 * - check 6, the signature: verifyWithEmbeddedKey();
 * - checks 8 and 9, the method and the URI: matchesHttpRequest();
 * - check 10, the nonce: getNonce() against the one the server provided, if it provided one;
 * - check 11, "within an acceptable window": getIssuedAtNumericDate() against the window the server allows,
 *   section 11.1 having servers "only accept DPoP proofs for a limited time after their creation";
 *   construction refuses only a proof from too far in the future;
 * - check 12, at a protected resource: matchesAccessToken(), and getJwkThumbprint() against the key the access
 *   token is bound to;
 * - the replay check of section 11.1, on getJwtId() "in the context of the target URI".
 *
 * A failed check surfaces as a DpopProofException from the getter concerned (as a JwsException for the clock
 * checks on the timestamps, which the base class makes for every token type), or as an InvalidValueException for
 * a value of the wrong shape; validate() runs them all in one pass, so the constructor reports every failure at
 * once in one JwsException.
 *
 * @see \SimpleSAML\Test\OpenID\OAuth2\DpopProofTest
 */
class DpopProof extends ParsedJws
{
    /**
     * Section 4.2: "ath: Hash of the access token. The value MUST be the result of a base64url encoding (as
     * defined in Section 2 of [RFC7515]) the SHA-256 [SHS] hash of the ASCII encoding of the associated access
     * token's value."
     *
     * @return non-empty-string
     */
    public static function accessTokenHash(string $accessToken): string
    {
        /** @var non-empty-string $hash A SHA-256 digest is 32 octets, 43 characters in base64url. */
        $hash = rtrim(strtr(base64_encode(hash('sha256', $accessToken, true)), '+/', '-_'), '=');

        return $hash;
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getType(): string
    {
        // Section 4.2: "typ: A field with the value dpop+jwt, which explicitly types the DPoP proof JWT as
        // recommended in Section 3.11 of [RFC8725]." Compared as a media type, which RFC 7515 section 4.1.9 has
        // case-insensitive with "application/" implied when there is no '/'.
        $claimKey = ClaimsEnum::Typ->value;

        $typ = $this->getHeaderClaim($claimKey) ?? throw new DpopProofException('No Type header claim found.');
        $typ = $this->helpers->type()->enforceNonEmptyString($typ, $claimKey);

        if (!$this->helpers->mediaType()->areJwtTypesEqual($typ, JwtTypesEnum::DpopJwt->value)) {
            throw new DpopProofException(
                sprintf('Invalid Type header claim (%s), expected %s.', $typ, JwtTypesEnum::DpopJwt->value),
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
        // Section 4.2: "alg: An identifier for a JWS asymmetric digital signature algorithm from [IANA.JOSE.ALGS].
        // It MUST NOT be none or an identifier for a symmetric algorithm (Message Authentication Code (MAC))." The
        // inherited getter refuses "none" and any algorithm this library does not know, and the others it knows
        // are all asymmetric.
        return parent::getAlgorithm() ?? throw new DpopProofException('No Algorithm header claim found.');
    }


    /**
     * The public key the proof is signed with, as the "jwk" header parameter carries it.
     *
     * @return array<string,mixed>
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getJsonWebKey(): array
    {
        // Section 4.2: "jwk: Represents the public key chosen by the client in JSON Web Key (JWK) [RFC7517] format
        // as defined in Section 4.1.3 of [RFC7515]. It MUST NOT contain a private key." Held to the members of a
        // public key of an asymmetric type, see Helpers\Jwk::enforcePublicKey(). The key has to fit the algorithm
        // as well: an RSA key of 2048 bits or more, which RFC 7518 sections 3.3 and 3.5 require and FAPI 2.0
        // section 5.4.1 repeats, or a key on the curve the algorithm names (P-256, P-384 or P-521, or Ed25519 or
        // Ed448 for EdDSA), each above the 224 bits that section sets for elliptic curve keys.
        $claimKey = ClaimsEnum::Jwk->value;

        $jwk = $this->getHeaderClaim($claimKey) ?? throw new DpopProofException('No JWK header claim found.');
        $jwk = $this->helpers->jwk()->enforcePublicKey($jwk, $claimKey);

        $this->helpers->jwk()->enforceKeyFitsSignatureAlgorithm(
            $jwk,
            SignatureAlgorithmEnum::from($this->getAlgorithm()),
            $claimKey,
        );

        return $jwk;
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getJwtId(): string
    {
        // Section 4.2: "jti: Unique identifier for the DPoP proof JWT." Whether it was seen before is the
        // caller's check (section 11.1), and so is the size it is willing to store: "a server that is tracking
        // jti values should reject DPoP proof JWTs with unnecessarily large jti values or store only a hash
        // thereof."
        return parent::getJwtId() ?? throw new DpopProofException('No JWT ID claim found.');
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getHttpMethod(): string
    {
        // Section 4.2: "htm: The value of the HTTP method (Section 9.1 of [RFC9110]) of the request to which the
        // JWT is attached."
        $claimKey = ClaimsEnum::Htm->value;

        $htm = $this->getPayloadClaim($claimKey) ?? throw new DpopProofException('No HTTP Method claim found.');

        return $this->helpers->type()->enforceNonEmptyString($htm, $claimKey);
    }


    /**
     * The "htu" claim as the proof carries it; see matchesHttpRequest() for the comparison.
     *
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getHttpUri(): string
    {
        // Section 4.2: "htu: The HTTP target URI (Section 7.1 of [RFC9110]) of the request to which the JWT is
        // attached, without query and fragment parts." So an "http" or "https" URI; one that is not can match no
        // request, and is refused here rather than at the comparison. A query or a fragment is not refused, as
        // the comparison of section 4.3 ignores them.
        $claimKey = ClaimsEnum::Htu->value;

        $htu = $this->getPayloadClaim($claimKey) ?? throw new DpopProofException('No HTTP URI claim found.');
        $htu = $this->helpers->type()->enforceNonEmptyString($htu, $claimKey);

        if (is_null($this->helpers->url()->normalizeHttpTargetUri($htu))) {
            throw new DpopProofException('HTTP URI claim is not an http or https URI.');
        }

        return $htu;
    }


    /**
     * The Issued At claim as the proof carries it, a fraction of a second included, which is what an acceptance
     * window is to be compared with.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getIssuedAtNumericDate(): int|float
    {
        // Section 4.2: "iat: Creation timestamp of the JWT (Section 4.1.6 of [RFC7519])." Required.
        return parent::getIssuedAtNumericDate() ?? throw new DpopProofException('No Issued At claim found.');
    }


    /**
     * The Issued At claim in whole seconds; getIssuedAtNumericDate() has it as carried.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getIssuedAt(): int
    {
        return (int)$this->getIssuedAtNumericDate();
    }


    /**
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getAccessTokenHash(): ?string
    {
        // Section 4.2: required "When the DPoP proof is used in conjunction with the presentation of an access
        // token in protected resource access", so optional as far as the proof alone goes.
        $claimKey = ClaimsEnum::Ath->value;

        $ath = $this->getPayloadClaim($claimKey);

        return is_null($ath) ? null : $this->helpers->type()->enforceNonEmptyString($ath, $claimKey);
    }


    /**
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getNonce(): ?string
    {
        // Section 4.2: "nonce: A recent nonce provided via the DPoP-Nonce HTTP header." Required only when the
        // server provided one, which only the server knows.
        $claimKey = ClaimsEnum::Nonce->value;

        $nonce = $this->getPayloadClaim($claimKey);

        return is_null($nonce) ? null : $this->helpers->type()->enforceNonEmptyString($nonce, $claimKey);
    }


    /**
     * Section 4.3 check 6: "The JWT signature verifies with the public key contained in the jwk JOSE Header
     * Parameter." The verification uses the signature algorithms the factory was given: a proof signed with
     * another one fails here as a signature that does not verify.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\DpopProofException
     */
    public function verifyWithEmbeddedKey(): void
    {
        try {
            $this->verifyWithKey($this->getJsonWebKey());
        } catch (Throwable $throwable) {
            throw new DpopProofException(
                'Could not verify the DPoP proof signature with its own key.',
                (int)$throwable->getCode(),
                $throwable,
            );
        }
    }


    /**
     * The JWK SHA-256 Thumbprint of the proof's key (RFC 7638), base64url encoded: the value section 6.1 puts in
     * the "jkt" member of a bound access token's "cnf" claim, and section 10 in the "dpop_jkt" authorization
     * request parameter. getJsonWebKey() lets through only the members of a public key and those RFC 7517
     * defines for every key, so the members the computation takes are exactly the ones RFC 7638 section 3.2
     * requires, and only in their canonical representation, which RFC 7638 section 7 makes the condition for a
     * thumbprint to identify a key uniquely.
     *
     * It is the thumbprint of the key the proof names: only verifyWithEmbeddedKey() shows the proof was signed
     * with that key.
     *
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\DpopProofException
     */
    public function getJwkThumbprint(): string
    {
        try {
            /** @var non-empty-string $thumbprint */
            $thumbprint = (new JWK($this->getJsonWebKey()))->thumbprint('sha256');
        } catch (Throwable $throwable) {
            throw new DpopProofException(
                'Unable to compute the thumbprint of the DPoP proof key.',
                (int)$throwable->getCode(),
                $throwable,
            );
        }

        return $thumbprint;
    }


    /**
     * Section 4.3 checks 8 and 9: "The htm claim matches the HTTP method of the current request." and "The htu
     * claim matches the HTTP URI value for the HTTP request in which the JWT was received, ignoring any query and
     * fragment parts." The method is compared exactly, as RFC 9110 section 9.1 has "The method token is
     * case-sensitive". The URIs are compared after Helpers\Url::normalizeHttpTargetUri(), which drops the query
     * and the fragment, and applies the normalization the section asks for; a URI it does not take matches
     * nothing. For an OPTIONS request an empty path stays apart from "/", as RFC 9110 section 4.2.3 has it: the
     * one targets the server as a whole, the other its root resource.
     *
     * Which URI the request was received at is the caller's knowledge: the one it publishes for the endpoint, or
     * one it rebuilds from the request, with whatever trust that puts in proxy headers.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function matchesHttpRequest(string $method, string $uri): bool
    {
        if ($this->getHttpMethod() !== $method) {
            return false;
        }

        $isOptionsRequest = $method === 'OPTIONS';
        $proofUri = $this->helpers->url()->normalizeHttpTargetUri($this->getHttpUri(), $isOptionsRequest);
        $requestUri = $this->helpers->url()->normalizeHttpTargetUri($uri, $isOptionsRequest);

        return !is_null($proofUri) && $proofUri === $requestUri;
    }


    /**
     * The first half of section 4.3 check 12: "ensure that the value of the ath claim equals the hash of that
     * access token". False for a proof without "ath", which a proof presented with an access token has to carry,
     * and for an empty access token, which is none at all (RFC 6750 section 2.1 has a token be one or more
     * characters), whatever hash a proof carries for it.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function matchesAccessToken(string $accessToken): bool
    {
        $ath = $this->getAccessTokenHash();

        return $accessToken !== '' && !is_null($ath) && hash_equals(self::accessTokenHash($accessToken), $ath);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function validate(): void
    {
        // One callback per optional claim, since the check stops at the first null it finds.
        $nullChecks = array_map(
            fn(string $claimKey): \Closure => function () use ($claimKey): void {
                $this->enforceNoNullOptionalClaims([$claimKey]);
            },
            [
                ClaimsEnum::Nbf->value,
                ClaimsEnum::Exp->value,
                ClaimsEnum::Ath->value,
                ClaimsEnum::Nonce->value,
            ],
        );

        $this->validateByCallbacks(
            $this->getType(...),
            $this->getAlgorithm(...),
            $this->enforceNoCriticalHeaderParameters(...),
            $this->enforceNoUnencodedPayloadOption(...),
            $this->getJsonWebKey(...),
            $this->getJwtId(...),
            $this->getHttpMethod(...),
            $this->getHttpUri(...),
            $this->getIssuedAtNumericDate(...),
            // The base class checks these two again after this method, but only once everything here has
            // passed; listed here so that their failures are reported along with the others.
            $this->getNotBefore(...),
            $this->getExpirationTime(...),
            $this->getAccessTokenHash(...),
            $this->getNonce(...),
            ...$nullChecks,
        );
    }
}
