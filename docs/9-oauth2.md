# OAuth 2.0 Tools

Tools for the OAuth 2.0 profiles which are not part of OpenID Connect. For now
that is [RFC 9068](https://www.rfc-editor.org/rfc/rfc9068), the JSON Web Token
(JWT) Profile for OAuth 2.0 Access Tokens: an access token which is a signed
JWT, typed `at+jwt`, that a resource server can validate on its own; and the
proofs of [RFC 9449](https://www.rfc-editor.org/rfc/rfc9449), OAuth 2.0
Demonstrating Proof of Possession (DPoP), see [DPoP proofs](#dpop-proofs).

To use it, create an instance of the `\SimpleSAML\OpenID\OAuth2` class.

```php
use SimpleSAML\OpenID\OAuth2;

$oAuth2Tools = new OAuth2();
```

Signature algorithms default to `RS256`, which RFC 9068 section 2.1 has every
conforming authorization server and resource server support. Hand the
constructor a `SupportedAlgorithms` instance to widen that.

## Authorization server side

`JwtAccessTokenFactory::fromData()` signs a JWT access token. The `typ` header
is written by the factory (`at+jwt`), whatever the given header carries, and
the payload is validated as the profile requires before the token is returned:
a payload missing one of `iss`, `sub`, `aud`, `client_id`, `exp`, `iat` or
`jti` (section 2.2) is refused, so a misconfigured issuer can not mint an
incomplete token.

```php
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

$jwtAccessToken = $oAuth2Tools->jwtAccessTokenFactory()->fromData(
    $signingKey, // \SimpleSAML\OpenID\Jwk\JwkDecorator
    SignatureAlgorithmEnum::RS256,
    [
        ClaimsEnum::Iss->value => 'https://authorization-server.example.com/',
        ClaimsEnum::Sub->value => '5ba552d67',
        ClaimsEnum::Aud->value => 'https://rs.example.com/',
        ClaimsEnum::Exp->value => time() + 3600,
        ClaimsEnum::Iat->value => time(),
        ClaimsEnum::Jti->value => 'dbe39bf3a3ba4238a513f51d6e1691c4',
        ClaimsEnum::ClientId->value => 's6BhdRkqt3',
        ClaimsEnum::Scope->value => 'openid profile reademail',
    ],
    [
        ClaimsEnum::Kid->value => 'RjEwOwOA',
    ],
);

$token = $jwtAccessToken->getToken();
```

`sub` is required whatever the grant: for one without a resource owner, such
as client credentials, section 2.2 has it "correspond to an identifier the
authorization server uses to indicate the client application".

## Resource server side

`JwtAccessTokenFactory::fromToken()` parses a presented token. Construction
already refuses a token whose `typ` is anything other than `at+jwt` or
`application/at+jwt` (compared as a media type, so `at+JWT` passes and
`at+jwt;v=1` does not), whose algorithm is `none`, whose header marks any
parameter as critical (`crit`, RFC 7515 section 4.1.11: this library implements
no JWS extensions) or carries the unencoded payload option (`b64`, which RFC
7797 section 7 keeps out of JWTs), whose required claims are missing or not of
the JSON type the profile gives them (a numeric string is not a `NumericDate`,
a number is not a `sub`, an optional claim is optional by being absent, not by
being `null`), or whose `exp` has passed, allowing for the configured leeway.
The claims come out through typed getters.

```php
$jwtAccessToken = $oAuth2Tools->jwtAccessTokenFactory()->fromToken($token);

$jwtAccessToken->getType();       // 'at+jwt'
$jwtAccessToken->getIssuer();     // 'https://authorization-server.example.com/'
$jwtAccessToken->getSubject();    // '5ba552d67'
$jwtAccessToken->getAudience();   // ['https://rs.example.com/']
$jwtAccessToken->getClientId();   // 's6BhdRkqt3'
$jwtAccessToken->getScope();      // 'openid profile reademail'
$jwtAccessToken->getScopes();     // ['openid', 'profile', 'reademail']
$jwtAccessToken->getJwtId();      // 'dbe39bf3a3ba4238a513f51d6e1691c4'
```

The optional claims of section 2.2.1 (`auth_time`, `acr`, `amr`) and section
2.2.3.1 (`groups`, `roles`, `entitlements`) have getters too, returning `null`
when absent. Any other claim is available through `getPayloadClaim()`.

Three of the checks in section 4 depend on what the resource server knows about
itself, and stay with the caller:

```php
// The signature, against the authorization server's published keys.
$jwtAccessToken->verifyWithKeySet($jwks);

// The issuer, which "MUST exactly match".
if ($jwtAccessToken->getIssuer() !== $expectedIssuer) {
    // Reject with invalid_token.
}

// The audience, which "MUST" contain an identifier this resource server expects for itself.
if (!in_array($ownResourceIndicator, $jwtAccessToken->getAudience(), true)) {
    // Reject with invalid_token.
}
```

Construction runs all the checks and reports every failure at once in one
`\SimpleSAML\OpenID\Exceptions\JwsException` whose message lists each of them,
so that is the class to catch around `fromToken()` and `fromData()`. A getter
called on its own afterwards can only fail on what changes with time: an
expired `exp` throws `JwsException` from the base class. Where the profile's
own rules are what a getter enforces, the exception is the
`JwtAccessTokenException` subclass; a value of the wrong shape surfaces as
`InvalidValueException`. All three extend `OpenIdException`.

## DPoP proofs

A DPoP proof is a JWT a client signs with a key of its own and sends in the
`DPoP` header of a request, binding the request (its method and URI) to that
key. `$oAuth2Tools->dpopProofFactory()` builds one or parses one.

The factory holds the proof's timestamps to the leeway of the `OAuth2`
instance. Hand it a `DateInterval` to give DPoP a leeway of its own, such as
60 seconds, the furthest into the future FAPI 2.0 section 5.3.2.1 lets an `iat`
lie: `$oAuth2Tools->dpopProofFactory(new DateInterval('PT60S'))`. Signatures
are made and verified with the instance's `SupportedAlgorithms`, so widen those
to the algorithms the proofs use: RFC 9449 names no default, and its examples
use `ES256`.

### Client side

`DpopProofFactory::fromData()` signs a proof. The `typ` header (`dpop+jwt`) and
the `jwk` header are written by the factory, whatever the given header carries:
`jwk` is the public key of the signing key, `kty` and the key material only (the
signing key's `kid`, `use`, `key_ops` and the like describe the private key, and
a `key_ops` of `["sign"]` would make the proof unverifiable). A symmetric
signing key is refused before anything is signed, since its "public" form would
be the secret. The payload is the caller's, and is checked as on the server side
before the proof is returned.

```php
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\OAuth2\DpopProof;

$dpopProof = $oAuth2Tools->dpopProofFactory()->fromData(
    $dpopKey, // \SimpleSAML\OpenID\Jwk\JwkDecorator, holding the private key
    SignatureAlgorithmEnum::ES256,
    [
        ClaimsEnum::Jti->value => bin2hex(random_bytes(16)),
        ClaimsEnum::Htm->value => 'POST',
        ClaimsEnum::Htu->value => 'https://server.example.com/token',
        ClaimsEnum::Iat->value => time(),
        // With an access token, at a protected resource:
        // ClaimsEnum::Ath->value => DpopProof::accessTokenHash($accessToken),
    ],
);

$dpopHeaderValue = $dpopProof->getToken();
```

### Server side

`DpopProofFactory::fromToken()` parses a proof. Construction already makes the
checks of RFC 9449 section 4.3 which need nothing but the proof: the required
header parameters (`typ`, `alg`, `jwk`) and claims (`jti`, `htm`, `htu`, `iat`)
are present with the JSON types section 4.2 gives them; `typ` is `dpop+jwt`
(compared as a media type); `alg` is a known algorithm and not `none` (every
algorithm this library knows is asymmetric); `jwk` is a public key and nothing
else, in the one representation RFC 7518 defines for it (fixed-width curve
coordinates, RSA integers without leading zero octets, canonical base64url), so
that its thumbprint identifies it uniquely as RFC 7638 section 7 requires, and
of a type and size the algorithm can be used with (an RSA key of at least 2048
bits, an elliptic curve key on the curve the algorithm names); an RSA key whose
private key anyone could compute is refused where that is cheap to tell (an
exponent of 1, an even exponent or modulus, a modulus which is a perfect power
or has a prime factor below 752), and sizes are capped (modulus 8192 bits,
exponent 256 bits) so that an unauthenticated proof costs little to check; `htu` is an
`http` or `https` URI; `crit` and `b64` headers are refused; an optional claim
(`ath`, `nonce`, `nbf`, `exp`) is optional by being absent, not by being
`null`. `iat`, and `nbf` and `exp` when present, must be JSON numbers and are
compared with the clock fraction included, so `iat = now + 60.5` does not pass
a 60-second leeway: `iat` and `nbf` may not lie further ahead than the leeway,
and `exp` may not have passed even with it.

What depends on the request, and on the server's policy, stays with the caller.
Only the parsing and the signature check throw; the other checks return a
boolean, which the caller has to act on:

```php
use SimpleSAML\OpenID\Exceptions\JwsException;

try {
    // RFC 9449 section 4.3 checks 1 and 2, one DPoP header field holding one
    // JWT, come before parsing.
    $dpopProof = $oAuth2Tools->dpopProofFactory(new DateInterval('PT60S'))
        ->fromToken($dpopHeaderValue);

    // Check 6: the signature, with the key the proof carries.
    $dpopProof->verifyWithEmbeddedKey();
} catch (JwsException) {
    // Refuse with invalid_dpop_proof.
}

// Check 5: an algorithm this server accepts.
if (!in_array($dpopProof->getAlgorithm(), $acceptedAlgorithms, true)) {
    // Refuse with invalid_dpop_proof.
}

// Checks 8 and 9: the request's method and URI. The URI is the one the request
// was received at; query and fragment are ignored on both sides.
if (!$dpopProof->matchesHttpRequest('POST', 'https://server.example.com/token')) {
    // Refuse with invalid_dpop_proof.
}

// Check 11: the acceptance window. Construction refuses only a proof from too
// far ahead; how old a proof may be is the server's to decide.
if ($dpopProof->getIssuedAtNumericDate() < microtime(true) - 60) {
    // Refuse with invalid_dpop_proof.
}

// Section 11.1: refuse a jti seen before, in the context of the target URI,
// for as long as the window lasts; store a hash of it, not the value.
$replayKey = hash('sha256', json_encode([
    $dpopProof->getJwkThumbprint(),
    'https://server.example.com/token',
    $dpopProof->getJwtId(),
]));

// The key, as the "jkt" of a bound access token's "cnf" claim.
$jkt = $dpopProof->getJwkThumbprint();

// Check 12, at a protected resource: the proof is for this access token, and
// made with the key the token is bound to.
if (!$dpopProof->matchesAccessToken($accessToken)) {
    // Refuse with invalid_dpop_proof.
}
if (!hash_equals($boundJkt, $jkt)) {
    // Refuse with invalid_token.
}
```

The nonce of check 10 is not looked at either: `getNonce()` returns it, for a
server which provides nonces to compare.

Nor is every weak key: a prime RSA modulus, for one, is accepted, since testing
primality would cost each proof far more than its signature check does. Such a
key weakens only the binding to itself: a token is bound to the key its client
presented, so the tokens it exposes are those of a client that chose it.

`matchesHttpRequest()` compares the method exactly (RFC 9110 has methods
case-sensitive) and the URIs after `Helpers\Url::normalizeHttpTargetUri()`,
which applies the normalization section 4.3 asks for: scheme and host
lowercased, a default port or an empty one dropped, an empty path made `/`
(except for an OPTIONS request, where RFC 9110 section 4.2.3 keeps the two
apart), percent-encoded unreserved characters decoded and other
percent-encodings uppercased, dot segments removed, query and fragment dropped.
A URI with a userinfo part, or one which is not an `http` or `https` URI,
matches nothing. The normalization takes time linear in the URI's length.

RFC 9449 answers a proof which fails a check with `invalid_dpop_proof`
(`ErrorsEnum::InvalidDpopProof`), and an access token bound to another key than
the proof's with `invalid_token` (section 7.1, Figure 16).

Construction runs all its checks and reports every failure at once in one
`\SimpleSAML\OpenID\Exceptions\JwsException` whose message lists each of them,
so that is the class to catch around `fromToken()` and `fromData()`. A value
which is not a JWS at all throws `JwsParseException` from `fromToken()`, and a
symmetric signing key `DpopProofException` from `fromData()`; both extend
`JwsException`. After construction, `verifyWithEmbeddedKey()` and
`getJwkThumbprint()` throw `DpopProofException`, and a getter can only fail on
what changes with time: an `exp` that has passed since. The messages can quote
the value they refused, which came from the client: write the error code into a
response, not the message.
