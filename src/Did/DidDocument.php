<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Did;

use SimpleSAML\OpenID\Codebooks\VerificationRelationshipEnum;
use SimpleSAML\OpenID\Exceptions\DidException;

/**
 * A parsed DID document, reduced to what verification method resolution needs.
 *
 * Relationship membership is materialised at construction time: whether a relationship referenced a verification
 * method by id or embedded one inline, the relationship maps hold the resolved methods. A method that is present
 * under `verificationMethod` but absent from the relationship being asked for is a rejection, never a fallback.
 *
 * @see https://www.w3.org/TR/did-core/#did-document-properties
 * @see \SimpleSAML\Test\OpenID\Did\DidDocumentTest
 */
class DidDocument
{
    /**
     * @param string $id The document subject, a bare DID.
     * @param array<string, \SimpleSAML\OpenID\Did\VerificationMethod> $verificationMethods Keyed by method id.
     * @param array<string, array<string, \SimpleSAML\OpenID\Did\VerificationMethod>> $relationshipMethods Keyed
     * by relationship value, then by method id.
     */
    public function __construct(
        protected readonly string $id,
        protected readonly array $verificationMethods,
        protected readonly array $relationshipMethods = [],
    ) {
    }


    public function getId(): string
    {
        return $this->id;
    }


    /**
     * @return array<string, \SimpleSAML\OpenID\Did\VerificationMethod>
     */
    public function getVerificationMethods(): array
    {
        return $this->verificationMethods;
    }


    /**
     * @return array<string, \SimpleSAML\OpenID\Did\VerificationMethod>
     */
    public function getVerificationMethodsFor(VerificationRelationshipEnum $relationship): array
    {
        return $this->relationshipMethods[$relationship->value] ?? [];
    }


    /**
     * Resolve a verification method id against this document.
     *
     * When a relationship is given, the method must appear in it. When none is given, only the document's own
     * `verificationMethod` entries are searched, since a method embedded inline under a relationship is scoped
     * to that relationship and is not a document-wide method.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function resolveVerificationMethod(
        DidUrl $id,
        ?VerificationRelationshipEnum $relationship = null,
    ): ResolvedVerificationMethod {
        $candidates = $relationship instanceof VerificationRelationshipEnum ?
        $this->getVerificationMethodsFor($relationship) :
        $this->verificationMethods;

        $verificationMethod = $candidates[$id->getValue()] ?? null;

        if (!$verificationMethod instanceof VerificationMethod) {
            throw new DidException(
                $relationship instanceof VerificationRelationshipEnum ?
                sprintf(
                    'DID document does not list the requested verification method under the %s relationship.',
                    $relationship->value,
                ) :
                'DID document does not contain the requested verification method.',
            );
        }

        return new ResolvedVerificationMethod(
            $this->id,
            $verificationMethod->getId(),
            $verificationMethod->getPublicJwk(),
            $relationship,
        );
    }
}
