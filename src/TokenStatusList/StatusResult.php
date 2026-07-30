<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\TokenStatusList;

use DateTimeImmutable;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\StatusTypeEnum;

/**
 * Outcome of resolving the status of a Referenced Token against its Status List Token.
 *
 * @see \SimpleSAML\Test\OpenID\TokenStatusList\StatusResultTest
 */
class StatusResult
{
    /**
     * @param int $status Raw status value read from the Status List.
     * @param \DateTimeImmutable $resolvedAt Time the status was resolved, for the caller's own freshness policy.
     */
    public function __construct(
        protected readonly int $status,
        protected readonly StatusReference $statusReference,
        protected readonly StatusListToken $statusListToken,
        protected readonly DateTimeImmutable $resolvedAt,
    ) {
    }


    public function getStatus(): int
    {
        return $this->status;
    }


    /**
     * Status Type for the resolved value, or null if the value is application specific or not yet registered.
     */
    public function getStatusType(): ?StatusTypeEnum
    {
        return StatusTypeEnum::tryFrom($this->status);
    }


    /**
     * Whether the Referenced Token is valid according to the Status List alone.
     *
     * This is not the whole answer about the Referenced Token: the processing rules for the token itself
     * supersede its status here, so a token which is expired through its own claims stays expired even when this
     * returns true.
     */
    public function isValid(): bool
    {
        return $this->status === StatusTypeEnum::Valid->value;
    }


    public function getStatusReference(): StatusReference
    {
        return $this->statusReference;
    }


    public function getStatusListToken(): StatusListToken
    {
        return $this->statusListToken;
    }


    public function getResolvedAt(): DateTimeImmutable
    {
        return $this->resolvedAt;
    }


    /**
     * Whether this result may still be relied upon at the given time.
     *
     * Two limits apply, whichever falls first: the `ttl` claim, being how long the issuer permits a copy to be
     * held from the time the status was resolved, and the `exp` claim, past which the Status List Token states
     * nothing at all. A token carrying neither states no limit, so the caller's own policy is all there is and
     * this returns true.
     *
     * Note what the `ttl` limit is measured from. When a result came from a Status List Token that the fetcher
     * served out of its cache, the `ttl` window is measured from that resolution, not from when the token was
     * originally retrieved, so a result held across a cache hit can be considered fresh for up to two `ttl`
     * periods after the token reached this process. `exp` bounds that, and so does the fetcher's own cache
     * lifetime, which never exceeds `ttl`. Callers wanting the tighter guarantee should resolve again rather
     * than hold a result, or pass the original retrieval time to StatusResolver::resolveWithToken().
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function isFreshAt(DateTimeImmutable $moment): bool
    {
        // Compared with the subsecond part intact. Both limits are specified as numbers rather than whole
        // seconds -- `ttl` explicitly so, and a NumericDate by RFC 7519 -- and truncating either side to whole
        // seconds can leave a result looking fresh for most of a second after its window closed.
        $timestamp = $this->toFloatTimestamp($moment);

        $ttl = $this->statusListToken->getTimeToLive();

        // `ttl` is how long a copy may be held from the time it was obtained, so the instant it runs out is the
        // last one still inside the window the issuer allowed.
        if ($ttl !== null && $this->toFloatTimestamp($this->resolvedAt) + $ttl < $timestamp) {
            return false;
        }

        // Read from the payload rather than through getExpirationTime(), which throws once the token is expired
        // and would turn the answer to a question about freshness into an exception.
        $exp = $this->statusListToken->getPayloadClaim(ClaimsEnum::Exp->value);

        // RFC 7519 asks for the current time to be *before* `exp`, so unlike the `ttl` window the limit itself
        // is already outside it. The claim was bounded to a usable NumericDate when the token was validated, so
        // the cast here cannot turn a nonsensical value into a plausible one.
        if (is_int($exp) || is_float($exp)) {
            return (float)$exp > $timestamp;
        }

        return true;
    }


    /**
     * Seconds since the epoch, with the subsecond part kept.
     */
    protected function toFloatTimestamp(DateTimeImmutable $moment): float
    {
        return (float)$moment->format('U.u');
    }
}
