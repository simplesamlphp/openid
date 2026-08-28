<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Did;

use SimpleSAML\OpenID\Codebooks\VerificationRelationshipEnum;

/**
 * The outcome of resolving a DID URL to a single verification method.
 *
 * Callers get the canonical verification method id alongside the key, not just the key. A caller that only
 * received key material would have to rebuild that id by string surgery, which is exactly the class of thing
 * this resolution exists to remove.
 *
 * @see \SimpleSAML\Test\OpenID\Did\ResolvedVerificationMethodTest
 */
class ResolvedVerificationMethod
{
    /**
     * @param string $did The bare DID of the document subject, carrying no fragment.
     * @param \SimpleSAML\OpenID\Did\DidUrl $id The canonical, absolute verification method id.
     * @param array<array-key, mixed> $publicJwk
     * @param \SimpleSAML\OpenID\Codebooks\VerificationRelationshipEnum|null $relationship The relationship the
     * method was required to appear in, or null if resolution was not constrained to one.
     */
    public function __construct(
        protected readonly string $did,
        protected readonly DidUrl $id,
        protected readonly array $publicJwk,
        protected readonly ?VerificationRelationshipEnum $relationship = null,
    ) {
    }


    public function getDid(): string
    {
        return $this->did;
    }


    public function getId(): DidUrl
    {
        return $this->id;
    }


    /**
     * @return array<array-key, mixed>
     */
    public function getPublicJwk(): array
    {
        return $this->publicJwk;
    }


    public function getRelationship(): ?VerificationRelationshipEnum
    {
        return $this->relationship;
    }
}
