<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jws;

use Jose\Component\Signature\JWS;
use JsonException;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Factories\JwksFactory;
use Throwable;

class ParsedJws
{
    protected JWS $jws;
    protected ?array $header = null;
    protected ?array $payload = null;

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function __construct(
        protected readonly string $token,
        protected readonly JwsParser $jwsParser,
        protected readonly JwsVerifier $jwsVerifier,
        protected readonly JwksFactory $jwksFactory,
    ) {
        $this->jws = $this->jwsParser->parse($this->token);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getHeader(): array
    {
        try {
            return $this->header ??= $this->jws->getSignature(0)->getProtectedHeader();
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

    public function getToken(): string
    {
        return $this->token;
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
}
