<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityStatement;

class TrustMarkClaimBag
{
    /**
     * @var \SimpleSAML\OpenID\Federation\EntityStatement\TrustMarkClaim[]
     */
    protected array $trustMarkClaims = [];

    public function __construct(TrustMarkClaim ...$trustMarkClaims)
    {
        $this->add(...$trustMarkClaims);
    }

    public function add(TrustMarkClaim ...$trustMarkClaims): void
    {
        $this->trustMarkClaims = array_merge($this->trustMarkClaims, $trustMarkClaims);
    }

    /**
     * @return \SimpleSAML\OpenID\Federation\EntityStatement\TrustMarkClaim[]
     */
    public function getAll(): array
    {
        return $this->trustMarkClaims;
    }

    /**
     * @param non-empty-string $trustMarkId
     * @return \SimpleSAML\OpenID\Federation\EntityStatement\TrustMarkClaim[]
     */
    public function gerAllFor(string $trustMarkId): array
    {
        return array_values(array_filter(
            $this->trustMarkClaims,
            fn(TrustMarkClaim $trustMarkClaim): bool => $trustMarkClaim->getId() === $trustMarkId,
        ));
    }

    public function getFirstFor(string $trustMarkId): ?TrustMarkClaim
    {
        foreach ($this->trustMarkClaims as $trustMarkClaim) {
            if ($trustMarkClaim->getId() === $trustMarkId) {
                return $trustMarkClaim;
            }
        }

        return null;
    }
}
