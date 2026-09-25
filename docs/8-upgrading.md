# Upgrading

The library is under development and breaking changes are expected along the
way. This page records the ones that change what already-working code does,
version by version, so a deployment can tell what will behave differently before
it moves.

Purely additive API — a new class, a new optional argument with a default that
preserves the old behaviour — is not listed here.

## 0.10.0

### String claims and header parameters are no longer cast into strings

`ParsedJws::getIssuer()`, `getSubject()`, `getJwtId()`, `getIdentifier()`,
`getKeyId()`, `getType()` and `getAlgorithm()` used to cast a scalar into a
string: `"iss": 42` came back as `"42"`, `"kid": true` as `"1"`. They now take
a JSON string only, and throw `InvalidValueException` for anything else, as
`JwtAccessToken` already did. RFC 7519 section 2 defines a StringOrURI (what
`iss` and `sub` are) as "A JSON string value", section 4.1.7 has `jti` be a
string, and RFC 7515 has `alg` (section 4.1.1), `kid` (4.1.4) and `typ` (4.1.9)
be strings. The same goes for `Core\RequestObject::getAlgorithm()`.

This affects every token type built on `ParsedJws`: entity statements, trust
marks, ID Tokens, Logout Tokens, client assertions, request objects,
credentials and proofs. A token that carried one of these as a number or a
boolean was malformed all along; it is refused now instead of being read as a
string it never contained.

### An unknown `alg` is a `JwsException`

`ParsedJws::getAlgorithm()` threw `EntityStatementException` for an algorithm
this library does not know, whatever kind of token it was reading. It now throws
`JwsException`, the parent class, as it already did for `none`. Code which
catches `JwsException` or `OpenIdException` is unaffected. Code which catches
`EntityStatementException` to handle this case has to catch `JwsException`
instead, for entity statements too, whose `getAlgorithm()` makes the same check.
An entity statement without an `alg` still throws `EntityStatementException`.

### Telling a value that is not a JWS from a JWS that fails a check

This part is additive, and is listed so a caller knows it can rely on it.
`JwsDecoratorBuilder::fromToken()` throws the new `JwsParseException` when the
value can not be parsed as a JWS at all, and so does every `fromToken()` factory
method built on it but one: `StatusListTokenFactory::fromToken()` refuses
anything but a JWS Compact Serialization itself, before parsing, with
`StatusListTokenException` as before. `JwsParseException` extends
`JwsException`, so existing `catch` blocks still catch it. A token that parses
and then fails a check on construction (its lifetime, a claim) throws a plain
`JwsException`, as before. A caller that has to route a value by whether it is
a JWS, such as with `ParsedJwsFactory::fromToken()`, can now do so with a single
parse.

## 0.9.0

### A token is expired the second its `exp` is reached

`ParsedJws::getExpirationTime()` used to accept a token during the very second
named by `exp` plus the timestamp validation leeway, and reject it from the next
second on. RFC 7519 section 4.1.4 (and RFC 9068 section 4 for access tokens)
has the current time strictly *before* the expiration time, so that second now
counts as expired too. This affects every token type the library parses; a
deployment will only notice it if it presents tokens within one second of their
deadline.

### A Status List Token may not carry the `b64` header parameter

`StatusListToken` now refuses a token whose protected header carries RFC 7797's
`b64`, as `JwtAccessToken` does: RFC 7797 section 7 keeps the unencoded payload
option out of JWTs, and the JWS verifier would otherwise honour it. A token that
carried `b64` together with `crit` was already refused.

## 0.8.0

### The did:web identifier transform is static

