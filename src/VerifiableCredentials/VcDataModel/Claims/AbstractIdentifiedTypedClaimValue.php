<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use SimpleSAML\OpenID\Claims\ClaimInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

abstract class AbstractIdentifiedTypedClaimValue implements ClaimInterface
{
    /** @var non-empty-array<mixed> */
    protected readonly array $data;


    /**
     * @param non-empty-string $id,
     * @param mixed[] $otherClaims
     */
    public function __construct(
        protected readonly string $id,
        protected readonly TypeClaimValue $typeClaimValue,
        array $otherClaims = [],
    ) {
        $this->data = array_merge(
            $otherClaims,
            [ClaimsEnum::Id->value => $this->id],
            [ClaimsEnum::Type->value => $this->typeClaimValue->jsonSerialize()],
        );
    }


    /**
     * @return non-empty-string
     */
    public function getId(): string
    {
        return $this->id;
    }


    public function getType(): TypeClaimValue
    {
        return $this->typeClaimValue;
    }


    public function getKey(int|string $key): mixed
    {
        return $this->data[$key] ?? null;
    }


    abstract public function getName(): string;


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
