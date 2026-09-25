<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws;

use JsonException;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Helpers\Type;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Serializers\JwsSerializerEnum;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use Throwable;

/**
 * The getters for claims and header parameters that are strings by definition (iss, sub, jti, id, kid, typ, alg)
 * take a JSON string only, and refuse a number or a boolean rather than cast it into a string the token never
 * carried.
 *
 * @see \SimpleSAML\Test\OpenID\Jws\ParsedJwsTest
 */
class ParsedJws
{
    /**
     * @var array<string,mixed>
     */
    protected ?array $header = null;

    /**
     * @var array<string,mixed>
     */
    protected ?array $payload = null;

    protected ?string $token = null;


    public function __construct(
        protected readonly JwsDecorator $jwsDecorator,
        protected readonly JwsVerifierDecorator $jwsVerifierDecorator,
        protected readonly JwksDecoratorFactory $jwksDecoratorFactory,
        protected readonly JwsSerializerManagerDecorator $jwsSerializerManagerDecorator,
        protected readonly DateIntervalDecorator $timestampValidationLeeway,
        protected readonly Helpers $helpers,
        protected readonly ClaimFactory $claimFactory,
    ) {
        $this->validate();
        $this->validateCommonTimestamps();
    }


    protected function validate(): void
    {
    }


    protected function validateCommonTimestamps(): void
    {
        $this->validateByCallbacks(
            $this->getExpirationTime(...),
            $this->getNotBefore(...),
            $this->getIssuedAt(...),
        );
    }


