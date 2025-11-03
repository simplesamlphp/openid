<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Factories;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\TrustMarkException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Federation\Claims\TrustMarkIssuersClaimBag;
use SimpleSAML\OpenID\Federation\Claims\TrustMarkIssuersClaimValue;
use SimpleSAML\OpenID\Federation\Claims\TrustMarkOwnersClaimBag;
use SimpleSAML\OpenID\Federation\Claims\TrustMarkOwnersClaimValue;
use SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimBag;
use SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimValue;
use SimpleSAML\OpenID\Helpers;

class FederationClaimFactory
{
    public function __construct(
        protected readonly Helpers $helpers,
        protected readonly ClaimFactory $claimFactory,
    ) {
    }


    /**
     * @param array<array-key,mixed> $otherClaims
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function buildTrustMarksClaimValue(
        mixed $trustMarkType,
        mixed $trustMark,
        mixed $otherClaims = [],
    ): TrustMarksClaimValue {
        $trustMarkType = $this->helpers->type()->ensureNonEmptyString($trustMarkType);
        $trustMark = $this->helpers->type()->ensureNonEmptyString($trustMark);
        $otherClaims = $this->helpers->type()->ensureArrayWithKeysAsNonEmptyStrings($otherClaims);

        return new TrustMarksClaimValue($trustMarkType, $trustMark, $otherClaims);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function buildTrustMarksClaimValueFrom(mixed $trustMarksClaimData): TrustMarksClaimValue
    {
        $trustMarksClaimData = $this->helpers->type()->ensureArray($trustMarksClaimData);

        // Each JSON object MUST contain the following two claims and MAY contain other claims.
        // trust_mark_type
        // The Trust Mark Type identifier. It MUST be the same value as the id claim contained in the Trust Mark JWT.
        // trust_mark
        // A signed JSON Web Token that represents a Trust Mark.
        $trustMarkType = $trustMarksClaimData[ClaimsEnum::TrustMarkType->value] ?? throw new TrustMarkException(
            'No type present in Trust Mark claim.',
        );

        $trustMark = $trustMarksClaimData[ClaimsEnum::TrustMark->value] ?? throw new TrustMarkException(
            'No Trust Mark present in Trust Mark claim.',
        );

        $otherClaims = array_diff_key(
            $trustMarksClaimData,
            [ClaimsEnum::TrustMarkType->value => true, ClaimsEnum::TrustMark->value => true],
        );

        return $this->buildTrustMarksClaimValue(
            $trustMarkType,
            $trustMark,
            $otherClaims,
        );
    }


    public function buildTrustMarksClaimBag(TrustMarksClaimValue ...$trustMarksClaimValues): TrustMarksClaimBag
    {
        return new TrustMarksClaimBag(...$trustMarksClaimValues);
    }


    public function buildTrustMarkOwnersClaimValue(
        mixed $trustMarkType,
        mixed $subject,
        mixed $jwks,
        mixed $otherClaims = [],
    ): TrustMarkOwnersClaimValue {
        $trustMarkType = $this->helpers->type()->ensureNonEmptyString($trustMarkType);
        $subject = $this->helpers->type()->ensureNonEmptyString($subject);
        $jwksClaim = $this->claimFactory->buildJwks($jwks);
        $otherClaims = $this->helpers->type()->ensureArrayWithKeysAsNonEmptyStrings($otherClaims);

        return new TrustMarkOwnersClaimValue(
            $trustMarkType,
            $subject,
            $jwksClaim,
            $otherClaims,
        );
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     */
    public function buildTrustMarkOwnersClaimBagFrom(mixed $trustMarkOwnersClaimData): TrustMarkOwnersClaimBag
    {
        $trustMarkOwnersClaimData = $this->helpers->type()->ensureArray($trustMarkOwnersClaimData);

        $trustMarkOwnersClaimValues = [];

        // It is a JSON object with member names that are Trust Mark Type identifiers and each corresponding value
        // being a JSON object with these members:
        // sub
        // REQUIRED Identifier of the Trust Mark Owner.
        // jwks
        // REQUIRED JSON Web Key Set (JWKS) [RFC7517] containing the owner's Federation Entity Keys used
        // for signing.
        // Other members MAY also be defined and used.

        foreach ($trustMarkOwnersClaimData as $trustMarkType => $trustMarkOwnersClaim) {
            $trustMarkOwnersClaim = $this->helpers->type()->ensureArray($trustMarkOwnersClaim);


            $subject = $trustMarkOwnersClaim[ClaimsEnum::Sub->value] ?? throw new TrustMarkException(
                'No Subject present in Trust Mark Owners claim.',
            );
            $jwks = $trustMarkOwnersClaim[ClaimsEnum::Jwks->value] ?? throw new TrustMarkException(
                'No JWKS present in Trust Mark Owners claim.',
            );

            $otherClaims = array_diff_key(
                $trustMarkOwnersClaim,
                [ClaimsEnum::TrustMarkType->value => true, ClaimsEnum::TrustMark->value => true],
            );

            $trustMarkOwnersClaimValues[] = $this->buildTrustMarkOwnersClaimValue(
                $trustMarkType,
                $subject,
                $jwks,
                $otherClaims,
            );
        }

        return $this->buildTrustMarkOwnersClaimBag(...$trustMarkOwnersClaimValues);
    }


    public function buildTrustMarkOwnersClaimBag(
        TrustMarkOwnersClaimValue ...$trustMarkOwnersClaimValues,
    ): TrustMarkOwnersClaimBag {
        return new TrustMarkOwnersClaimBag(...$trustMarkOwnersClaimValues);
    }


    public function buildTrustMarkIssuersClaimBagFrom(mixed $trustMarkIssuersClaimData): TrustMarkIssuersClaimBag
    {
        $trustMarkIssuersClaimData = $this->helpers->type()->ensureArrayWithKeysAsNonEmptyStrings(
            $trustMarkIssuersClaimData,
        );

        $trustMarkIssuersClaimValues = [];

        foreach ($trustMarkIssuersClaimData as $trustMarkType => $trustMarkIssuersClaim) {
            $trustMarkIssuersClaim = $this->helpers->type()->ensureArrayWithValuesAsNonEmptyStrings(
                $trustMarkIssuersClaim,
            );

            $trustMarkIssuersClaimValues[] = $this->buildTrustMarkIssuersClaimValue(
                $trustMarkType,
                $trustMarkIssuersClaim,
            );
        }

        return $this->buildTrustMarkIssuerClaimBag(...$trustMarkIssuersClaimValues);
    }


    public function buildTrustMarkIssuersClaimValue(
        mixed $trustMarkType,
        mixed $trustMarkIssuers,
    ): TrustMarkIssuersClaimValue {
        $trustMarkType = $this->helpers->type()->ensureNonEmptyString($trustMarkType);
        $trustMarkIssuers = $this->helpers->type()->ensureArrayWithValuesAsNonEmptyStrings($trustMarkIssuers);

        return new TrustMarkIssuersClaimValue(
            $trustMarkType,
            $trustMarkIssuers,
        );
    }


    public function buildTrustMarkIssuerClaimBag(
        TrustMarkIssuersClaimValue ...$trustMarkIssuersClaimValues,
    ): TrustMarkIssuersClaimBag {
        return new TrustMarkIssuersClaimBag(...$trustMarkIssuersClaimValues);
    }
}
