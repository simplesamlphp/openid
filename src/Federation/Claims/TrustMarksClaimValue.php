<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Claims;

use JsonSerializable;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

/**
 * Value object representing a single Trust Mark Claim value with properties 'id' and 'trust_mark'. This object is
 * contained in trust_marks claim array in Entity Statement.
 */
class TrustMarksClaimValue implements JsonSerializable
{
    /**
     * @param non-empty-string $trustMarkType
     * @param non-empty-string $trustMark
     * @param array<non-empty-string,mixed> $otherClaims
     */
    public function __construct(
        protected readonly string $trustMarkType,
        protected readonly string $trustMark,
        protected readonly array $otherClaims = [],
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function getTrustMarkType(): string
    {
        return $this->trustMarkType;
    }

    /**
     * @return non-empty-string
     */
    public function getTrustMark(): string
    {
        return $this->trustMark;
    }

    /**
     * @return array<non-empty-string,mixed>
     */
    public function getOtherClaims(): array
    {
        return $this->otherClaims;
    }

    /**
     * @return array<non-empty-string,mixed>
     */
    public function jsonSerialize(): array
    {
        return array_merge(
            [
                ClaimsEnum::TrustMarkType->value => $this->trustMarkType,
                ClaimsEnum::TrustMark->value => $this->trustMark,
            ],
            $this->otherClaims,
        );
    }
}