    /**
     * Whether the Expiration Time (exp) claim should be validated against the current time. Subclasses which
     * represent tokens that are legitimately used after expiry (like the ID Token Hint) may override this to
     * accept expired tokens. Note that the Not Before (nbf) and Issued At (iat) checks are intentionally not
     * covered here: a previously issued token always has those in the past, so a future value remains invalid.
     */
    protected function shouldValidateExpirationTime(): bool
    {
        return true;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function validateByCallbacks(callable ...$calls): void
    {
        $errors = [];

        foreach ($calls as $call) {
            try {
                call_user_func($call);
            } catch (Throwable $exception) {
                $errors[] = sprintf('%s: %s', $exception::class, $exception->getMessage());
            }
        }

        if ($errors !== []) {
            throw new JwsException('JWS not valid: ' . implode('; ', $errors));
        }
    }


    /**
     * Rejects a token whose protected header marks any parameter as critical. For a subclass to call from its
     * validate() when its token type defines no JWS extensions.
     *
     * RFC 7515 section 4.1.11: "If any of the listed extension Header Parameters are not understood and supported
     * by the recipient, then the JWS is invalid." and "This Header Parameter MUST be understood and processed by
     * implementations." This library implements no JWS extension, so whatever "crit" lists is by construction
     * something the code here does not understand; verifying the signature and reading the claims out anyway
     * would be skipping semantics the producer said were mandatory.
     *
     * Presence is what is checked, not the value: the section forbids the empty list, and a "crit" present with
     * any other malformed value is a declaration that can not be honoured either.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function enforceNoCriticalHeaderParameters(): void
    {
        $claimKey = ClaimsEnum::Crit->value;

        if (!$this->hasHeaderClaim($claimKey)) {
            return;
        }

        $crit = $this->getHeaderClaim($claimKey);

        throw new JwsException(
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
     * Rejects a token whose protected header carries the RFC 7797 "b64" parameter. For a subclass to call from
     * its validate() when its token type is a JWT.
     *
     * RFC 7797 section 7: "For interoperability reasons, JSON Web Tokens [JWT] MUST NOT use "b64" with a "false"
     * value." and section 6: "The "crit" Header Parameter MUST be included with "b64" in its set of values when
     * using the "b64" Header Parameter to cause implementations not implementing "b64" to reject the JWS (instead
     * of it being misinterpreted)." A "b64" that comes with "crit" falls to enforceNoCriticalHeaderParameters();
     * one that comes without it is a header the producer was not allowed to write, and the underlying verifier
     * would honour it all the same, accepting a raw, unencoded payload under a valid signature. So it is refused
     * whatever its value: section 7 also has a producer omit it when it would be "true".
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function enforceNoUnencodedPayloadOption(): void
    {
        $claimKey = ClaimsEnum::B64->value;

        if (!$this->hasHeaderClaim($claimKey)) {
            return;
        }

        throw new JwsException(
            sprintf(
                'Token carries the %s header parameter, which a JWT may not use: %s.',
                $claimKey,
                var_export($this->getHeaderClaim($claimKey), true),
            ),
        );
    }


    /**
     * Enforces that the given optional claims are optional by being absent, rather than by being present with a
     * null value. For a subclass to call from its validate() with the claims its token type treats as optional.
     *
     * The getters read a claim with `??` and return null for one that is missing, which makes an explicit null
     * indistinguishable from an omission and would let it pass for a claim that was never there. Checked once
     * here, so that after construction the getters only ever see an absent claim or a valid one.
     *
     * @param string[] $payloadClaimKeys
     * @param string[] $headerClaimKeys
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function enforceNoNullOptionalClaims(array $payloadClaimKeys, array $headerClaimKeys = []): void
    {
        $payload = $this->getPayload();
        $header = $this->getHeader();

        foreach ($payloadClaimKeys as $claimKey) {
            if (array_key_exists($claimKey, $payload) && is_null($payload[$claimKey])) {
                throw new JwsException(
                    sprintf('Claim %s is present and null, which is not a value it may take.', $claimKey),
                );
            }
        }

        foreach ($headerClaimKeys as $claimKey) {
            if (array_key_exists($claimKey, $header) && is_null($header[$claimKey])) {
                throw new JwsException(
                    sprintf('Claim %s is present and null, which is not a value it may take.', $claimKey),
                );
            }
        }
    }


    /**
     * @return array<string,mixed>
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getHeader(int $signatureId = 0): array
    {
        try {
            return $this->header ??= $this->jwsDecorator->jws()->getSignature($signatureId)->getProtectedHeader();
        } catch (Throwable $throwable) {
            throw new JwsException('Unable to get protected header.', (int)$throwable->getCode(), $throwable);
        }
    }


    /**
     * @param non-empty-string $key
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getHeaderClaim(string $key): mixed
    {
        return $this->getHeader()[$key] ?? null;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getPayloadClaim(string $key): mixed
    {
        return $this->getPayload()[$key] ?? null;
    }


    /**
     * Whether the payload carries the claim at all, as distinct from carrying it with a null value, which
     * getPayloadClaim() cannot tell from an absent one.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function hasPayloadClaim(string $key): bool
    {
        return array_key_exists($key, $this->getPayload());
    }


    /**
     * Whether the protected header carries the parameter at all, as distinct from carrying it with a null value.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function hasHeaderClaim(string $key): bool
    {
        return array_key_exists($key, $this->getHeader());
    }


    public function getNestedPayloadClaim(int|string ...$keys): mixed
    {
        return $this->helpers->arr()->getNestedValue(
            $this->getPayload(),
            ...$keys,
        );
    }


    public function getToken(
        JwsSerializerEnum $jwsSerializerEnum = JwsSerializerEnum::Compact,
        ?int $signatureIndex = null,
    ): string {
        return $this->token ??= $this->jwsSerializerManagerDecorator->serialize(
            $jwsSerializerEnum->value,
            $this->jwsDecorator,
            $signatureIndex,
        );
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @return array<string,mixed>
     */
    public function getPayload(): array
    {
        if (is_array($this->payload)) {
            return $this->payload;
        }

        $payloadString = $this->jwsDecorator->jws()->getPayload();
        if (in_array($payloadString, [null, '', '0'], true)) {
            return $this->payload = [];
        }

        try {
            /** @var ?array<string,mixed> $payload */
            $payload = $this->helpers->json()->decode($payloadString);
            return $this->payload = is_array($payload) ? $payload : [];
        } catch (JsonException $jsonException) {
            throw new JwsException('Unable to decode JWS payload.', $jsonException->getCode(), $jsonException);
        }
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @phpstan-ignore missingType.iterableValue (JWKS array is validated later)
     */
    public function verifyWithKeySet(array $jwks, int $signatureIndex = 0): void
    {
        if (
            !$this->jwsVerifierDecorator->verifyWithKeySet(
                $this->jwsDecorator,
                $this->jwksDecoratorFactory->fromKeySetData($jwks),
                $signatureIndex,
            )
        ) {
            throw new JwsException('Could not verify JWS signature.');
        }
    }


    /**
     * @param mixed[] $key
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     *
     */
    public function verifyWithKey(array $key): void
    {
        $this->verifyWithKeySet([
            'keys' => [
                $key,
            ],
        ]);
    }


    /**
     * RFC 7519 section 4.1.1: "The "iss" value is a case-sensitive string containing a StringOrURI value." Section
     * 2 defines a StringOrURI as "A JSON string value, with the additional requirement that while arbitrary string
     * values MAY be used, any value containing a ":" character MUST be a URI [RFC3986]."
     *
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getIssuer(): ?string
    {
        $claimKey = ClaimsEnum::Iss->value;

        $iss = $this->getPayloadClaim($claimKey);

        return is_null($iss) ? null : $this->helpers->type()->enforceNonEmptyString($iss, $claimKey);
    }


    /**
     * RFC 7519 section 4.1.2: "The "sub" value is a case-sensitive string containing a StringOrURI value."
     *
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getSubject(): ?string
    {
        $claimKey = ClaimsEnum::Sub->value;

        $sub = $this->getPayloadClaim($claimKey);

        return is_null($sub) ? null : $this->helpers->type()->enforceNonEmptyString($sub, $claimKey);
    }


    /**
     * @return ?string[]
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getAudience(): ?array
    {
        $aud = $this->getPayloadClaim(ClaimsEnum::Aud->value);

        if (is_null($aud)) {
            return null;
        }

        if (is_array($aud)) {
            // Ensure string values.
            return $this->helpers->type()->ensureArrayWithValuesAsStrings($aud, ClaimsEnum::Aud->value);
        }

        if (is_string($aud)) {
            return [$aud];
        }

        throw new JwsException(sprintf('Invalid audience claim format: %s', var_export($aud, true)));
    }


    /**
     * RFC 7519 section 4.1.7: "The "jti" value is a case-sensitive string."
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\ClientAssertionException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @return ?non-empty-string
     */
    public function getJwtId(): ?string
    {
        $claimKey = ClaimsEnum::Jti->value;

        $jti = $this->getPayloadClaim($claimKey);

        return is_null($jti) ? null : $this->helpers->type()->enforceNonEmptyString($jti, $claimKey);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getExpirationTime(): ?int
    {
        $exp = $this->getPayloadClaim(ClaimsEnum::Exp->value);

        if (is_null($exp)) {
            return null;
        }

        // A NumericDate may carry a fraction of a second; the comparison keeps it, the returned value does not.
        // A float the integer conversion below cannot represent (infinite, or beyond 2 ** 53) is refused first:
        // it would compare as a far-future deadline and then be returned as 0.
        if (is_float($exp) && (!is_finite($exp) || abs($exp) > Type::MAX_NUMERIC_DATE)) {
            throw new JwsException(
                sprintf('Expiration Time claim is not a usable NumericDate: %s.', var_export($exp, true)),
            );
        }

        $deadline = is_float($exp) ? $exp : $this->helpers->type()->ensureInt($exp);
        $exp = $this->helpers->type()->ensureInt($exp);

        // RFC 7519 section 4.1.4 has the current time strictly before the expiration time (RFC 9068 section 4
        // repeats it for access tokens: "The current time MUST be before the time represented by the "exp"
        // claim."), so the second the deadline is reached, leeway included, the token is already expired.
        if (
            $this->shouldValidateExpirationTime() &&
            $deadline + $this->timestampValidationLeeway->getInSeconds() <= time()
        ) {
            throw new JwsException(sprintf('Expiration Time claim (%d) is not after current time.', $exp));
        }

        return $exp;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getNotBefore(): ?int
    {
        $nbf = $this->getPayloadClaim(ClaimsEnum::Nbf->value);

        if (is_null($nbf)) {
            return null;
        }

        $nbf = $this->helpers->type()->ensureInt($nbf);

        if ($nbf - $this->timestampValidationLeeway->getInSeconds() > time()) {
            throw new JwsException(sprintf('Not Before claim (%d) is higher than current time.', $nbf));
        }

        return $nbf;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getIssuedAt(): ?int
    {
        $iat = $this->getPayloadClaim(ClaimsEnum::Iat->value);

        if (is_null($iat)) {
            return null;
        }

        $iat = $this->helpers->type()->ensureInt($iat);

        if ($iat - $this->timestampValidationLeeway->getInSeconds() > time()) {
            throw new JwsException(sprintf('Issued At claim (%d) is greater than current time.', $iat));
        }

        return $iat;
    }


    /**
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getIdentifier(): ?string
    {
        $claimKey = ClaimsEnum::Id->value;

        $id = $this->getPayloadClaim($claimKey);

        return is_null($id) ? null : $this->helpers->type()->enforceNonEmptyString($id, $claimKey);
    }


    /**
     * RFC 7515 section 4.1.4: "The structure of the "kid" value is unspecified. Its value MUST be a case-sensitive
     * string."
     *
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getKeyId(): ?string
    {
        $claimKey = ClaimsEnum::Kid->value;

        $kid = $this->getHeaderClaim($claimKey);

        return is_null($kid) ? null : $this->helpers->type()->enforceNonEmptyString($kid, $claimKey);
    }


    /**
     * RFC 7515 section 4.1.9: a media type, which is a string.
     *
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getType(): ?string
    {
        $claimKey = ClaimsEnum::Typ->value;

        $typ = $this->getHeaderClaim($claimKey);

        return is_null($typ) ? null : $this->helpers->type()->enforceNonEmptyString($typ, $claimKey);
    }


    /**
     * RFC 7515 section 4.1.1: "The "alg" value is a case-sensitive ASCII string containing a StringOrURI value."
     * One this library does not know, and "none", are refused.
     *
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getAlgorithm(): ?string
    {
        $claimKey = ClaimsEnum::Alg->value;

        $alg = $this->getHeaderClaim($claimKey);

        if (is_null($alg)) {
            return null;
        }

        $alg = $this->helpers->type()->enforceNonEmptyString($alg, $claimKey);

        $algEnum = SignatureAlgorithmEnum::tryFrom($alg) ?? throw new JwsException(
            'Invalid Algorithm header claim.',
        );

        if ($algEnum->isNone()) {
            throw new JwsException('Invalid Algorithm header claim (none).');
        }

        return $alg;
    }
}
