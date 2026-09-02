<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Did;

use JsonSerializable;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\VerificationMethodTypeEnum;
use SimpleSAML\OpenID\Exceptions\DidException;

/**
 * A verification method as it appears in a DID document.
 *
 * Whatever encoding the document used for the key material, it is normalised to a JWK here, so that callers do
 * not have to care whether it arrived as `publicKeyJwk` or `publicKeyMultibase`.
 *
 * @see https://www.w3.org/TR/did-core/#verification-methods
 * @see \SimpleSAML\Test\OpenID\Did\VerificationMethodTest
 */
class VerificationMethod implements JsonSerializable
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


    /**
     * Only the JWK the key material was normalised to is retained, never the encoding it arrived in, so a
     * type declaring `publicKeyMultibase` has nothing to serialise. Emitting the JWK under it anyway would
     * publish a method whose declared type and key material property disagree - a document this library
     * would itself refuse to parse - so it is refused here instead.
     *
     * @return array<string, mixed>
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function jsonSerialize(): array
    {
        if (!in_array($this->type, VerificationMethodTypeEnum::withPublicKeyJwk(), true)) {
            throw new DidException(
                sprintf(
                    'Verification method type %s carries its key material as publicKeyMultibase, which is not ' .
                    'retained, so this method can not be serialised.',
                    $this->type->value,
                ),
            );
        }

        return [
            ClaimsEnum::Id->value => $this->id->getValue(),
            ClaimsEnum::Type->value => $this->type->value,
            ClaimsEnum::Controller->value => $this->controller,
            ClaimsEnum::PublicKeyJwk->value => $this->publicJwk,
        ];
    }
}
