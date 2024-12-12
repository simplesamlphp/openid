<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityStatement;

use SimpleSAML\OpenID\Exceptions\TrustMarkClaimException;
use SimpleSAML\OpenID\Federation\TrustMark;

/**
 * Value object representing a single Trust Mark Claim value with properties 'id' and 'trust_mark'. This object is
 * contained in trust_marks claim array in Entity Statement.
 */
class TrustMarkClaim
{
    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkClaimException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function __construct(
        protected readonly string $id,
        protected readonly TrustMark $trustMark,
        protected readonly array $otherClaims = [],
    ) {
        if ($id !== $this->trustMark->getIdentifier()) {
            throw new TrustMarkClaimException(
                sprintf('Invalid TrustMark identifier: %s != %s.', $id, $this->trustMark->getIdentifier()),
            );
        }

        // All the claims in the JSON object MUST have the same values as those contained in the Trust Mark JWT.
        $trustMarkPayload = $this->trustMark->getPayload();
        $commonClaims = array_intersect_key($this->otherClaims, $trustMarkPayload);
        /** @psalm-suppress MixedAssignment */
        foreach ($commonClaims as $key => $value) {
            if ($value !== $trustMarkPayload[$key]) {
                throw new TrustMarkClaimException(
                    sprintf(
                        "Trust Mark Claim value for '$key' is different from Trust Mark JWT value: %s != %s",
                        var_export($value, true),
                        var_export($trustMarkPayload[$key], true),
                    ),
                );
            }
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTrustMark(): TrustMark
    {
        return $this->trustMark;
    }

    public function getOtherClaims(): array
    {
        return $this->otherClaims;
    }
}
