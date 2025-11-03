<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws;

use JsonException;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Serializers\JwsSerializerEnum;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use Throwable;

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
        protected readonly JwksFactory $jwksFactory,
        protected readonly JwsSerializerManagerDecorator $jwsSerializerManagerDecorator,
        protected readonly DateIntervalDecorator $timestampValidationLeeway,
        protected readonly Helpers $helpers,
        protected readonly ClaimFactory $claimFactory,
    ) {
        $this->validate();
    }


    protected function validate(): void
    {
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
                $this->jwksFactory->fromKeyData($jwks),
                $signatureIndex,
            )
        ) {
            throw new JwsException('Could not verify JWS signature.');
        }
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @return ?non-empty-string
     */
    public function getIssuer(): ?string
    {
        $claimKey = ClaimsEnum::Iss->value;

        $iss = $this->getPayloadClaim($claimKey);

        return is_null($iss) ?
        null :
        $this->helpers->type()->ensureNonEmptyString($iss, ClaimsEnum::Iss->value);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @return ?non-empty-string
     */
    public function getSubject(): ?string
    {
        $claimKey = ClaimsEnum::Sub->value;

        $sub = $this->getPayloadClaim($claimKey);

        return is_null($sub) ? null : $this->helpers->type()->ensureNonEmptyString($sub, $claimKey);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @return ?string[]
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
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\ClientAssertionException
     * @return ?non-empty-string
     */
    public function getJwtId(): ?string
    {
        $claimKey = ClaimsEnum::Jti->value;

        $jti = $this->getPayloadClaim($claimKey);

        return is_null($jti) ? null : $this->helpers->type()->ensureNonEmptyString($jti, $claimKey);
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

        $exp = $this->helpers->type()->ensureInt($exp);

        if ($exp + $this->timestampValidationLeeway->getInSeconds() < time()) {
            throw new JwsException(sprintf('Expiration Time claim (%d) is lesser than current time.', $exp));
        }

        return $exp;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
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
     */
    public function getIdentifier(): ?string
    {
        $claimKey = ClaimsEnum::Id->value;

        $id = $this->getPayloadClaim($claimKey);

        return is_null($id) ?
        null :
        $this->helpers->type()->ensureNonEmptyString($id, $claimKey);
    }


    /**
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getKeyId(): ?string
    {
        $claimKey = ClaimsEnum::Kid->value;

        $kid = $this->getHeaderClaim($claimKey);

        return is_null($kid) ? null : $this->helpers->type()->ensureNonEmptyString($kid, $claimKey);
    }


    /**
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getType(): ?string
    {
        $claimKey = ClaimsEnum::Typ->value;

        $typ = $this->getHeaderClaim($claimKey);

        return is_null($typ) ? null : $this->helpers->type()->ensureNonEmptyString($typ, $claimKey);
    }
}
