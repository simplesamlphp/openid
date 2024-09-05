<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jwks;

use JsonSerializable;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\SignedJwksException;
use SimpleSAML\OpenID\Jws\ParsedJws;

class SignedJwks extends ParsedJws implements JsonSerializable
{
    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\SignedJwksException
     */
    public function getKeys(): array
    {
        $keys = $this->getPayloadClaim(ClaimsEnum::Keys->value) ??
        throw new SignedJwksException('No keys claim found.');

        if ((!is_array($keys)) || empty($keys)) {
            throw new SignedJwksException(
                sprintf('Unexpected JWKS keys claim format: %s.', var_export($keys, true)),
            );
        }

        return $keys;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\SignedJwksException
     * @return non-empty-string
     */
    public function getIssuer(): string
    {
        return $this->ensureNonEmptyString(
            ($this->getPayloadClaim(ClaimsEnum::Iss->value) ??
                throw new SignedJwksException('No issuer claim found.')),
            ClaimsEnum::Iss->value,
        );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\SignedJwksException
     * @return non-empty-string
     */
    public function getSubject(): string
    {
        return $this->ensureNonEmptyString(
            ($this->getPayloadClaim(ClaimsEnum::Sub->value) ??
                throw new SignedJwksException('No subject claim found.')),
            ClaimsEnum::Sub->value,
        );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\SignedJwksException
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
        throw new SignedJwksException("Issued At claim ($iat) is greater than current time.");

        return $iat;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\SignedJwksException
     */
    public function getExpirationTime(): ?int
    {
        /** @psalm-suppress MixedAssignment */
        $exp = $this->getPayloadClaim(ClaimsEnum::Exp->value) ?? null;

        if (is_null($exp)) {
            return null;
        }
        $exp = (int)$exp;

        ($exp + $this->timestampValidationLeeway->inSeconds >= time()) ||
        throw new SignedJwksException("Expiration Time claim ($exp) is lesser than current time.");

        return $exp;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\SignedJwksException
     */
    protected function validate(): void
    {
        $this->getKeys();
        $this->getIssuer();
        $this->getSubject();
        $this->getIssuedAt();
        $this->getExpirationTime();
    }

    public function jsonSerialize(): array
    {
        return [ClaimsEnum::Keys->value => $this->getKeys()];
    }
}
