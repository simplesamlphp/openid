<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Claims;

use JsonSerializable;

class TrustMarksClaimBag implements JsonSerializable
{
    /**
     * @var \SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimValue[]
     */
    protected array $trustMarksClaimValues = [];

    public function __construct(TrustMarksClaimValue ...$trustMarksClaimValue)
    {
        $this->add(...$trustMarksClaimValue);
    }

    public function add(TrustMarksClaimValue ...$trustMarksClaimValue): void
    {
        $this->trustMarksClaimValues = array_merge($this->trustMarksClaimValues, $trustMarksClaimValue);
    }

    /**
     * @return \SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimValue[]
     */
    public function getAll(): array
    {
        return $this->trustMarksClaimValues;
    }

    /**
     * @param non-empty-string $trustMarkId
     * @return \SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimValue[]
     */
    public function getAllFor(string $trustMarkId): array
    {
        return array_values(array_filter(
            $this->trustMarksClaimValues,
            fn(TrustMarksClaimValue $trustMarkClaim): bool => $trustMarkClaim->getTrustMarkId() === $trustMarkId,
        ));
    }

    public function getFirstFor(string $trustMarkId): ?TrustMarksClaimValue
    {
        foreach ($this->trustMarksClaimValues as $trustMarkClaim) {
            if ($trustMarkClaim->getTrustMarkId() === $trustMarkId) {
                return $trustMarkClaim;
            }
        }

        return null;
    }

    /**
     * @return array<mixed[]>
     */
    public function jsonSerialize(): array
    {
        return array_map(
            fn(TrustMarksClaimValue $trustMarksClaimValue): array => $trustMarksClaimValue->jsonSerialize(),
            $this->trustMarksClaimValues,
        );
    }
}