DID document generation is new and additive, and documented in
[Decentralized Identifier (DID) Tools](7-did.md#publishing-a-document). One part
of it is not additive.

`DidWebResolver::buildDocumentUrl()` keeps its signature and its behaviour, but
the transform behind it moved into a new public static `documentUrlFor()`, and
the six protected helpers it uses — `buildAuthority()`, `requireHost()`,
`isIpLikeHost()`, `requirePort()`, `requireLiteralPathSegment()` and
`assertNotPercentEncoded()` — became `protected static`.

**A subclass that overrides any of those will now fail to load**, since PHP
forbids overriding a static method with a non-static one. Nothing else changes:
every refusal is the same refusal, with the same message.

The reason is that constructing a `DidWebResolver` constructs the HTTP client a
did:web fetch runs under, and two callers want the identifier rules while
fetching nothing: a deployment checking that the DID it was configured with
transforms to the URL it actually serves, and document publication, which must
not publish a document under an identifier this library could not resolve.

Adding `static` to such an override restores loading but not its effect — the
transform reaches these through `self::`, so it uses this class's
implementations. That is deliberate. There is one implementation of the rules
deciding where a did:web document may be fetched from, and a second one reachable
by subclassing would be a way around them rather than an extension point.

## 0.7.0

### Decentralized Identifier resolution

New, and documented in [Decentralized Identifier (DID) Tools](7-did.md). The
`\SimpleSAML\OpenID\Did` class gained a constructor, a resolver registry over
`did:jwk`, `did:key` and `did:web`, and `resolveVerificationMethod()`. Every
constructor argument is optional, so `new Did()` keeps working, and the two
methods that existed before — `didKeyResolver()` and `didJwkResolver()` — are
unchanged.

did:web resolution fetches a URL that whoever is being authenticated chose, so it
runs under its own destination policy rather than the one described in
[Outbound destination policy](6-outbound-destination-policy.md). If a deployment
allows non-public hosts or ranges for federation, **those exemptions do not
apply to DID resolution and must not be copied into it.**

### The minimum web-token/jwt-library version has been raised

`web-token/jwt-library` moved from `^3.4 || ^4.0.2` to `^3.4.10 || ^4.1.7`.

The versions the old constraint allowed carry four security advisories, fixed
in 3.4.10, 4.0.7 and 4.1.7:

- `JWSVerifier` takes the algorithm from the unprotected header, which allows
  algorithm confusion. This library verifies a JWS on nearly every path it has,
  so this is the one that matters most here.
- RSA1_5 decryption lacks implicit rejection, exposing a Bleichenbacher padding
  oracle.
- The Chacha20Poly1305 key encryption algorithm discards the Poly1305 tag, so it
  authenticates nothing.
- PBES2 key unwrapping accepts an unbounded `p2c` iteration count, which is a
  CPU-amplification denial of service.

The 4.0 line is dropped rather than pinned at 4.0.7, because `^4.0.7` would also
match 4.1.0 to 4.1.6, which are affected. A deployment on 4.0.x has to move to
4.1.7 or later.

### Fetched artifacts no longer appear in debug logs

`ArtifactFetcher` used to write the whole fetched artifact into a debug log
entry. It no longer does, and this affects every caller of it: entity statement
and JWKS fetching in `Federation`, Status List Token fetching in
`TokenStatusList`, and request object fetching in `RequestObject`. The log entry
itself remains — only the artifact body is gone from its context.

A body fetched from a destination somebody else named is attacker-influenced
content of unbounded length going into a log file, which is a volume and
log-forging problem rather than a secrecy one; these documents are public. The
fetcher gained `logArtifacts` and `maxLoggedArtifactLength` constructor
arguments, defaulting to *not* logging and to 2048 characters when asked to. No
facade passes them, so there is currently no way to turn this back on through
`Federation`, `TokenStatusList` or `RequestObject` — a caller that genuinely
needs the output has to construct its own `ArtifactFetcher`.

If a deployment depended on that debug output for troubleshooting, expect it to
be missing.

### Failed HTTP requests no longer quote the response body

When a fetch got a 4xx or 5xx response, the `HttpException` message read
`Error sending HTTP request to <uri>. Error was: <Guzzle message>`, and the
Guzzle message ends with a summary of up to 120 characters of the response
body. Most of what this library fetches is at a destination somebody else
named, so that put remote-chosen text into the message, and from there into
whatever the caller logged. Guzzle 8 escapes control characters in that
summary; Guzzle 7, which this library still supports, does not.

Such a failure is now described from the status line instead, in the same words
the decorator already used when `http_errors` was off:
`Unexpected HTTP response for URI <uri>. Status code: <code>, reason: <reason>.`

The reason phrase in that replacement is reduced to printable ASCII and cut to
100 characters, since RFC 9110 permits a tab and any byte from 0x80 to 0xFF in
one. Guzzle escapes it inside its own messages; this does the same, on both
supported major versions.

Only `ClientException` and `ServerException` are treated this way, since Guzzle's
`http_errors` middleware is the only thing that adds a body summary and those are
the only two classes it raises. Every other failure keeps the message it came
with, and that includes ones carrying a response: the redirect limit, a
redirect refused for using a protocol other than `https`, a transfer that failed
after the headers arrived, a refused connection, a timeout, a name that does
not resolve.

This affects every fetch made through `HttpClientDecorator`: entity statements,
JWKS documents and subordinate listings in `Federation`, Status List Tokens in
`TokenStatusList`, request objects in `RequestObject`, and DID documents. Anything
matching on the old message text needs updating; the response body was never a
reliable thing to match on in any case.

### Unpinnable outbound requests now say why

Where a validated address could not be pinned to the connection, the refusal
under required pinning said only that it could not be pinned, and the warning
under preferred pinning asserted a single cause - that the request was not made
through the cURL handler - which was one of several and often the wrong one.

Both now name the actual reason: no cURL handler, no cURL extension, a proxy
making the connection, a cURL option overriding the routing, or a streaming
request. `AddressPinner::unsupportedReason()` is the single answer both that and
`isSupported()` are derived from.

Only the message text changed; which requests are refused is unchanged. Anything
matching on the old wording needs updating.

### A trailing newline is no longer accepted in a URI

The patterns in `Codebooks\UriPattern` ended in `$`. PCRE lets `$` match
immediately before a trailing newline, so a value ending in one passed
validation and kept the newline. Both patterns now end in `$D`, which refuses it.

A value that used to be accepted and carried its newline onward now throws
`InvalidValueException` at validation instead. This reaches:

- `iss` on an ID Token and on a Logout Token,
- the `uri` of a Status Reference and the `aggregation_uri` of a Status List,
  both of which are fetched,
- the `id` of an issued credential, in `JwtVcJson`, `VcSdJwt` and the Verifiable
  Credential data model claim factories,
- the `sub` claim of a Status List Token.

Nothing legitimate produces such a value, so in practice this surfaces a
producer that was already emitting a malformed URI.

### did:key multicodec identifiers corrected

The table mapping multicodec identifiers to key types was wrong, and both copies
of it — the canonical one and the frozen dispatch inside
`DidKeyJwkResolver::extractJwkFromDidKey()` — have been corrected against the
multiformats registry:

| Identifier | Was read as | Is now |
| --- | --- | --- |
| `0x1200` | P-256 | P-256 (unchanged) |
| `0x1201` | P-256 | P-384 |
| `0x1202` | P-384 | P-521 |
| `0x1203` | P-521 | Ed448 in the registry, unsupported here |
| `0x1204` | unsupported | X448 in the registry, unsupported here |
| `0x1102` | P-256 | unassigned, refused |

Ed448 and X448 are refused rather than misrouted, but by the same catch-all
branch that refuses any identifier the decoder does not know, not by a case
named for them.

The old table therefore rejected valid P-384 and P-521 keys, and would have
decoded an Ed448 key as P-521. No wrong key was ever produced from mismatched
material, because the point length checks refused it, so this was an
interoperability defect rather than a vulnerability.

What that does **not** mean is that every value the old table accepted is still
accepted. A value whose identifier and material happened to agree with the
wrong mapping decoded successfully before and is refused now — `0x1201` or
`0x1102` carrying a P-256 point, `0x1202` carrying a P-384 point, `0x1203`
carrying a P-521 point. Such a value was mislabelled to begin with, but a
deployment that produced one will see it stop resolving.

The correction also does not make every NIST-curve `did:key` resolvable. Only
the **uncompressed** point encoding is decoded, and the `did:key` method
specifies the compressed one for these curves; `createP256Jwk()` refuses a
compressed point explicitly and the P-384 and P-521 equivalents accept nothing
but the uncompressed form. So P-384 and P-521 values now resolve **where they
carry an uncompressed point**, which is the encoding this library has always
handled, rather than in general.

### A did:key JSON JWK without `use` is judged by its curve

A `did:key` value can carry a raw JSON JWK rather than raw key material,
under multicodec `0xeb51`. Where such a JWK omitted `use`, the member used to be
set to `sig` unconditionally. It is now inferred from the curve: X25519 and X448
get `enc`, everything else `sig`.

The old behaviour published a key agreement key under a signing use, which is
a claim the key does not support. `DidKeyJwkResolver::createJwkFromRawJson()`
delegates to the shared decoder, so this reaches the existing
`extractJwkFromDidKey()` path as well as the new resolvers.

### did:key and did:jwk values are now length-bounded

Decoding is the expensive part of resolving a self-describing DID, and both
paths took a value of any length:

- `MultibaseKeyDecoder::base58BtcDecode()` refuses more than 2048 characters
  (`MAX_BASE58_LENGTH`). Base58 decoding is superlinear in its input, so a value
  the size of a whole document cost seconds of CPU on something that could never
  have been a key. `DidKeyJwkResolver::base58BtcDecode()` delegates here, so the
  bound applies to the existing `did:key` path as well.
- `DidJwkResolver::extractJwkFromDidJwk()` refuses an encoded value longer than
  4096 characters (`MAX_ENCODED_JWK_LENGTH`), before the base64url decode and the
  JSON decode run.

`DidJwkResolver::generateDidJwkFromJwk()` enforces the same 4096-character bound
and so **can now throw** `Exceptions\DidException`, where it previously threw
only `\JsonException`. A JWK carrying a certificate chain in `x5c` is what
realistically crosses it; without the check the method could emit an identifier
that the same class would then refuse to resolve.

An RSA-4096 public JWK encodes to roughly 1100 characters and every curve key is
far shorter, so neither bound refuses a legitimate key.
