<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Did;

use SimpleSAML\OpenID\Codebooks\VerificationMethodTypeEnum;

/**
 * A verification method as it appears in a DID document.
 *
 * Whatever encoding the document used for the key material, it is normalised to a JWK here, so that callers do
 * not have to care whether it arrived as `publicKeyJwk` or `publicKeyMultibase`.
 *
 * @see https://www.w3.org/TR/did-core/#verification-methods
 * @see \SimpleSAML\Test\OpenID\Did\VerificationMethodTest
 */
class VerificationMethod
{
    /**
     * @param array<array-key, mixed> $publicJwk
     */
    public function __construct(
        protected readonly DidUrl $id,
        protected readonly VerificationMethodTypeEnum $type,
        protected readonly string $controller,
        protected readonly array $publicJwk,
    ) {
    }


    public function getId(): DidUrl
    {
        return $this->id;
    }


    public function getType(): VerificationMethodTypeEnum
    {
        return $this->type;
    }


    public function getController(): string
    {
        return $this->controller;
    }


    /**
     * @return array<array-key, mixed>
     */
    public function getPublicJwk(): array
    {
        return $this->publicJwk;
    }
}
