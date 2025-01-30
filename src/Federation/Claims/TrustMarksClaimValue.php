<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Claims;

/**
 * Value object representing a single Trust Mark Claim value with properties 'id' and 'trust_mark'. This object is
 * contained in trust_marks claim array in Entity Statement.
 */
class TrustMarksClaimValue
{
    /**
     * @param non-empty-string $trustMarkId
     * @param non-empty-string $trustMark
     * @param array<non-empty-string,mixed> $otherClaims
     */
    public function __construct(
        protected readonly string $trustMarkId,
        protected readonly string $trustMark,
        protected readonly array $otherClaims = [],
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function getTrustMarkId(): string
    {
        return $this->trustMarkId;
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
}
