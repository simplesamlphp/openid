<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Claims;

use JsonSerializable;
use SimpleSAML\OpenID\Claims\JwksClaim;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

class TrustMarkOwnersClaimValue implements JsonSerializable
{
    /**
     * @param non-empty-string $trustMarkType
     * @param non-empty-string $subject
     * @param array<non-empty-string,mixed> $otherClaims
     */
    public function __construct(
        protected readonly string $trustMarkType,
        protected readonly string $subject,
        protected readonly JwksClaim $jwks,
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
    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getJwks(): JwksClaim
    {
        return $this->jwks;
    }

    /**
     * @return array<non-empty-string,mixed>
     */
    public function getOtherClaims(): array
    {
        return $this->otherClaims;
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return array_merge(
            [
                ClaimsEnum::TrustMarkType->value => $this->trustMarkType,
                ClaimsEnum::Sub->value => $this->subject,
                ClaimsEnum::Jwks->value => $this->jwks->getValue(),
            ],
            $this->otherClaims,
        );
    }
}
