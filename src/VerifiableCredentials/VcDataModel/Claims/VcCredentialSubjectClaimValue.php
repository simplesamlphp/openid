<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use SimpleSAML\OpenID\Claims\ClaimInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

class VcCredentialSubjectClaimValue implements ClaimInterface
{
    /**
     * @param non-empty-array<mixed> $data
     */
    public function __construct(protected readonly array $data)
    {
    }


    public function get(int|string $key): mixed
    {
        return $this->data[$key] ?? null;
    }


    /**
     * @return non-empty-array<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->getValue();
    }


    public function getName(): string
    {
        return ClaimsEnum::Credential_Subject->value;
    }


    /**
     * @return non-empty-array<mixed>
     */
    public function getValue(): array
    {
        return $this->data;
    }
}
