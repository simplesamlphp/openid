<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jwks;

use JsonSerializable;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;
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
        $keys = $this->getPayloadClaim(ClaimsEnum::Keys->value) ?? throw new SignedJwksException(
            'No keys claim found.',
        );

        if ((!is_array($keys)) || $keys === []) {
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
        return parent::getIssuer() ?? throw new SignedJwksException('No Issuer claim found.');
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\SignedJwksException
     * @return non-empty-string
     */
    public function getSubject(): string
    {
        return parent::getSubject() ?? throw new SignedJwksException('No Subject claim found.');
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\SignedJwksException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @return non-empty-string
     */
    public function getType(): string
    {
        $typ = parent::getType() ?? throw new SignedJwksException('No Type header claim found.');

        if ($typ !== JwtTypesEnum::JwkSetJwt->value) {
            throw new SignedJwksException('Invalid Type header claim.');
        }

        return $typ;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function validate(): void
    {
        $this->validateByCallbacks(
            $this->getKeys(...),
            $this->getIssuer(...),
            $this->getSubject(...),
            $this->getIssuedAt(...),
            $this->getExpirationTime(...),
            $this->getType(...),
        );
    }

    public function jsonSerialize(): array
    {
        return [ClaimsEnum::Keys->value => $this->getKeys()];
    }
}
