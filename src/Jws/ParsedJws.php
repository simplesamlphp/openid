<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws;

use Jose\Component\Signature\JWS;
use JsonException;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Serializers\JwsSerializerEnum;
use SimpleSAML\OpenID\Serializers\JwsSerializerManager;
use Throwable;

class ParsedJws
{
    protected JWS $jws;
    protected ?array $header = null;
    protected ?array $payload = null;

    protected ?string $token = null;

    public function __construct(
        JwsDecorator $jwsDecorator,
        protected readonly JwsVerifier $jwsVerifier,
        protected readonly JwksFactory $jwksFactory,
        protected readonly JwsSerializerManager $jwsSerializerManager,
        protected readonly DateIntervalDecorator $timestampValidationLeeway,
    ) {
        $this->jws = $jwsDecorator->jws;
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
                $errors[] = sprintf('%s: %s', get_class($exception), $exception->getMessage());
            }
        }

        if (!empty($errors)) {
            throw new JwsException('JWS not valid: ' . implode('; ', $errors));
        }
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @return non-empty-string
     */
    protected function ensureNonEmptyString(mixed $value, string $description): string
    {
        $value = (string)$value;

        if (empty($value)) {
            $message = "Empty string value encountered: $description";
            throw new JwsException($message);
        }

        return $value;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getHeader(int $signatureId = 0): array
    {
        try {
            return $this->header ??= $this->jws->getSignature($signatureId)->getProtectedHeader();
        } catch (Throwable $exception) {
            throw new JwsException('Unable to get protected header.', (int)$exception->getCode(), $exception);
        }
    }

    /**
     * @param non-empty-string $key
     * @return mixed
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
        return $this->token ??= $this->jwsSerializerManager->serialize(
            $jwsSerializerEnum->value,
            $this->jws,
            $signatureIndex,
        );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getPayload(): array
    {
        if (is_array($this->payload)) {
            return $this->payload;
        }

        $payloadString = $this->jws->getPayload();
        /** @psalm-suppress RiskyTruthyFalsyComparison */
        if (empty($payloadString)) {
            return $this->payload = [];
        }

        try {
            /** @var ?array $payload */
            $payload = json_decode($payloadString, true, 512, JSON_THROW_ON_ERROR);
            return $this->payload = is_array($payload) ? $payload : [];
        } catch (JsonException $exception) {
            throw new JwsException('Unable to decode JWS payload.', $exception->getCode(), $exception);
        }
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function verifyWithKeySet(array $jwks, int $signatureIndex = 0): void
    {
        $this->jwsVerifier->verifyWithKeySet(
            $this->jws,
            $this->jwksFactory->fromKeyData($jwks)->jwks,
            $signatureIndex,
        ) || throw new JwsException('Could not verify JWS signature.');
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @return ?non-empty-string
     */
    public function getIssuer(): ?string
    {
        /** @psalm-suppress MixedAssignment */
        $iss = $this->getPayloadClaim(ClaimsEnum::Iss->value);

        if (is_null($iss)) {
            return null;
        }

        return $this->ensureNonEmptyString($iss, ClaimsEnum::Iss->value);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @return ?non-empty-string
     */
    public function getSubject(): ?string
    {
        /** @psalm-suppress MixedAssignment */
        $sub = $this->getPayloadClaim(ClaimsEnum::Sub->value);

        if (is_null($sub)) {
            return null;
        }

        return $this->ensureNonEmptyString($sub, ClaimsEnum::Sub->value);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @return ?string[]
     */
    public function getAudience(): ?array
    {
        /** @psalm-suppress MixedAssignment */
        $aud = $this->getPayloadClaim(ClaimsEnum::Aud->value);

        if (is_null($aud)) {
            return null;
        }

        if (is_array($aud)) {
            // Ensure string values.
            return array_map(fn(mixed $val): string => (string)$val, $aud);
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
        /** @psalm-suppress MixedAssignment */
        $jti = $this->getPayloadClaim(ClaimsEnum::Jti->value);

        if (is_null($jti)) {
            return null;
        }

        return $this->ensureNonEmptyString($jti, ClaimsEnum::Jti->value);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getExpirationTime(): ?int
    {
        /** @psalm-suppress MixedAssignment */
        $exp = $this->getPayloadClaim(ClaimsEnum::Exp->value);

        if (is_null($exp)) {
            return null;
        }

        $exp = (int)$exp;

        ($exp + $this->timestampValidationLeeway->inSeconds >= time()) ||
        throw new JwsException("Expiration Time claim ($exp) is lesser than current time.");

        return $exp;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getIssuedAt(): ?int
    {
        /** @psalm-suppress MixedAssignment */
        $iat = $this->getPayloadClaim(ClaimsEnum::Iat->value);

        if (is_null($iat)) {
            return null;
        }

        $iat = (int)$iat;

        ($iat - $this->timestampValidationLeeway->inSeconds <= time()) ||
        throw new JwsException("Issued At claim ($iat) is greater than current time.");

        return $iat;
    }
}
