<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Did;

use JsonSerializable;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\VerificationRelationshipEnum;
use SimpleSAML\OpenID\Exceptions\DidException;

/**
 * A DID document, reduced to what verification method resolution needs.
 *
 * Relationship membership is materialised at construction time: whether a relationship referenced a verification
 * method by id or embedded one inline, the relationship maps hold the resolved methods. A method that is present
 * under `verificationMethod` but absent from the relationship being asked for is a rejection, never a fallback.
 *
 * @see https://www.w3.org/TR/did-core/#did-document-properties
 * @see \SimpleSAML\Test\OpenID\Did\DidDocumentTest
 */
class DidDocument implements JsonSerializable
{
    /**
     * @param string $id The document subject, a bare DID.
     * @param array<string, \SimpleSAML\OpenID\Did\VerificationMethod> $verificationMethods Keyed by method id.
     * @param array<string, array<string, \SimpleSAML\OpenID\Did\VerificationMethod>> $relationshipMethods Keyed
     * by relationship value, then by method id.
     * @param list<string> $contexts The JSON-LD contexts this document declares. A parsed document has none:
     * `@context` is not read, since it decides nothing about which key resolves, so a document that arrived
     * over the network carries no claim about its contexts for this to repeat.
     */
    public function __construct(
        protected readonly string $id,
        protected readonly array $verificationMethods,
        protected readonly array $relationshipMethods = [],
        protected readonly array $contexts = [],
    ) {
    }


    public function getId(): string
    {
        return $this->id;
    }


    /**
     * @return list<string>
     */
    public function getContexts(): array
    {
        return $this->contexts;
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


    /**
     * The document as it is published.
     *
     * This exists for the documents this library builds. A parsed one serialises to what resolution kept of
     * it, which is less than arrived: members that decide nothing about which key resolves - `@context`,
     * `service`, `alsoKnownAs` - were never read, so they can not come back out.
     *
     * @return array<string, mixed>
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function jsonSerialize(): array
    {
        $document = [];

        if ($this->contexts !== []) {
            $document[ClaimsEnum::AtContext->value] = $this->contexts;
        }

        $document[ClaimsEnum::Id->value] = $this->id;

        $verificationMethods = [];

        foreach ($this->verificationMethods as $verificationMethod) {
            $verificationMethods[] = $verificationMethod->jsonSerialize();
        }

        if ($verificationMethods !== []) {
            $document[ClaimsEnum::VerificationMethod->value] = $verificationMethods;
        }

        foreach (VerificationRelationshipEnum::cases() as $relationship) {
            $methods = $this->relationshipMethods[$relationship->value] ?? [];

            if ($methods === []) {
                continue;
            }

            $document[$relationship->value] = $this->serializeRelationship($methods);
        }

        return $document;
    }


    /**
     * A method the document also defines at the top level is emitted as a reference to it, which is the
     * compact shape and the one every document this library builds uses. One that is not - a method a parsed
     * document embedded inline, which DID Core scopes to the relationship it appears under - is emitted
     * inline again, since a reference to it would name a method the document does not define.
     *
     * @param array<string, \SimpleSAML\OpenID\Did\VerificationMethod> $methods
     * @return list<string|array<string, mixed>>
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function serializeRelationship(array $methods): array
    {
        $entries = [];

        foreach ($methods as $id => $method) {
            $entries[] = array_key_exists($id, $this->verificationMethods) ?
            $id :
            $method->jsonSerialize();
        }

        return $entries;
    }
}
