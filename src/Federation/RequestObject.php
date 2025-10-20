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
        return parent::getAudience() ?? throw new RequestObjectException('No Audience claim found.');
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\RequestObjectException
     * @return non-empty-string
     */
    public function getIssuer(): string
    {
        return parent::getIssuer() ?? throw new RequestObjectException('No Issuer claim found.');
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
        return parent::getJwtId() ?? throw new RequestObjectException('No JWT ID claim found.');
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getExpirationTime(): int
    {
        return parent::getExpirationTime() ?? throw new RequestObjectException('No Expiration Time claim found.');
    }


    /**
     * @return ?string[]
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\RequestObjectException
     */
    public function getTrustChain(): ?array
    {
        $claimKey = ClaimsEnum::TrustChain->value;
        $trustChain = $this->getPayloadClaim($claimKey) ?? null;

        if (is_null($trustChain)) {
            return null;
        }

        if (is_array($trustChain)) {
            return $this->helpers->type()->ensureArrayWithValuesAsNonEmptyStrings($trustChain, $claimKey);
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
        $this->validateByCallbacks(
            $this->getAudience(...),
            $this->getIssuer(...),
            $this->validateSubject(...),
            $this->getJwtId(...),
            $this->getExpirationTime(...),
            $this->getIssuedAt(...),
            $this->getTrustChain(...),
        );
    }
}
