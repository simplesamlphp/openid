<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\RequestObjectException;
use SimpleSAML\OpenID\Jws\ParsedJws;

class RequestObject extends ParsedJws
{
    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\RequestObjectException
     * @return string[]
     */
    public function getAudience(): array
    {
        /** @psalm-suppress MixedAssignment */
        $aud = $this->getPayloadClaim(ClaimsEnum::Aud->value) ??
        throw new RequestObjectException('No audience claim found.');

        if (is_array($aud)) {
            return array_map(fn(mixed $val): string => (string)$val, $aud);
        }

        if (is_string($aud)) {
            return [$aud];
        }

        throw new RequestObjectException(sprintf('Invalid audience claim format: %s', var_export($aud, true)));
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\RequestObjectException
     */
    public function getIssuer(): string
    {
        return $this->ensureNonEmptyString(
            ($this->getPayloadClaim(ClaimsEnum::Iss->value) ??
                throw new RequestObjectException('No issuer claim found.')),
            ClaimsEnum::Iss->value,
        );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\RequestObjectException
     */
    protected function validateSubject(): void
    {
        if (
            array_key_exists(
                ClaimsEnum::Sub->value,
                $this->getPayload(),
            )
        ) {
            throw new RequestObjectException('Subject claim must not be present.');
        }
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\RequestObjectException
     * @return non-empty-string
     */
    public function getJwtId(): string
    {
        return $this->ensureNonEmptyString(
            ($this->getPayloadClaim(ClaimsEnum::Jti->value) ??
            throw new RequestObjectException('No JWT ID claim found.')),
            ClaimsEnum::Jti->value,
        );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getExpirationTime(): int
    {
        $exp = (int)($this->getPayloadClaim(ClaimsEnum::Exp->value) ??
            throw new RequestObjectException('No Expiration Time claim found.'));

        ($exp + $this->timestampValidationLeeway->inSeconds >= time()) ||
        throw new RequestObjectException("Expiration Time claim ($exp) is lesser than current time.");

        return $exp;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\RequestObjectException
     */
    public function getIssuedAt(): ?int
    {
        /** @psalm-suppress MixedAssignment */
        $iat = $this->getPayloadClaim(ClaimsEnum::Iat->value) ?? null;

        if (is_null($iat)) {
            return null;
        }

        $iat = (int)$iat;

        ($iat - $this->timestampValidationLeeway->inSeconds <= time()) ||
        throw new RequestObjectException("Issued At claim ($iat) is greater than current time.");

        return $iat;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\RequestObjectException
     */
    public function getTrustChain(): ?array
    {
        /** @psalm-suppress MixedAssignment */
        $trustChain = $this->getPayloadClaim(ClaimsEnum::TrustChain->value) ?? null;

        if (is_null($trustChain)) {
            return null;
        }

        if (is_array($trustChain)) {
            return $trustChain;
        }

        throw new RequestObjectException(
            sprintf('Unexpected trust chain claim format: %s', var_export($trustChain, true)),
        );
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\RequestObjectException
     */
    protected function validate(): void
    {
        $this->getAudience();
        $this->getIssuer();
        $this->validateSubject();
        $this->getJwtId();
        $this->getExpirationTime();
        $this->getIssuedAt();
        $this->getTrustChain();
    }
}
