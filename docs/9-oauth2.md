# OAuth 2.0 Tools

Tools for the OAuth 2.0 profiles which are not part of OpenID Connect. For now
that is [RFC 9068](https://www.rfc-editor.org/rfc/rfc9068), the JSON Web Token
(JWT) Profile for OAuth 2.0 Access Tokens: an access token which is a signed
JWT, typed `at+jwt`, that a resource server can validate on its own.

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
