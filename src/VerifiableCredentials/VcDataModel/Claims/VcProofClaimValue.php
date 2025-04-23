<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use SimpleSAML\OpenID\Claims\ClaimInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

class VcProofClaimValue implements ClaimInterface
{
    /** @var non-empty-array<mixed> */
    protected array $data;

    /**
     * @param non-empty-string $type
     * @param mixed[] $otherClaims
     */
    public function __construct(
        protected string $type,
        array $otherClaims = [],
        protected readonly string $name = ClaimsEnum::Proof->value,
    ) {
        $this->data = array_merge(
            $otherClaims,
            [ClaimsEnum::Type->value => $this->type],
        );
    }

    /**
     * @return non-empty-string
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function getKey(int|string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return non-empty-array<mixed>
     */
    public function getValue(): array
    {
        return $this->data;
    }

    /**
     * @return non-empty-array<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->getValue();
    }
}
