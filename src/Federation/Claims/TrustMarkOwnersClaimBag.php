<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Claims;

use JsonSerializable;

class TrustMarkOwnersClaimBag implements JsonSerializable
{
    /** @var array<non-empty-string,\SimpleSAML\OpenID\Federation\Claims\TrustMarkOwnersClaimValue>  */
    protected array $trustMarkOwnersClaimValues = [];

    public function __construct(TrustMarkOwnersClaimValue ...$trustMarkOwnersClaimValues)
    {
        $this->add(...$trustMarkOwnersClaimValues);
    }

    public function add(TrustMarkOwnersClaimValue ...$trustMarkOwnersClaimValues): void
    {
        foreach ($trustMarkOwnersClaimValues as $trustMarkOwnersClaimValue) {
            $this->trustMarkOwnersClaimValues[$trustMarkOwnersClaimValue->getTrustMarkId()] =
            $trustMarkOwnersClaimValue;
        }
    }

    public function has(string $trustMarkId): bool
    {
        return isset($this->trustMarkOwnersClaimValues[$trustMarkId]);
    }

    public function get(string $trustMarkId): ?TrustMarkOwnersClaimValue
    {
        return $this->trustMarkOwnersClaimValues[$trustMarkId] ?? null;
    }

    /**
     * @return array<non-empty-string,\SimpleSAML\OpenID\Federation\Claims\TrustMarkOwnersClaimValue>
     */
    public function getAll(): array
    {
        return $this->trustMarkOwnersClaimValues;
    }

    /**
     * @return array<non-empty-string,array<non-empty-string,mixed>>
     */
    public function jsonSerialize(): array
    {
        return array_combine(
            array_keys($this->trustMarkOwnersClaimValues),
            array_map(
                fn(TrustMarkOwnersClaimValue $tMOCValue): array => $tMOCValue->jsonSerialize(),
                $this->trustMarkOwnersClaimValues,
            ),
        );
    }
}
