<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws;

use Jose\Component\Signature\JWS;
use JsonException;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Factories\JwksFactory;
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
            $this->jwksFactory->fromKeyData($jwks),
            $signatureIndex,
        ) || throw new JwsException('Could not verify JWS signature.');
    }
}
