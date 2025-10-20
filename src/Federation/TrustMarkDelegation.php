<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;
use SimpleSAML\OpenID\Exceptions\TrustMarkDelegationException;
use SimpleSAML\OpenID\Jws\ParsedJws;

class TrustMarkDelegation extends ParsedJws
{
    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkDelegationException
     */
    public function getIssuer(): string
    {
        return parent::getIssuer() ?? throw new TrustMarkDelegationException('No Issuer claim found.');
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkDelegationException
     */
    public function getSubject(): string
    {
        return parent::getSubject() ?? throw new TrustMarkDelegationException('No Subject claim found.');
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getTrustMarkType(): string
    {
        $claimKey = ClaimsEnum::TrustMarkType->value;

        $trustMarkType = $this->getPayloadClaim($claimKey) ?? throw new TrustMarkDelegationException(
            'No Trust Mark Type claim found.',
        );

        return $this->helpers->type()->ensureNonEmptyString($trustMarkType);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkDelegationException
     */
    public function getIssuedAt(): int
    {
        return parent::getIssuedAt() ?? throw new TrustMarkDelegationException('No Issued At claim found.');
    }


    /**
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getReference(): ?string
    {
        $ref = $this->getPayloadClaim(ClaimsEnum::Ref->value);

        return is_null($ref) ?
        null :
        $this->helpers->type()->ensureNonEmptyString($ref, ClaimsEnum::Ref->value);
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkDelegationException
     */
    public function getKeyId(): string
    {
        return parent::getKeyId() ?? throw new TrustMarkDelegationException('No KeyId header claim found.');
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkDelegationException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @return non-empty-string
     */
    public function getType(): string
    {
        $typ = parent::getType() ?? throw new TrustMarkDelegationException('No Type header claim found.');

        if ($typ !== JwtTypesEnum::TrustMarkDelegationJwt->value) {
            throw new TrustMarkDelegationException('Invalid Type header claim.');
        }

        return $typ;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkDelegationException
     */
    protected function validate(): void
    {
        $this->validateByCallbacks(
            $this->getIssuer(...),
            $this->getSubject(...),
            $this->getTrustMarkType(...),
            $this->getIssuedAt(...),
            $this->getExpirationTime(...),
            $this->getReference(...),
            $this->getKeyId(...),
            $this->getType(...),
        );
    }
}
