<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\TokenStatusList\Factories;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\StatusListException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\TokenStatusList\StatusClaim;
use SimpleSAML\OpenID\TokenStatusList\StatusReference;

/**
 * @see \SimpleSAML\Test\OpenID\TokenStatusList\Factories\StatusReferenceFactoryTest
 */
class StatusReferenceFactory
{
    public function __construct(
        protected readonly Helpers $helpers,
    ) {
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function build(string $uri, int $idx): StatusReference
    {
        return new StatusReference($uri, $idx, $this->helpers);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function buildClaim(string $uri, int $idx): StatusClaim
    {
        return new StatusClaim($this->build($uri, $idx));
    }


    /**
     * Builds a reference from the contents of a `status_list` member.
     *
     * @param array<string,mixed> $claimData
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function fromStatusListClaimData(array $claimData): StatusReference
    {
        $idx = $claimData[ClaimsEnum::Idx->value] ?? null;

        // A non-negative Integer, so a numeric string or a float does not satisfy it.
        if (!is_int($idx)) {
            throw new StatusListException(
                sprintf('Status List index claim must be an integer, %s given.', get_debug_type($idx)),
            );
        }

        $uri = $this->helpers->type()->ensureNonEmptyString(
            $claimData[ClaimsEnum::Uri->value] ?? null,
            ClaimsEnum::Uri->value,
        );

        return $this->build($uri, $idx);
    }


    /**
     * Builds a reference from the payload of a Referenced Token, or returns null if the token carries no `status`
     * claim at all. A `status` claim which is present but does not reference this mechanism, or which is malformed,
     * is an error rather than an absence.
     *
     * @param array<string,mixed> $payload
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function fromReferencedTokenPayload(array $payload): ?StatusReference
    {
        // Absence of the claim is the only thing that reads as "no status mechanism". A claim which is present
        // but null is a malformed Referenced Token, and saying so is what stops a caller from treating it as one
        // that simply carries no status.
        if (!array_key_exists(ClaimsEnum::Status->value, $payload)) {
            return null;
        }

        $status = $payload[ClaimsEnum::Status->value];

        if (!is_array($status)) {
            throw new StatusListException(
                sprintf('Status claim must be an object, %s given.', get_debug_type($status)),
            );
        }

        $statusList = $status[ClaimsEnum::StatusList->value] ?? null;

        if (!is_array($statusList)) {
            throw new StatusListException(
                sprintf('Status List claim must be an object, %s given.', get_debug_type($statusList)),
            );
        }

        return $this->fromStatusListClaimData(
            $this->helpers->type()->ensureArrayWithKeysAsStrings($statusList, ClaimsEnum::StatusList->value),
        );
    }
}
