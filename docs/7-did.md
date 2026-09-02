# Decentralized Identifier (DID) Tools

Tools for resolving a [Decentralized Identifier](https://www.w3.org/TR/did-core/)
to the public key it names.

A DID identifies a subject without a registry behind it, and a **DID document**
lists the keys that subject uses and what each key is allowed to do. In this
library the point of resolving one is almost always the same: a token arrived
carrying a `kid` that is a DID URL, and the key to verify it with has to be
found. That is how a wallet identifies itself when it asks for a Verifiable
Credential, and how an issuer identifies itself to whoever later verifies one.

Three methods are supported: `did:jwk`, `did:key` and `did:web`. The first two
describe a key inside the identifier itself, so resolving one is arithmetic. The
third points at a web server, so resolving one is a fetch — and the identifier
that decides where that fetch goes is supplied by whoever is being
authenticated. Most of what follows is about that difference.

Resolution is most of what follows, but not all of it. A deployment identified by
a `did:web` has to *serve* the document that identifier resolves to, and
[Publishing a document](#publishing-a-document) builds that one.

To use these tools, create an instance of the `\SimpleSAML\OpenID\Did` class.

```php
use SimpleSAML\OpenID\Did;

$didTools = new Did();
```

Every argument is optional, and every one of them exists for `did:web`:

```php
use DateInterval;
use SimpleSAML\OpenID\Codebooks\AddressPinningModeEnum;
use SimpleSAML\OpenID\Did;

$didTools = new Did(
    // Ceiling for how long a resolved DID document is cached. A DID document
    // carries no expiry of its own, so this is the whole of its freshness rule.
    maxCacheDuration: new DateInterval('PT6H'),
    // Any PSR-16 cache. Without one, every request naming a did:web identifier
    // is another outbound fetch.
    cache: $cache,
    // Any PSR-3 logger. Resolution logs the DID and the outcome rather than
    // the document it fetched. See "What reaches the log" for the exception.
    logger: $logger,
    // Largest DID document body that will be read.
    maxFetchSizeBytes: 102400,
    // Where did:web fetches may be sent. See "Where a did:web fetch may go".
    destinationPolicy: null,
    // How strictly a validated address is held to the connection. Required by
    // default. Only relevant when no destinationPolicy is supplied, since one
    // carries its own mode.
    addressPinningMode: AddressPinningModeEnum::Required,
);
```

Note what is **not** there: a pre-built HTTP client, and free-form client
configuration. `Federation` and `TokenStatusList` accept both; this class
accepts neither, because both are ways to undo the hardening a did:web fetch
depends on. A pre-built client is returned unguarded by the destination policy,
and supplied configuration is merged *over* the timeout and redirect defaults
rather than under them.

No HTTP client is built at all until a `did:web` identifier is actually
resolved, so a deployment that only ever sees `did:jwk` never pays for one.

## Resolving a verification method

This is the method to reach for. Give it the DID URL naming the key — for a
proof, whatever the `kid` header carried — and it resolves the document that id
lives in and returns the single method it names.

```php
use SimpleSAML\OpenID\Codebooks\VerificationRelationshipEnum;
use SimpleSAML\OpenID\Exceptions\DidException;

$resolved = $didTools->resolveVerificationMethod(
    'did:web:example.org#key-1',
    VerificationRelationshipEnum::Authentication,
);

$resolved->getDid();            // 'did:web:example.org'
$resolved->getId()->getValue(); // 'did:web:example.org#key-1'
$resolved->getPublicJwk();      // ['kty' => 'OKP', 'crv' => 'Ed25519', ...]
$resolved->getRelationship();   // VerificationRelationshipEnum::Authentication
```

The canonical method id comes back alongside the key, not just the key. A caller
that received only key material would have to rebuild that id by string surgery,
and that id is what travels onward — into a `cnf` claim, into an issued
credential.

The second argument is the **verification relationship** the key must appear in,
and passing one is how a caller says what the key is about to be used for.
DID Core defines five: `AssertionMethod`, `Authentication`,
`CapabilityDelegation`, `CapabilityInvocation` and `KeyAgreement`. A key present
in the document but absent from the relationship asked for is a rejection, never
a fallback — which is the whole reason to pass one. Verifying a holder's proof
of possession means `Authentication`; a key the document only lists under
`keyAgreement` must not satisfy it.

Passing no relationship searches only the document's own `verificationMethod`
entries. That is deliberately **not** the same as searching every relationship:
a method embedded inline under a relationship is scoped to it and is not a
document-wide method.

The optional third argument is a deadline, as a unix timestamp with fractions:

```php
$deadline = microtime(true) + 15.0;

foreach ($proofs as $proof) {
    $resolved = $didTools->resolveVerificationMethod(
        $proof->getKeyId(),
        VerificationRelationshipEnum::Authentication,
        $deadline,
    );
}
```

A per-request timeout cannot bound an operation made of several fetches. One
credential request may carry several proofs naming several DIDs, and each one is
a fetch; a caller holding a budget for the request as a whole passes it here, and
resolution refuses to start a fetch that the budget no longer has room for. The
self-describing methods ignore it, since refusing a local construction because a
budget for network work elapsed would turn a free operation into a failure.

## Resolving a whole document

```php
$document = $didTools->resolveDocument('did:web:example.org');

$document->getId();
$document->getVerificationMethods();
$document->getVerificationMethodsFor(VerificationRelationshipEnum::AssertionMethod);
```

This takes a bare DID. A DID URL naming something inside a document is refused
rather than quietly reduced to the DID it sits under, since a caller asking for a
document by the id of one of its keys meant `resolveVerificationMethod()`.

## Which methods are supported

```php
$didTools->supportedMethods(); // ['did:jwk', 'did:key', 'did:web']
```

Spelled the way the DID Specification Registries spell a method, which is also
the spelling OpenID4VCI's `cryptographic_binding_methods_supported` uses, so a
deployment publishing what it accepts can publish this list as it stands.

### did:jwk and did:key

Nothing is fetched. The key *is* the identifier — base64url-encoded JSON for
`did:jwk`, a multibase-encoded key for `did:key` — so the document is
constructed from it. A document supplied from anywhere else is refused outright
for these two methods, since accepting one would let a different key be resolved
under an identifier that is supposed to be the key.

The method fixes the fragment. `did:jwk` uses `#0`; `did:key` repeats the
multibase value, so the id is `did:key:z6Mk…#z6Mk…`.

Which relationships the constructed document grants is decided by the key's own
`use` member: `sig` gives the four signature relationships, `enc` gives
`keyAgreement`, and anything else is refused rather than quietly earning every
relationship. An absent `use` is inferred from the curve — X25519 and X448 agree
keys, everything else signs. That is stricter than the rule applied to a
*retrieved* document, and deliberately so: in a document someone else published
the author chose the relationships and an absent `use` contradicts nothing,
whereas here this library is the one asserting them, and granting all five would
be it claiming an X25519 key authenticates.

### did:web

The identifier is transformed into a URL and the document is fetched from it.
Each `:` in the method-specific identifier becomes a `/`, and a `%3A` in the
authority is decoded to the port separator:

| Identifier | URL |
| --- | --- |
| `did:web:example.org` | `https://example.org/.well-known/did.json` |
| `did:web:example.org:users:alice` | `https://example.org/users/alice/did.json` |
| `did:web:example.org%3A3000` | `https://example.org:3000/.well-known/did.json` |

`%3A` between a syntactically valid host and a valid decimal port is the only
percent-encoded triplet accepted anywhere in the identifier. The method's
algorithm decodes exactly that one, so decoding any other would be a
liberalisation rather than conformance — and `%2F`, `%3F`, `%23` or `%40` would
let the identifier restructure the URL rather than merely name a path within it.
`.` and `..` path segments are refused for the same reason.

The host must be a domain name of at least two labels. That refuses IP literals,
which the method specification prohibits, and refuses single-label internal
names. `did:web:localhost` therefore does not resolve.

The retrieved document's own `id` must be the DID it was fetched for. Without
that check a redirect could substitute another party's document, and every later
check would run against the wrong subject.

## Where a did:web fetch may go

did:web resolution runs under a destination policy of its **own**, separate from
the one described in
[Outbound destination policy](6-outbound-destination-policy.md). Public
destinations only, `https` only, and address pinning **required** rather than
preferred.

By default there is nothing to configure — the policy the class builds for
itself is already the strict one. Where a deployment does need an exemption:

```php
use SimpleSAML\OpenID\Did;
use SimpleSAML\OpenID\Did\DidWebResolver;

$didTools = new Did(
    logger: $logger,
    destinationPolicy: DidWebResolver::buildDestinationPolicy(
        logger: $logger,
        allowedHosts: ['issuer.test.internal'],
        allowedCidrs: [],
    ),
);
```

> **Never build this from the federation configuration.** The hosts and ranges a
> deployment allows for federation were allowed so it could reach addresses it
> operates itself. Handing that list to DID resolution lets whoever supplies a
> DID name any of them, which is an allowlist being used for a purpose it was
> never granted for. What matters is not that DID resolution has no exemptions,
> but that they are not the federation ones.

An exemption here is a destination that whoever supplies a DID may send this
deployment to, so a non-empty list logs a `notice` when the policy is built. The
case it exists for is a deployment whose own hostname resolves to an address
inside a container network, which is how a development or test environment is
usually built, and where a public-only policy would otherwise make did:web
untestable rather than safe. Use the narrowest range that covers the
destination, and keep both lists empty in production.

Address pinning is `Required`, and `AddressPinningModeEnum::Preferred` is
**refused** rather than merely defaulted away from — passing it throws. Preferred
proceeds unpinned wherever the cURL handler is missing, which leaves the DNS
rebinding window open on exactly the fetches that are driven from outside. A
deployment that controls egress by other means may say so with `Disabled`. The
refusal applies to a policy assembled directly as well as to one built by the
helper, so there is no way around it.

### When the address cannot be pinned

An address is held to a connection with `CURLOPT_RESOLVE`, so pinning needs the
cURL extension, a cURL handler, and a connection this library is the one making.
Where any of those is missing, a policy that requires pinning **refuses the
request** rather than proceeding unpinned - and since that is the default here,
did:web resolution stops working rather than quietly weakening. The refusal
names which of the causes applies.

Two situations reach it, and they do not deserve the same answer.

**No cURL extension.** Nothing else is checking where the request goes, so
turning pinning off here really would leave the deployment open to the DNS
rebinding this exists to close. Install `ext-curl`. The oidc module already
requires it, so this case is mostly confined to using this library on its own.

**A forward proxy.** A proxy set through request options, or through
`http_proxy` / `https_proxy` / `all_proxy` in the environment, means the proxy
resolves the destination and makes the connection. There is nothing for this
library to pin - but the proxy is itself an egress control, usually a stronger
one than any application-layer check, which is what the
[outbound destination policy](6-outbound-destination-policy.md) page says in its
opening. Such a deployment is not unprotected; it has moved the enforcement
somewhere better. It says so explicitly:

```php
use SimpleSAML\OpenID\Codebooks\AddressPinningModeEnum;
use SimpleSAML\OpenID\Did;

$didTools = new Did(
    logger: $logger,
    addressPinningMode: AddressPinningModeEnum::Disabled,
);
```

**Check that the proxy really carries the DID fetches before doing this.**
`Disabled` applies to every did:web fetch this instance makes, while a proxy may
carry only some of them. Two ways it carries fewer than it looks:

- An exclusion list - a `no` entry in the proxy option, or `NO_PROXY` in the
  environment - sends matching hosts direct. This library does not read one, so
  such a deployment is still treated as unpinnable even though those requests
  could have been pinned.
- `http_proxy` alone does not proxy an https request, and did:web is always
  https. This library counts it anyway, so setting only `http_proxy` makes DID
  resolution unpinnable without a single fetch actually going through a proxy.

In both cases the connection is made directly, by this deployment, to a host
whoever supplied the DID chose - which is exactly the situation pinning exists
for. Turning pinning off there does not delegate the protection to the proxy; it
removes it. So `Disabled` is right only when the proxy genuinely carries every DID
destination: a proxy configured for https (`https_proxy` or `all_proxy`), with no
exclusion matching the hosts DIDs resolve to. Where that does not hold, fix the
proxy configuration so it does, rather than reaching for `Disabled`.

`Disabled` is a claim about the deployment, not a convenience: it says something
else is deciding where requests may go, for every destination this resolves.
`Preferred` remains refused, because it would proceed unpinned precisely where
pinning is unavailable, which is the case this whole section is about.

The policy in force can be read back, so the same rules can be applied to a
destination before anything is fetched:

```php
$didTools->destinationPolicy()->isUriAllowed('https://example.org/.well-known/did.json');
```

## Caching

Pass a PSR-16 cache and resolved documents are kept for the configured maximum
cache duration. A DID document carries no expiry of its own, so that ceiling is
the whole of its freshness rule.

A **failed** resolution is remembered too, for 60 seconds by default, so that a
DID which cannot be resolved does not turn every request naming it into another
outbound fetch. It stores the message the failed attempt reported rather than a
marker, so a repeat is answered with what the first attempt said. Deliberately
short, since whoever owns the DID may be in the middle of fixing it. A failure
caused by the caller's *own* deadline running out is never remembered: that says
nothing about the DID, and remembering it would let one slow request make a
working identifier unresolvable for everyone else.

No stale document is ever served. A cached document is re-parsed and
re-validated on every read, so a rule that tightens invalidates what is already
cached rather than grandfathering it, and a failed refresh has no path back to
the previous document.

## What bounds a resolution

Every one of these applies to input chosen by whoever is being authenticated.

| Bound | Default |
| --- | --- |
| Response body | 102400 bytes |
| JSON nesting depth | 32 |
| Request timeout | 5 s, with a 3 s connect timeout |
| Redirects | at most 3 hops, `https` only, revalidated per hop |
| Host length | 253 characters, labels of at most 63 |
| `did:key` base58 value | 2048 characters |
| `did:jwk` encoded JWK | 4096 characters |

The last two exist because decoding is the expensive part. Base58 decoding is
superlinear in its input, so a value the size of a whole document costs seconds
of CPU on something that could never have been a key; the `did:jwk` bound stops
a base64url decode and a JSON decode running over a string as long as the
request allowed. Both refuse before any decoding starts. An RSA-4096 public JWK
encodes to about 1100 characters, so neither bound refuses anything legitimate.

## Handling a failure

Everything throws `\SimpleSAML\OpenID\Exceptions\DidException`, and the message
splits two ways on purpose.

Anything on the way to the document — a policy refusal, a name that does not
resolve, a timeout, an oversized body, a non-2xx response, an exhausted deadline
— becomes the single message `Could not retrieve the DID document.` Those
outcomes each tell whoever supplied the DID something different about this
deployment's network, and together they turn resolution into a map of it. The
reason goes to the log instead.

Anything about the document's own contents keeps the message the parser gave it,
since that describes the wallet's document rather than this deployment's
network.

On the attempt that actually failed, the cause is chained onto the exception,
because it is useful when debugging. **Render the message and never the
chain**: the chain carries the detail the split exists to withhold. A caller
mapping this to a protocol error — OpenID4VCI's `invalid_proof`, say — is where
that rule is actually enforced.

A repeat answered from the negative cache carries the remembered message with
**no** chain, since what was remembered is the message rather than the
exception. Code that reads the chain has to tolerate its absence.

```php
use SimpleSAML\OpenID\Exceptions\DidException;

try {
    $resolved = $didTools->resolveVerificationMethod($keyId, $relationship);
} catch (DidException $exception) {
    $logger->warning('DID resolution failed.', [
        'did' => $keyId,
        'error' => $exception->getMessage(),
    ]);

    throw new SomeProtocolException('invalid_proof');
}
```

### What reaches the log

Resolution logs the DID it was asked for and what happened to it. The fetched
document is not logged, on success or on failure.

That takes some keeping. Guzzle describes a 4xx or 5xx response by appending a
summary of the response body — up to 120 characters — to its exception message,
and for a did:web fetch that body is chosen by whoever published the DID. The
message travels through the HTTP client decorator and would land in the `error`
line this class writes. So such a failure is described from its status line
instead — status code and reason phrase — and the summary never gets that far.

Only the two exception types Guzzle raises for 4xx and 5xx are treated this
way. Anything else keeps the message it came with, because that message is the
diagnostic: a refused connection, a timeout, the redirect limit, or a redirect
refused for pointing somewhere other than https.

What still reaches the log from the remote is the reason phrase of the status
line, and it is reduced to printable ASCII and cut to 100 characters before it
gets there. RFC 9110 lets one carry a tab and any byte from 0x80 to 0xFF, so it
can arrive as invalid UTF-8 and break a formatter that does not expect it. It
cannot carry a line break without the response failing to parse as HTTP at all,
so it was never able to forge a log line.

The **chained** cause is a different matter. It is still the original Guzzle
exception, and that one keeps its body summary. A logger handed the exception
object rather than its message — Monolog with `['exception' => $e]`, or an uncaught
handler stringifying the chain — will print it. This is the same rule as below:
render the message, not the chain.

## Publishing a document

Everything above is about documents somebody else published. This is the other
direction: the document a deployment identified by a `did:web` has to serve at
the URL its own identifier transforms to.

```php
use SimpleSAML\OpenID\Did\DidUrl;

$document = $didTools->didDocumentFactory()->forDidWeb(
    new DidUrl('did:web:example.org:oidc'),
    $signatureKeyPairBag,
);

$body = json_encode($document);
```

```json
{
    "@context": [
        "https://www.w3.org/ns/did/v1",
        "https://w3id.org/security/suites/jws-2020/v1"
    ],
    "id": "did:web:example.org:oidc",
    "verificationMethod": [
        {
            "id": "did:web:example.org:oidc#ec-signing-key-01",
            "type": "JsonWebKey2020",
            "controller": "did:web:example.org:oidc",
            "publicKeyJwk": {
                "kty": "EC", "crv": "P-256", "x": "…", "y": "…",
                "use": "sig", "alg": "ES256", "kid": "ec-signing-key-01"
            }
        }
    ],
    "assertionMethod": ["did:web:example.org:oidc#ec-signing-key-01"]
}
```

Only `did:web` has a document to publish. A `did:jwk` or `did:key` document is
derived from the identifier by whoever resolves it, so there is nothing to serve.

### Which keys to pass

Every pair in the bag becomes a verification method, and this decision is more
consequential than it looks: a key that signed something still being verified has
to stay in the document, in the relationship a verifier looks under, long after
it has stopped signing. Dropping a retired signer makes everything it signed
unverifiable — and leaving it under `verificationMethod` alone does not help a
verifier that checks the relationship. Pass every signer that is still valid, not
only the active one, and include any key used to sign something else the
deployment publishes, such as a Status List Token.

Only the public half of each pair is read, and it goes through the same validator
a retrieved document's key material does. A DID document must carry no private
key material, and this is the one document where *this* library is the party that
could put it there, so the check runs on the way out as well as on the way in.

A key pair is nonetheless what this takes, so a retired signer has to remain
configured as a pair for as long as it stays published — its private half cannot
be discarded first. In practice a deployment is keeping it anyway: re-signing
anything that key originally signed, such as a Status List Token, needs the
private half regardless of what the DID document says. A public-only input would
be the more natural shape for publication alone, and can be added when something
actually has one.

### Identifiers

The fragment naming each key is derived from its key identifier, percent-encoding
whatever a fragment cannot carry — so `ec-signing-key-01` is left as it is, while
a key identifier that is itself a DID URL keeps its colons and loses its `#`.
Encoding rather than refusing means any configured key identifier can be
published; the mapping is injective, so two of them can never collide, and it is
reversible, so a fragment read out of a published document still names its key.

Whatever emits a `kid` for one of these keys must ask for it here rather than
assembling the id itself:

```php
$didTools->didDocumentFactory()->verificationMethodIdFor(
    new DidUrl('did:web:example.org:oidc'),
    'ec-signing-key-01',
); // did:web:example.org:oidc#ec-signing-key-01
```

Two spellings of that mapping is how a signature comes to name a verification
method its own document does not contain.

### Relationships and type

`assertionMethod` by default, because signing a credential or a status token is
asserting something and nothing authenticates *as* an issuer. Pass a `list` of
`VerificationRelationshipEnum` cases to place the keys in others as well. A key
whose own `use` rules out a relationship — `enc` under a signature relationship —
is refused rather than published there.

The verification method type defaults to `JsonWebKey2020`. The DID Specification
Registries deprecate it in favour of `JsonWebKey`, which is also accepted here,
but it remains the more widely understood of the two. The `@context` follows from
whichever is chosen, so the two cannot disagree.

### What it refuses

An identifier that is not a bare `did:web`, **or is one this library could not
resolve** — an IP-literal or single-label host, a percent-encoded segment, a
relative path segment. Those are the resolver's own rules, asked for rather than
restated, because a document published under an identifier that does not
transform to a URL sits somewhere nothing can look it up.

Then: an empty key bag; an empty relationship list, since a key belonging to no
relationship can be used for nothing; a verification method type that carries
`publicKeyMultibase` rather than a JWK; an empty key identifier; a key whose
`use` names neither purpose; and any JWK member that is not public.

## What is deliberately not supported

- **Relative DID URLs**, anywhere. The did:web specification requires every DID
  URL inside a document to be absolute, including inside embedded key material,
  precisely to prevent key confusion, and DID Core's general permission for
  relative references does not override it.
- **A verification method id outside the document subject.** Otherwise
  `did:web:evil` could publish a method whose id sits under `did:web:victim`,
  and that id would travel onward as the key identifier.
- **Relationship references into another document.** DID Core permits them;
  resolving one needs a second fetch, so they are refused.
- **The X25519 key agreement method that `did:key` implies** for an Ed25519 key.
  Deriving it is out of scope, so a `keyAgreement` lookup against such a document
  finds nothing rather than the wrong key.
- **Percent-encoding in a did:web identifier**, other than the `%3A` port
  separator.
- **IP-literal and single-label did:web hosts.**
- **Compressed elliptic curve points**, for P-256, P-384 and P-521. Only the
  uncompressed encoding is decoded, and the `did:key` method specifies the
  compressed one for these curves, so a NIST-curve `did:key` produced by another
  implementation is likely to be refused. Ed25519, X25519 and secp256k1 are
  unaffected, as is a key carried as a raw JSON JWK under multicodec `0xeb51`.
- **`@context` in a document being read.** It is neither parsed nor validated,
  since it does not affect key selection. A document this library *builds*
  declares one; a document it parsed therefore serialises without any, rather
  than with one it never saw.
- **Round-tripping a parsed document.** Serialisation exists for the documents
  this library builds. A parsed one keeps only what resolution needs, so members
  that decide nothing about which key resolves — `@context`, `service`,
  `alsoKnownAs` — cannot come back out of it, and a verification method that
  arrived as `publicKeyMultibase` refuses to serialise at all rather than being
  emitted under a type that disagrees with its key material.
