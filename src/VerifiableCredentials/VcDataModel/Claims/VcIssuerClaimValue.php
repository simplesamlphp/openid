<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use SimpleSAML\OpenID\Claims\ClaimInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

class VcIssuerClaimValue implements ClaimInterface
{
    /** @var non-empty-array<mixed> */
    protected array $data;


    /**
     * @param non-empty-string $id
     * @param mixed[] $otherClaims
     */
    public function __construct(
        protected string $id,
        array $otherClaims = [],
    ) {
        $this->data = array_merge(
            $otherClaims,
            [ClaimsEnum::Id->value => $this->id],
        );
    }


    /**
     * @return non-empty-string
     */
    public function getId(): string
    {
        return $this->id;
    }


    public function getKey(int|string $key): mixed
    {
        return $this->data[$key] ?? null;
    }


    public function getName(): string
    {
        return ClaimsEnum::Issuer->value;
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
