# Outbound destination policy

Most of what this library fetches is named by somebody other than the operator
of the deployment doing the fetching. An entity statement says which endpoint to
fetch next. A registering client supplies its own `jwks_uri`, `signed_jwks_uri`
and `request_uri`. A credential issuer names the Status List Token URI. Left
unrestricted, that turns the deployment into a way of reaching its own network
from the outside: a URL pointing at `127.0.0.1`, `10.0.0.5` or
`169.254.169.254` is fetched like any other, and so is a public URL that
redirects to one of them.

The **destination policy** decides where an outbound request may go. It refuses
non-public destinations by default, so nothing has to be switched on for a
deployment to be covered.

> Application-layer SSRF defence in PHP is leaky by nature. An egress firewall
> or a forward proxy that cannot reach internal networks remains the stronger
> control. This raises the bar; it does not make fetching arbitrary URLs safe.

## What is refused

Before any name is looked up:

- a scheme other than the allowed ones (`https` alone, by default),
- a URI carrying credentials (`https://user:password@host/…`),
- a host that is already a non-public address literal, in either family and in
  any spelling — `127.0.0.1`, `[::1]`, `[::ffff:127.0.0.1]`, `[64:ff9b::a00:5]`.

Then the host is resolved, and the request is refused if **any** address it
answers with is not one an outbound request may legitimately be routed to:
loopback, private, link-local (the `169.254.169.254` cloud metadata address
among them), shared address space, multicast, unspecified, benchmarking and
documentation ranges. For IPv6 the question is turned around — only global
unicast (`2000::/3`) counts as reachable, minus the carve-outs inside it
(Teredo, 6to4, ORCHID, documentation) — so unique local, link local and the
large unallocated remainder need no enumerating. Addresses that carry an IPv4
address (v4-mapped, and the well-known NAT64 prefix) are judged as the IPv4
address they carry.

A host that resolves to nothing is refused as well, rather than passing for a
destination with no restrictions on it.

A request is also refused outright if it carries a cURL option that would decide
the destination underneath the policy: `CURLOPT_URL` replaces the address that was
validated, and `CURLOPT_FOLLOWLOCATION` hands redirect following to libcurl, below
the middleware, so the hops it takes would never be checked. Guzzle 8 rejects both
on its own; on Guzzle 7 the policy rejects them.

**Every redirect hop is checked in the same way.** A first hop that passes and
then redirects inward is the whole attack, so the check runs per hop rather than
once for the original request.

## Pinning, and the rebinding problem it closes

Checking a hostname and then handing the hostname to the HTTP client resolves it
twice: once to decide whether it is allowed, and once to actually connect.
Whoever controls DNS for that name controls both answers, and they need not be
the same. The first returns a public address and passes the check; the second,
moments later, returns `127.0.0.1`, and that is the one the connection uses.

So the addresses that passed the check are **pinned**: the client is told to
connect to those addresses instead of resolving again. With cURL that is
`CURLOPT_RESOLVE`, which only changes where the connection goes — the request is
still made to the original hostname, so the TLS handshake still validates the
certificate against the name.

Pinning needs the cURL handler, and the library only claims it where that can be
established — by asking Guzzle which handler it would choose, rather than by
assuming that a loaded extension means a cURL transport. It is reported as
unavailable when Guzzle would not use cURL (no extension, its functions disabled,
libcurl without the SSL support Guzzle requires), when the transport was supplied
from outside the library (a handler passed through the HTTP client options, since
nothing can be established about it), when the request is a streaming one (Guzzle
hands those to the stream handler), when a proxy is configured — through the
`proxy` option or the environment — because a proxy resolves the destination
itself, and when a cURL option that decides the connection over the resolver
cache is in play (`CURLOPT_CONNECT_TO`, `CURLOPT_PROXY`, `CURLOPT_PRE_PROXY`,
`CURLOPT_UNIX_SOCKET_PATH`, `CURLOPT_PORT`). The mode then decides what happens:

| Mode                  | Behaviour when the address cannot be pinned                                                                        |
|-----------------------|--------------------------------------------------------------------------------------------------------------------|
| `Preferred` (default) | The destination is still validated, the request goes out, and the weaker guarantee is reported to the logger once. |
| `Required`            | The request is refused.                                                                                            |
| `Disabled`            | Nothing is pinned and nothing is reported.                                                                         |

`Preferred` is the default so that a deployment without the cURL extension keeps
working rather than failing every fetch; validation still refuses every
destination that resolves inward at the time of the check. Set `Required` where
the cURL handler can be guaranteed — note that it refuses every request behind a
proxy or through a handler the deployment supplied, since a pin cannot be shown
to apply there.

## Configuring it

Nothing needs to be passed for the default policy to apply. To widen it, build a
policy and hand it to the tool classes:

```php
use SimpleSAML\OpenID\Codebooks\AddressPinningModeEnum;
use SimpleSAML\OpenID\Federation;
use SimpleSAML\OpenID\Jwks;
use SimpleSAML\OpenID\Network\DestinationPolicy;

$destinationPolicy = new DestinationPolicy(
    allowedSchemes: ['https'],
    allowedHosts: ['rp.internal.example'],
    allowedCidrs: ['10.1.2.3/32'],
    addressPinningMode: AddressPinningModeEnum::Preferred,
    logger: $logger,
);

$federationTools = new Federation(destinationPolicy: $destinationPolicy);
$jwksTools = new Jwks(destinationPolicy: $destinationPolicy);
```

