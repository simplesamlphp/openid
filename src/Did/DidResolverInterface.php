<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Did;

/**
 * A resolver for one DID method.
 *
 * Implementations fall into two kinds, and the difference is worth keeping in mind when reading one. A
 * self describing method - did:jwk, did:key - derives its document from the identifier, so resolution is a
 * local construction that cannot fail for any reason outside the identifier itself. A retrievable method -
 * did:web - fetches, which means the identifier decides where this deployment sends a request.
 *
 * @see \SimpleSAML\OpenID\Did\AbstractDidResolver
 */
interface DidResolverInterface
{
    /**
     * The bare DID method name this resolver handles, as it appears between the two colons of an identifier:
     * "web" for did:web.
     *
     * Declared here rather than left to whoever assembles a registry, so that the key a resolver is
     * registered under cannot disagree with the identifiers it actually accepts.
     */
    public function methodName(): string;


    /**
     * Whether this resolver handles the given DID's method. A value that is not a DID URL at all is simply
     * not supported, rather than an error: asking is how a caller finds out.
     */
    public function supports(string $did): bool;


    /**
     * Resolve a bare DID to its document.
     *
     * @param string $did A bare DID of this resolver's method. Resolution is of the document, so a DID URL
     *        naming something inside it is not what is being asked for here.
     * @param ?float $deadlineTimestamp The point in time by which the whole operation this resolution
     *        belongs to has to be finished, as a unix timestamp with fractions. A per-request timeout can
     *        not bound an operation made of several resolutions, so a caller holding such a budget passes
     *        it. A resolver that performs no I/O ignores it: refusing a local construction because a budget
     *        for network work elapsed would turn a free operation into a failure.
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function resolveDocument(string $did, ?float $deadlineTimestamp = null): DidDocument;
}
