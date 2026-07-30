<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\TokenStatusList;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\ValueAbstracts\ClaimInterface;

/**
 * The `status` claim of a Referenced Token, referencing the Status List mechanism.
 *
 * https://datatracker.ietf.org/doc/html/draft-ietf-oauth-status-list#name-status-claim
 *
 * The claim is an object which may reference several status mechanisms; this library defines the `status_list`
 * member of it. Not to be confused with the W3C VCDM `credentialStatus` claim, which is a different specification.
 *
 * @see \SimpleSAML\Test\OpenID\TokenStatusList\StatusClaimTest
 */
class StatusClaim implements ClaimInterface
{
    public function __construct(
        protected readonly StatusReference $statusReference,
    ) {
    }


    public function getStatusReference(): StatusReference
    {
        return $this->statusReference;
    }


    public function getName(): string
    {
        return ClaimsEnum::Status->value;
    }


    /**
     * @return array<string,mixed>
     */
    public function getValue(): array
    {
        return [
            ClaimsEnum::StatusList->value => $this->statusReference->jsonSerialize(),
        ];
    }


    /**
     * The complete claim, ready to be merged into the payload of a Referenced Token.
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            $this->getName() => $this->getValue(),
        ];
    }
}
