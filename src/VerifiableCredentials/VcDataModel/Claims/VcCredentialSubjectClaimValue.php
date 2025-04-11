<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use JsonSerializable;

class VcCredentialSubjectClaimValue implements JsonSerializable
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
        return $this->data;
    }
}
