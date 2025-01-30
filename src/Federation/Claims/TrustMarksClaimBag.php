<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Claims;

class TrustMarksClaimBag
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
    public function gerAllFor(string $trustMarkId): array
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
}
