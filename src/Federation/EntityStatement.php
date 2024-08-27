<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypeEnum;
use SimpleSAML\OpenID\Exceptions\EntityStatementException;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Jws\ParsedJws;
use Throwable;

class EntityStatement extends ParsedJws
{
    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getIssuer(): string
    {
        return (string)($this->getPayloadClaim(ClaimsEnum::Iss->value) ??
            throw new EntityStatementException('No issuer claim found.'));
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getSubject(): string
    {
        return (string)($this->getPayloadClaim(ClaimsEnum::Sub->value) ??
            throw new EntityStatementException('No subject claim found.'));
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getIssuedAt(): int
    {
        $iat = (int)($this->getPayloadClaim(ClaimsEnum::Iat->value) ??
            throw new EntityStatementException('No Issued At claim found.'));

        ($iat - $this->timestampValidationLeeway->inSeconds <= time()) ||
        throw new EntityStatementException("Issued At claim ($iat) is greater than current time.");

        return $iat;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getExpirationTime(): int
    {
        $exp = (int)($this->getPayloadClaim(ClaimsEnum::Exp->value) ??
            throw new EntityStatementException('No Expiration Time claim found.'));

        ($exp + $this->timestampValidationLeeway->inSeconds >= time()) ||
        throw new EntityStatementException("Expiration Time claim ($exp) is lesser than current time.");

        return $exp;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getJwks(): array
    {
        /** @psalm-suppress MixedAssignment We check the type manually. */
        $jwks = $this->getPayloadClaim(ClaimsEnum::Jwks->value);

        if (is_array($jwks) && (!empty($jwks))) {
            return $jwks;
        }

        throw new JwsException('No jwks found.');
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getType(): string
    {
        $typ = (string)($this->getHeaderClaim(ClaimsEnum::Typ->value) ??
            throw new EntityStatementException('No Type header claim found.'));

        if ($typ !== JwtTypeEnum::EntityStatementJwt->value) {
            throw new EntityStatementException('Invalid type claim.');
        }

        return $typ;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getKeyId(): string
    {
        return (string)($this->getHeaderClaim(ClaimsEnum::Kid->value) ??
            throw new EntityStatementException('No Key ID header claim found.'));
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function isConfiguration(): bool
    {
        return $this->getIssuer() === $this->getSubject();
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function verifyWithKeySet(array $jwks = null, int $signatureIndex = 0): void
    {
        // Verify with provided JWKS, otherwise use own JWKS.
        $jwks ??= $this->getJwks();

        parent::verifyWithKeySet($jwks, $signatureIndex);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     */
    protected function validate(): void
    {
        $calls = [
            $this->getIssuer(...),
            $this->getSubject(...),
            $this->getIssuedAt(...),
            $this->getExpirationTime(...),
            $this->getJwks(...),
            $this->getType(...),
            $this->getKeyId(...),
        ];

        $errors = [];

        foreach ($calls as $call) {
            try {
                call_user_func($call);
            } catch (Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        if (!empty($errors)) {
            throw new EntityStatementException('Entity statement not valid: ' . implode('; ', $errors));
        }
    }
}