The same parameter exists on `Federation`, `Jwks`, `RequestObject` and
`TokenStatusList`, and one policy instance can be shared between them.

**`allowedCidrs`** permits addresses in the given ranges alongside the public
ones. Use the narrowest range that covers the destination — `10.1.2.3/32`
rather than `10.0.0.0/8` — so that permitting one internal endpoint does not
permit the whole private network. A range written for IPv4 also covers the
v4-mapped and NAT64 spellings of the addresses in it. An unusable range is
refused when the policy is built, rather than quietly never matching.

**`allowedHosts`** permits a host whatever it resolves to, and skips the address
check and the pinning for it. It is for an internal destination that a range
cannot describe: a name resolved outside DNS (`/etc/hosts`, which the resolver
here does not see), or one whose address is not fixed. Note what it costs:
allowing a host means trusting whoever controls that name with where the request
goes. Keep the list to destinations the deployment operates itself. Comparison
ignores case, brackets and a trailing dot.

**`allowedSchemes`** defaults to `['https']`, since every destination this
library fetches is https by specification and redirects are already restricted
to https. Add `'http'` only for a deployment that knowingly fetches over plain
http; the address rules apply either way.

## Clients this library does not build

The policy is applied as Guzzle middleware, pushed below the redirect middleware
so that it runs for every hop. That happens automatically for the client the
library builds, including when no HTTP options are configured at all.

A **pre-built client** passed to a tool class is left alone — its handler stack
belongs to whoever built it, and every other client sharing that stack would be
affected too. Such a client is unguarded, and this is reported to the logger.
Guard it by pushing the middleware yourself:

```php
$handlerStack = HandlerStack::create();
$handlerStack->push($destinationPolicy->middleware());

$client = new Client(['handler' => $handlerStack]);
```

A middleware obtained this way assumes nothing about the transport, and so reports
every request through it as unpinnable — the policy cannot tell what the stack was
built on, and claiming a pin a handler ignores would be a guarantee that was never
made. Where the stack is known to be Guzzle's default cURL one, say so:

```php
$handlerStack->push($destinationPolicy->middleware(new AddressPinner(handlerIsCurl: true)));
```

A handler supplied through the HTTP client options (`['handler' => …]`) *is*
guarded: a handler stack has the middleware pushed onto it, and a bare callable
handler is wrapped. Its destinations are validated like any other, but pinning is
not claimed for it, since nothing can be established about a transport that came
from outside. One handler stack shared between policies keeps a guard for each,
so a destination has to satisfy all of them.

## Checking a destination without fetching it

The policy works without a request in hand, so a destination can be refused when
it is registered rather than only when it is first fetched:

```php
if (!$destinationPolicy->isUriAllowed($clientMetadata['jwks_uri'])) {
    // Refuse the registration.
}

// Or, to get the reason:
$destinationPolicy->assertUriIsAllowed($clientMetadata['jwks_uri']);

// A single address, where one is already in hand:
$destinationPolicy->isAddressAllowed('10.0.0.5');
```

## Handling a refusal

A refused destination raises
`\SimpleSAML\OpenID\Exceptions\DestinationPolicyException`, which is distinct
from a transport failure so that it can be reported as what it is — a
configuration or registration problem rather than an unreachable endpoint. The
fetchers that otherwise turn every failure into a `FetchException` let it
through unwrapped, so it arrives as itself rather than as something to dig out
of an exception chain.

```php
try {
    $entityStatement = $federationTools->entityStatementFetcher()
        ->fromCacheOrWellKnownEndpoint($entityId);
} catch (DestinationPolicyException $e) {
    // The destination itself was refused.
} catch (FetchException $e) {
    // The fetch failed.
}
```

It extends `HttpException`, which is what `HttpClientDecorator::request()` has
always raised, so code catching that keeps working unchanged.

One exception to that: `JwksFetcher::fromJwksUri()` and
`fromSignedJwksUri()` already report every fetch failure to the log and return
`null` rather than raising, and a refusal is reported the same way. To turn a
`jwks_uri` pointing inward into a protocol error, check it when the client
registers, as above.

## Notes on upgrading

- Non-public destinations are refused by default. A deployment that fetches from
  an internal address needs `allowedHosts` or `allowedCidrs` for it.
- Destinations over plain `http` are refused by default. Add `'http'` to
  `allowedSchemes` where that is wanted.
- Each destination costs a DNS lookup per request, and one per redirect hop. The
  lookup happens before the request goes out, so the client's own timeouts do
  not bound it; a slow resolver shows up as slow fetches.
- A pre-built client is not guarded; push the middleware onto it, or let the
  library build the client.
- Raw `curl` request options remain an escape hatch below the policy. The ones
  that matter for where a request goes are recognised and named above, but the
  list is an enumeration rather than a guarantee: a deployment setting raw cURL
  options is taking responsibility for what they do.
