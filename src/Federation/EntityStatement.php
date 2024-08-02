<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use SimpleSAML\OpenID\Codebooks\ClaimNamesEnum;
use SimpleSAML\OpenID\Codebooks\ClaimValues\TypeEnum;
use SimpleSAML\OpenID\Exceptions\EntityStatementException;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Jws\JwsVerifier;
use SimpleSAML\OpenID\Jws\ParsedJws;

class EntityStatement extends ParsedJws
{

    public function __construct(
        string $token,
        JwsParser $jwsParser,
        JwsVerifier $jwsVerifier,
        JwksFactory $jwksFactory,
    ) {
        parent::__construct($token, $jwsParser, $jwsVerifier, $jwksFactory);

        $this->validate();
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getIssuer(): string
    {
        return (string)($this->getPayloadClaim(ClaimNamesEnum::Issuer->value) ??
            throw new EntityStatementException('No issuer claim found.'));
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getSubject(): string
    {
        return (string)($this->getPayloadClaim(ClaimNamesEnum::Subject->value) ??
            throw new EntityStatementException('No subject claim found.'));
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getIssuedAt(): int
    {
        $iat = (int)($this->getPayloadClaim(ClaimNamesEnum::IssuedAt->value) ??
            throw new EntityStatementException('No Issued At claim found.'));

        ($iat <= time()) || throw new EntityStatementException('Issued At claim is greater than current time.');

        return $iat;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getExpirationTime(): int
    {
        $exp = (int)($this->getPayloadClaim(ClaimNamesEnum::ExpirationTime->value) ??
            throw new EntityStatementException('No Expiration Time claim found.'));

        ($exp >= time()) || throw new EntityStatementException('Expiration Time claim is greater than current time.');

        return $exp;
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
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getType(): string
    {
        $typ = (string)($this->getHeaderClaim(ClaimNamesEnum::Type->value) ??
            throw new EntityStatementException('No Type header claim found.'));

        if ($typ !== TypeEnum::EntityStatementJwt->value) {
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
        return (string)($this->getHeaderClaim(ClaimNamesEnum::KeyId->value) ??
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

        $this->jwsVerifier->verifyWithKeySet(
            $this->jws,
            $this->jwksFactory->fromKeyData($jwks),
            $signatureIndex,
        ) || throw new JwsException('Could not verify JWS signature.');
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
            } catch (\Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        if (!empty($errors)) {
            throw new EntityStatementException('Entity statement not valid: ' . implode('; ', $errors));
        }
    }
}
