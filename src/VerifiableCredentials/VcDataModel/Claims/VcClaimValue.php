<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use JsonSerializable;

class VcClaimValue implements JsonSerializable
{
    /**
     * @param null|non-empty-string $id
     * @param non-empty-array<non-empty-string> $type
     */
    public function __construct(
        protected readonly VcAtContextClaimValue $atContextClaimValue,
        /** @var null|non-empty-string */
        protected readonly null|string $id,
        /** @var non-empty-array<non-empty-string> */
        protected readonly array $type,
        protected readonly VcCredentialSubjectClaimBag $vcCredentialSubjectClaimBag,
        protected readonly VcIssuerClaimValue $issuerClaimValue,
    ) {
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        // TODO: Implement jsonSerialize() method.
        return [];
    }

    public function getAtContext(): VcAtContextClaimValue
    {
        return $this->atContextClaimValue;
    }

    /**
     * @return non-empty-string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return non-empty-array<non-empty-string>
     */
    public function getType(): array
    {
        return $this->type;
    }

    public function getCredentialSubject(): VcCredentialSubjectClaimBag
    {
        return $this->vcCredentialSubjectClaimBag;
    }

    public function getIssuer(): VcIssuerClaimValue
    {
        return $this->issuerClaimValue;
    }
}
