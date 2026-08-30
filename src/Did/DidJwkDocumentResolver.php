<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Did;

use SimpleSAML\OpenID\Did\Factories\DidDocumentFactory;

/**
 * Resolves a did:jwk identifier to the document it describes.
 *
 * Nothing is retrieved: the key is the identifier, so the document is constructed from it. That is also why
 * the factory refuses to parse supplied data for this method - a document handed to us could otherwise put
 * a different key under an identifier that is supposed to be the key.
 *
 * Named apart from {@see DidJwkResolver}, which extracts the key material and is a dependency of the factory
 * used here. Having that class implement this interface would close a dependency cycle around the factory.
 *
 * @see https://github.com/quartzjer/did-jwk/blob/main/spec.md
 * @see \SimpleSAML\Test\OpenID\Did\DidJwkDocumentResolverTest
 */
class DidJwkDocumentResolver extends AbstractDidResolver
{
    /** The DID method this resolver handles. */
    public const METHOD = 'jwk';


    public function __construct(
        protected readonly DidDocumentFactory $didDocumentFactory,
    ) {
    }


    public function methodName(): string
    {
        return self::METHOD;
    }


    /**
     * @param ?float $deadlineTimestamp Accepted for the interface and ignored, since nothing is fetched.
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function resolveDocument(string $did, ?float $deadlineTimestamp = null): DidDocument
    {
        return $this->didDocumentFactory->forDidJwk($this->requireBareDid($did));
    }
}
