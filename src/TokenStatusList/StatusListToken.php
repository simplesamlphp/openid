<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\TokenStatusList;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\StatusListTokenException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\TokenStatusList\Factories\StatusListFactory;

/**
 * Status List Token in JWT format, embedding a Status List into a signed token.
 *
 * https://datatracker.ietf.org/doc/html/draft-ietf-oauth-status-list#name-status-list-token-in-jwt-fo
 *
 * Claims are validated on construction, so an instance is a token that satisfies the structural rules of the
 * specification. What it does not establish is that the token is the right one: checking the signature against a
 * trusted key, and the subject against the URI the Referenced Token pointed at, is the caller's remaining work.
 * StatusResolver does both.
 *
 * @see \SimpleSAML\Test\OpenID\TokenStatusList\StatusListTokenTest
 */
class StatusListToken extends ParsedJws
{
    /**
     * Largest magnitude accepted for a NumericDate claim, being the largest integer a JSON number can carry
     * exactly. Some 285 million years of seconds, so no legitimate timestamp comes near it.
     */
    protected const MAX_NUMERIC_DATE = 9007199254740992; // 2 ** 53


    public function __construct(
        JwsDecorator $jwsDecorator,
        JwsVerifierDecorator $jwsVerifierDecorator,
        JwksDecoratorFactory $jwksDecoratorFactory,
        JwsSerializerManagerDecorator $jwsSerializerManagerDecorator,
        DateIntervalDecorator $timestampValidationLeeway,
        Helpers $helpers,
        ClaimFactory $claimFactory,
        protected readonly StatusListFactory $statusListFactory = new StatusListFactory(new Helpers()),
    ) {
        parent::__construct(
            $jwsDecorator,
            $jwsVerifierDecorator,
            $jwksDecoratorFactory,
            $jwsSerializerManagerDecorator,
            $timestampValidationLeeway,
            $helpers,
            $claimFactory,
        );
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getType(): string
    {
        $typ = parent::getType() ?? throw new StatusListTokenException('No Type header claim found.');

        if ($typ !== JwtTypesEnum::StatusListJwt->value) {
            throw new StatusListTokenException(
                sprintf(
                    'Invalid Type header claim (%s), expected %s.',
                    $typ,
                    JwtTypesEnum::StatusListJwt->value,
                ),
            );
        }

        return $typ;
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getAlgorithm(): string
    {
        return parent::getAlgorithm() ?? throw new StatusListTokenException('No Algorithm header claim found.');
    }


    /**
     * Rejects a token whose protected header marks any parameter as critical.
     *
     * RFC 7515 gives `crit` the meaning "understand every parameter listed here or reject this token", and only
     * lets it name extensions -- parameters the JOSE specifications themselves do not define. This token type
     * defines no extensions and this library implements none, so whatever is listed there is by construction
     * something the code below does not understand. Verifying the signature and reading the list out anyway
     * would be skipping semantics the producer said were mandatory, which is how a status ends up read under
     * rules that were never applied.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function enforceNoCriticalHeaderParameters(): void
    {
        $claimKey = ClaimsEnum::Crit->value;

        // Presence, not value: RFC 7515 requires a `crit` that is there to be a non-empty array of strings, so
        // `"crit": null` is a malformed declaration rather than an omitted one, and it is not going to be waved
        // through by a method whose whole purpose is to reject declarations it cannot honour.
        if (!array_key_exists($claimKey, $this->getHeader())) {
            return;
        }

        $crit = $this->getHeaderClaim($claimKey);

        throw new StatusListTokenException(
            sprintf(
                'Token marks header parameters as critical, and none are supported: %s.',
                implode(
                    ', ',
                    array_map(
                        static fn(mixed $value): string => var_export($value, true),
                        is_array($crit) ? $crit : [$crit],
                    ),
                ),
            ),
        );
    }


    /**
     * The URI of this Status List Token. A Relying Party rejects the token unless this is equal to the `uri` its
     * Referenced Token pointed at.
     *
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getSubject(): string
    {
        $sub = parent::getSubject() ?? throw new StatusListTokenException('No Subject claim found.');

        return $this->helpers->type()->enforceUri($sub, ClaimsEnum::Sub->value);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getIssuedAt(): int
    {
        $claimKey = ClaimsEnum::Iat->value;

        $iat = $this->getPayloadClaim($claimKey) ?? throw new StatusListTokenException('No Issued At claim found.');

        $this->enforceNumericDate($iat, $claimKey);

        return parent::getIssuedAt() ?? throw new StatusListTokenException('No Issued At claim found.');
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getExpirationTime(): ?int
    {
        $claimKey = ClaimsEnum::Exp->value;

        // Optional, but only by being absent. Present and null is a malformed claim, not an omitted one.
        if ($this->hasPayloadClaim($claimKey)) {
            $this->enforceNumericDate($this->getPayloadClaim($claimKey), $claimKey);
        }

        return parent::getExpirationTime();
    }


    /**
     * The Not Before claim is not one the specification asks a Status List Token to carry, but a token may carry
     * it, and the inherited validation acts on it when it is there. It therefore gets the same NumericDate
     * treatment as the claims that are specified, rather than the laxer inherited handling.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getNotBefore(): ?int
    {
        $claimKey = ClaimsEnum::Nbf->value;

        if ($this->hasPayloadClaim($claimKey)) {
            $this->enforceNumericDate($this->getPayloadClaim($claimKey), $claimKey);
        }

        return parent::getNotBefore();
    }


    /**
     * Enforces that the claims which are optional are optional by being absent, rather than by being present
     * with a null value.
     *
     * The inherited getters read a claim with `??` and return null for one that is missing, which makes an
     * explicit null indistinguishable from an omission and lets it pass for a claim that was never there. That
     * is the same rule already applied to `exp`, `nbf`, `ttl` and `crit`, applied here to the registered claims
     * this token type does not itself require but which have a defined shape once present.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function enforceNoNullOptionalClaims(): void
    {
        $payload = $this->getPayload();
        $header = $this->getHeader();

        /** @var array<string,array<string,mixed>> $optionalClaims */
        $optionalClaims = [
            ClaimsEnum::Iss->value => $payload,
            ClaimsEnum::Aud->value => $payload,
            ClaimsEnum::Jti->value => $payload,
            ClaimsEnum::Kid->value => $header,
        ];

        foreach ($optionalClaims as $claimKey => $claims) {
            if (array_key_exists($claimKey, $claims) && is_null($claims[$claimKey])) {
                throw new StatusListTokenException(
                    sprintf('Claim %s is present and null, which is not a value it may take.', $claimKey),
                );
            }
        }
    }


    /**
     * Whether the payload carries the claim at all, as distinct from carrying it with a null value.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function hasPayloadClaim(string $key): bool
    {
        return array_key_exists($key, $this->getPayload());
    }


    /**
     * Enforces that a claim is an RFC 7519 NumericDate, being a JSON number rather than a numeric string, and one
     * whose whole-second part survives the conversion to an integer that the inherited getters perform.
     *
     * The range check is what stops a nonsensical value from becoming a plausible one: casting a float larger than
     * the integer range yields 0 (or a negative number) rather than saturating, so an `iat` of 1e100 would
     * otherwise read as the epoch and sail past the check that a token was not issued in the future.
     *
     * RFC 7519 permits a NumericDate to carry a fraction of a second, so one is accepted here rather than
     * rejected -- but the inherited getters return whole seconds and compare against a whole-second clock, so a
     * fractional claim is truncated towards zero on the way out. The subsecond part is therefore not what any
     * decision rests on: it is bounded by definition at under a second, against a timestamp validation leeway
     * that is a minute by default and exists to absorb clock skew orders of magnitude larger. `exp` truncating
     * downwards expires a token marginally sooner rather than later. Callers needing the claim exactly as signed
     * should read it through getPayloadClaim(), the narrower return type here not being one a subclass may widen.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListTokenException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    protected function enforceNumericDate(mixed $value, string $claimKey): void
    {
        $value = $this->helpers->type()->ensureNumber($value, $claimKey);

        if ($value < -self::MAX_NUMERIC_DATE || $value > self::MAX_NUMERIC_DATE) {
            throw new StatusListTokenException(
                sprintf(
                    'Claim %s is not a usable NumericDate: %s is outside the representable range.',
                    $claimKey,
                    var_export($value, true),
                ),
            );
        }
    }


    /**
     * Maximum amount of time, in seconds, that this token can be cached before a fresh copy should be retrieved.
     *
     * The specification requires a positive number encoded in JSON as a number, and does not require it to be a
     * whole one, so a fractional value is returned as given rather than rounded into an integer.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getTimeToLive(): int|float|null
    {
        $claimKey = ClaimsEnum::Ttl->value;

        // Optional, but only by being absent. Present and null is a malformed claim, not an omitted one.
        if (!$this->hasPayloadClaim($claimKey)) {
            return null;
        }

        $ttl = $this->helpers->type()->ensureNumber($this->getPayloadClaim($claimKey), $claimKey);

        if ($ttl <= 0) {
            throw new StatusListTokenException(
                sprintf('Time To Live claim must be a positive number, %s given.', var_export($ttl, true)),
            );
        }

        // Bounded for the same reason a NumericDate is: consumers turn this into an integer number of seconds,
        // and a value past the integer range casts to zero rather than saturating, which would read as "do not
        // cache at all" and turn a nonsensical claim into a stream of requests to the Status Provider.
        if ($ttl > self::MAX_NUMERIC_DATE) {
            throw new StatusListTokenException(
                sprintf(
                    'Time To Live claim is outside the representable range: %s given.',
                    var_export($ttl, true),
                ),
            );
        }

        return $ttl;
    }


    /**
     * Members of the `status_list` claim, before the list itself is decoded.
     *
     * @return array<string,mixed>
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getStatusListClaimData(): array
    {
        $claimKey = ClaimsEnum::StatusList->value;

        $statusList = $this->getPayloadClaim($claimKey) ?? throw new StatusListTokenException(
            'No Status List claim found.',
        );

        if (!is_array($statusList)) {
            throw new StatusListTokenException(
                sprintf('Status List claim must be an object, %s given.', get_debug_type($statusList)),
            );
        }

        return $this->helpers->type()->ensureArrayWithKeysAsStrings($statusList, $claimKey);
    }


    /**
     * Number of bits per Referenced Token the embedded Status List uses.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getBits(): int
    {
        $claimKey = ClaimsEnum::Bits->value;

        $bits = $this->getStatusListClaimData()[$claimKey] ?? null;

        // A JSON Integer, so a numeric string does not satisfy it.
        if (!is_int($bits)) {
            throw new StatusListTokenException(
                sprintf('Status List bits claim must be an integer, %s given.', get_debug_type($bits)),
            );
        }

        if (!StatusList::isAllowedBits($bits)) {
            throw new StatusListTokenException(
                sprintf(
                    'Invalid number of bits per Referenced Token (%d), expected one of: %s.',
                    $bits,
                    implode(', ', StatusList::ALLOWED_BITS),
                ),
            );
        }

        return $bits;
    }


    /**
     * The embedded Status List in its encoded form, before it is decoded and bounded.
     *
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getEncodedStatusList(): string
    {
        $claimKey = ClaimsEnum::Lst->value;

        return $this->helpers->type()->ensureNonEmptyString(
            $this->getStatusListClaimData()[$claimKey] ?? null,
            $claimKey,
        );
    }


    /**
     * Decodes the embedded Status List.
     *
     * Decompression is bounded by the caller, since the specification sets no maximum list size; see
     * StatusListFactory::fromEncoded().
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getStatusList(int $maxDecompressedBytes, ?int $maxCompressedBytes = null): StatusList
    {
        return $this->statusListFactory->fromClaimData(
            $this->getStatusListClaimData(),
            $maxDecompressedBytes,
            $maxCompressedBytes,
        );
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function validate(): void
    {
        $this->validateByCallbacks(
            $this->getAlgorithm(...),
            $this->getType(...),
            $this->enforceNoCriticalHeaderParameters(...),
            $this->getSubject(...),
            $this->getIssuedAt(...),
            $this->getExpirationTime(...),
            $this->getTimeToLive(...),
            $this->getStatusListClaimData(...),
            // Structure only. Decoding the list needs a size bound the caller has to supply, so it stays explicit.
            $this->getBits(...),
            $this->getEncodedStatusList(...),
            // Claims the specification does not ask for, but which a token may carry and which have a defined
            // shape once present. A Status List Token that is invalid as a plain JWT is not one to work from,
            // and leaving these unchecked lets an instance exist whose own getters throw when anything reads it.
            $this->getIssuer(...),
            $this->getAudience(...),
            $this->getJwtId(...),
            $this->getKeyId(...),
            $this->enforceNoNullOptionalClaims(...),
        );
    }
}
