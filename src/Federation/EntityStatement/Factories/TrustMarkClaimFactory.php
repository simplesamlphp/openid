<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityStatement\Factories;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\TrustMarkClaimException;
use SimpleSAML\OpenID\Federation\EntityStatement\TrustMarkClaim;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkFactory;
use SimpleSAML\OpenID\Federation\TrustMark;

class TrustMarkClaimFactory
{
    public function __construct(
        protected readonly TrustMarkFactory $trustMarkFactory,
    ) {
    }

    /**
     * @param array<string,mixed> $otherClaims
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkClaimException
     */
    public function build(
        string $id,
        TrustMark $trustMark,
        array $otherClaims = [],
    ): TrustMarkClaim {
        return new TrustMarkClaim($id, $trustMark, $otherClaims);
    }

    /**
     * @param array<string,mixed> $trustMarkClaimData
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkClaimException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function buildFrom(array $trustMarkClaimData): TrustMarkClaim
    {
        // Each JSON object MUST contain the following two claims and MAY contain other claims.
        // id
        // The Trust Mark identifier. It MUST be the same value as the id claim contained in the Trust Mark JWT.
        // trust_mark
        // A signed JSON Web Token that represents a Trust Mark.
        $id = (string)($trustMarkClaimData[ClaimsEnum::Id->value] ?? throw new TrustMarkClaimException(
            'No ID present in Trust Mark Claim.',
        ));

        $trustMarkToken = (string)($trustMarkClaimData[ClaimsEnum::TrustMark->value] ??
        throw new TrustMarkClaimException(
            'No Trust Mark token present in Trust Mark Claim.',
        ));

        $otherClaims = array_diff_key(
            $trustMarkClaimData,
            [ClaimsEnum::Id->value => true, ClaimsEnum::TrustMark->value => true],
        );

        return $this->build(
            $id,
            $this->trustMarkFactory->fromToken($trustMarkToken),
            $otherClaims,
        );
    }
}
