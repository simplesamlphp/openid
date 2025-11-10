<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Claims;

use JsonSerializable;

class TrustMarkIssuersClaimBag implements JsonSerializable
{
    /** @var array<non-empty-string,\SimpleSAML\OpenID\Federation\Claims\TrustMarkIssuersClaimValue> */
    protected array $trustMarkIssuersClaimValues = [];


    public function __construct(TrustMarkIssuersClaimValue ...$trustMarkIssuersClaimValues)
    {
        $this->add(...$trustMarkIssuersClaimValues);
    }


    public function add(TrustMarkIssuersClaimValue ...$trustMarkIssuersClaimValues): void
    {
        foreach ($trustMarkIssuersClaimValues as $trustMarkIssuersClaimValue) {
            $this->trustMarkIssuersClaimValues[$trustMarkIssuersClaimValue->getTrustMarkType()] =
            $trustMarkIssuersClaimValue;
        }
    }


    public function has(string $trustMarkType): bool
    {
        return isset($this->trustMarkIssuersClaimValues[$trustMarkType]);
    }


    public function get(string $trustMarkType): ?TrustMarkIssuersClaimValue
    {
        return $this->trustMarkIssuersClaimValues[$trustMarkType] ?? null;
    }


    /**
     * @return \SimpleSAML\OpenID\Federation\Claims\TrustMarkIssuersClaimValue[]
     */
    public function getAll(): array
    {
        return $this->trustMarkIssuersClaimValues;
    }


    /**
     * @return array<non-empty-string,array<non-empty-string>>
     */
    public function jsonSerialize(): array
    {
        return array_combine(
            array_keys($this->trustMarkIssuersClaimValues),
            array_map(
                fn(TrustMarkIssuersClaimValue $tMICValue): array => $tMICValue->getTrustMarkIssuers(),
                $this->trustMarkIssuersClaimValues,
            ),
        );
    }
}
