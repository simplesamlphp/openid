<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;
use SimpleSAML\OpenID\Exceptions\TrustMarkStatusException;
use SimpleSAML\OpenID\Jws\ParsedJws;

class TrustMarkStatusResponse extends ParsedJws
{
    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkStatusException
     */
    public function getIssuer(): string
    {
        return parent::getIssuer() ?? throw new TrustMarkStatusException('No Issuer claim found.');
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkStatusException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getIssuedAt(): int
    {
        return parent::getIssuedAt() ?? throw new TrustMarkStatusException('No Issued At claim found.');
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkStatusException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getTrustMark(): string
    {
        $trustMark = $this->getPayloadClaim(ClaimsEnum::TrustMark->value);

        if (is_null($trustMark)) {
            throw new TrustMarkStatusException('No Trust Mark claim found.');
        }

        return $this->helpers->type()->ensureNonEmptyString($trustMark);
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkStatusException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getStatus(): string
    {
        $status = $this->getPayloadClaim(ClaimsEnum::Status->value);

        if (is_null($status)) {
            throw new TrustMarkStatusException('No Status claim found.');
        }

        return $this->helpers->type()->ensureNonEmptyString($status);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @return non-empty-string
     */
    public function getType(): string
    {
        $typ = parent::getType() ?? throw new TrustMarkStatusException('No Type header claim found.');

        if ($typ !== JwtTypesEnum::TrustMarkStatusResponseJwt->value) {
            throw new TrustMarkStatusException('Invalid Type header claim.');
        }

        return $typ;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkStatusException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @return non-empty-string
     */
    public function getKeyId(): string
    {
        return parent::getKeyId() ?? throw new TrustMarkStatusException('No KeyId header claim found.');
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     */
    protected function validate(): void
    {
        $this->validateByCallbacks(
            $this->getIssuer(...),
            $this->getIssuedAt(...),
            $this->getTrustMark(...),
            $this->getStatus(...),
            $this->getType(...),
            $this->getKeyId(...),
        );
    }
}
