<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use SimpleSAML\OpenID\Codebooks\ClaimNamesEnum;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Jws\ParsedJws;

class EntityStatement extends ParsedJws
{
    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getIssuer(): string
    {
        return (string)($this->getPayloadClaim(ClaimNamesEnum::Issuer->value) ??
            throw new JwsException('No issuer claim found.'));
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getSubject(): string
    {
        return (string)($this->getPayloadClaim(ClaimNamesEnum::Subject->value) ??
            throw new JwsException('No subject claim found.'));
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function isConfiguration(): bool
    {
        return $this->getIssuer() === $this->getSubject();
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getJwks(): array
    {
        $jwks = $this->getPayloadClaim(ClaimNamesEnum::JsonWebKeySet->value);

        if (is_array($jwks) && (!empty($jwks))) {
            return $jwks;
        }

        throw new JwsException('No jwks found.');
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function verifyWithKeySet(array $jwks = null, int $signatureIndex = 0): void
    {
        // Verify with provided JWKS, otherwise use own JWKS.
        $jwks ??= $this->getJwks();

        $this->jwsVerifier->verifyWithKeySet(
            $this->jws,
            $this->jwksFactory->fromKeyData($jwks),
            $signatureIndex,
        ) || throw new JwsException('Could not verify JWS signature.');
    }
}
