<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Did;

use SimpleSAML\OpenID\Did\Factories\DidDocumentFactory;

/**
 * Resolves a did:key identifier to the document it describes.
 *
 * Nothing is retrieved: the multibase encoded key is the identifier, so the document is constructed from it.
 *
 * Note that for an Ed25519 signing key the method also implies a derived X25519 key agreement method.
 * Deriving it is out of scope, so a key agreement lookup against such a document finds nothing rather than
 * the wrong key.
 *
 * Named apart from {@see DidKeyJwkResolver}, which extracts the key material. The factory used here shares
 * that class's {@see MultibaseKeyDecoder}, so the multicodec table is read from one place.
 *
 * @see https://w3c-ccg.github.io/did-method-key/
 * @see \SimpleSAML\Test\OpenID\Did\DidKeyDocumentResolverTest
 */
class DidKeyDocumentResolver extends AbstractDidResolver
{
    /** The DID method this resolver handles. */
    public const METHOD = 'key';


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
        return $this->didDocumentFactory->forDidKey($this->requireBareDid($did));
    }
}
