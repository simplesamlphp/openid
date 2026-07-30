# Token Status List (TSL) Tools

Tools for the
[OAuth Status List](https://datatracker.ietf.org/doc/html/draft-ietf-oauth-status-list)
specification: publishing the status of many issued tokens in one signed
document, and reading a single token's status back out of it.

The specification calls the token whose status is being tracked a **Referenced
Token**. It carries a `status` claim naming a URI and an index. Fetching the
**Status List Token** from that URI and reading the bits at that index tells a
Relying Party whether the Referenced Token is still valid, has been revoked, or
is suspended. Any JOSE-based token can be a Referenced Token — a Verifiable
Credential, an SD-JWT VC, an access token.

Only the JOSE side of the specification is implemented. CWT/COSE
representations and Status List Aggregation are not.

To use it, create an instance of the `\SimpleSAML\OpenID\TokenStatusList` class.

```php
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\SupportedAlgorithms;
use SimpleSAML\OpenID\TokenStatusList;

$tokenStatusListTools = new TokenStatusList(
    new SupportedAlgorithms(
        new SignatureAlgorithmBag(
            SignatureAlgorithmEnum::ES256,
        ),
    ),
);
```

## Issuer side

### Referencing a Status List from an issued token

Allocate an index for the token you are issuing, then merge the `status` claim
into its payload. The URI and the index are yours to assign and to store; this
library does not keep track of which index belongs to which token.

```php
$statusClaim = $tokenStatusListTools->statusReferenceFactory()->buildClaim(
    'https://example.com/statuslists/1',
    42,
);

$payload = [...] + $statusClaim->jsonSerialize();
// [
//     'status' => [
//         'status_list' => [
//             'idx' => 42,
//             'uri' => 'https://example.com/statuslists/1',
//         ],
//     ],
// ]
```

The `uri` is used verbatim. A Relying Party rejects a Status List Token whose
`sub` claim is not equal to it, so store the exact string you issued and use it
for both the claim and the token's subject. Changing a base URL later does not
change the credentials already issued: they keep pointing at the old origin,
which has to keep serving those lists.

### Building a Status List

A Status List is a byte array of `capacity` entries, `bits` wide each.

```php
use SimpleSAML\OpenID\Codebooks\StatusTypeEnum;

$statusList = $tokenStatusListTools->statusListFactory()->fromEntries(
    [
        42 => StatusTypeEnum::Invalid,
        43 => StatusTypeEnum::Suspended,
    ],
    2,        // bits per entry: 1, 2, 4 or 8
    131072,   // capacity
);
```

`fromEntries()` keys on the index each entry carries, never on its position in
the iterable, so it can be fed straight from a database result set with gaps in
it. Every index not supplied reads as `Valid`. Use it rather than repeated
`withStatus()` calls when materialising a whole list, since `withStatus()` is
immutable and copies the byte array each time.

Choose `bits` for the widest status the list will ever need to carry:
`StatusTypeEnum::Suspended` is `0x02` and does not fit into one bit, and a list
already in use cannot be widened. `StatusTypeEnum::requiredBits()` states the
minimum for a given status.

Other entry points: `forCapacity()` for an all-`Valid` list, `fromEncoded()` and
`fromClaimData()` for reading one back.

### Signing a Status List Token

```php
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;

$statusListToken = $tokenStatusListTools->statusListTokenFactory()->forStatusList(
    $statusList,
    'https://example.com/statuslists/1',   // becomes the `sub` claim
    $signingKey,                           // \SimpleSAML\OpenID\Jwk\JwkDecorator
    SignatureAlgorithmEnum::ES256,
    new DateTimeImmutable(),                       // iat
    (new DateTimeImmutable())->add(new DateInterval('P7D')),  // exp
    new DateInterval('PT12H'),                     // ttl
    null,                                          // optional iss
    [],                                            // additional payload claims
    ['kid' => 'did:jwk:...#0'],                    // additional header claims
);

$serialized = $statusListToken->getToken();
```

Serve that string with a `Content-Type` of `application/statuslist+jwt`.

The specification mandates no method for binding a Status List Token to a key,
so `kid` and `iss` are left to the caller. Whichever profile you choose, a
Relying Party has to be able to resolve the key from it — and the private key
has to be retained for as long as any list it signed is still served.

`ttl` is how long a consumer may cache a copy, and it is therefore also how long
a revocation may go unnoticed. It is a product decision, not a technical one.

## Relying Party side

Validate the Referenced Token on its own terms **first**. The specification
requires that a token which fails its own validation never has its status
resolved at all, and that a token which is expired through its own claims stays
expired regardless of what the Status List says.

```php
// Extract the reference from the validated Referenced Token's payload.
$statusReference = $tokenStatusListTools->statusReferenceFactory()
    ->fromReferencedTokenPayload($referencedTokenPayload);

if ($statusReference === null) {
    // No `status` claim at all. What that means is your policy to decide.
}

$statusResult = $tokenStatusListTools->statusResolver()->resolve(
    $statusReference,
    $jwks,      // key set the Status List Token is verified against
    16384,      // maximum bytes you are willing to decompress the list to
);

if (!$statusResult->isValid()) {
    // Revoked, suspended, or an application specific status.
}
```

`resolve()` fetches the Status List Token from the reference's URI (through the
cache, when one is configured), verifies its signature against the key set you
supply, checks that its subject equals that URI, decodes the list and reads the
index. Any failure throws: no statement about the Referenced Token can be made,
and it has to be rejected.

Use `resolveWithToken()` instead when you already hold the token — an offline
flow, or a token obtained by some other means.

`getStatusType()` returns `null` for values the specification reserves as
application specific (`0x03`, `0x0C`–`0x0F`) or has not registered; use
`getStatus()` for the raw number in that case. `isFreshAt()` answers whether the
result may still be relied upon, per the token's `ttl`.

### Bounding decompression

Every decoding entry point requires a maximum decompressed size from the caller.
The specification sets no maximum list size, so what counts as an acceptable
list is a deployment decision — and an unbounded `gzuncompress()` on a document
fetched from the network is a decompression bomb waiting to happen. A few
hundred bytes of input can otherwise expand to megabytes.

Size the bound from the largest list you expect to accept: `capacity / 8` bytes
at `bits` of 1, up to one byte per entry at `bits` of 8. A compressed input bound
is derived from it, and can be given explicitly as the next argument.

## Caching

`StatusListTokenFetcher` caches a fetched token for the shortest of the
configured maximum cache duration, the time left until the token expires, and
the token's own `ttl`. Pass a PSR-16 cache to the `TokenStatusList` constructor
to enable it; without one, every resolution goes to the network.

`resolve()` writes to the cache only once a token has verified and been bound to
its URI, so one bad response cannot be served back for the whole cache period.
For the same reason it treats a cached token that no longer resolves as a miss:
it discards the entry, fetches the current representation once, and caches that
instead. Otherwise a Status Provider rotating its signing key would make every
Referenced Token pointing at that URI unresolvable until the entry expired —
long after the provider itself was healthy again. A second failure is the
answer, not another fetch.
