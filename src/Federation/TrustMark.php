<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\TrustMarkException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

class TrustMark extends ParsedJws
{
    public function __construct(
        JwsDecorator $jwsDecorator,
        JwsVerifierDecorator $jwsVerifierDecorator,
        JwksFactory $jwksFactory,
        JwsSerializerManagerDecorator $jwsSerializerManagerDecorator,
        DateIntervalDecorator $timestampValidationLeeway,
        Helpers $helpers,
        ClaimFactory $claimFactory,
        protected readonly JwtTypesEnum $expectedJwtType = JwtTypesEnum::TrustMarkJwt,
    ) {
        parent::__construct(
            $jwsDecorator,
            $jwsVerifierDecorator,
            $jwksFactory,
            $jwsSerializerManagerDecorator,
            $timestampValidationLeeway,
            $helpers,
            $claimFactory,
        );
    }

    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     */
    public function getIssuer(): string
    {
        return parent::getIssuer() ?? throw new TrustMarkException('No Issuer claim found.');
    }

    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     */
    public function getSubject(): string
    {
        return parent::getSubject() ?? throw new TrustMarkException('No Subject claim found.');
    }

    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getTrustMarkType(): string
    {
        $claimKey = ClaimsEnum::TrustMarkType->value;

        $trustMarkType = $this->getPayloadClaim($claimKey) ?? throw new TrustMarkException(
            'No Trust Mark Type claim found.',
        );

        return $this->helpers->type()->ensureNonEmptyString($trustMarkType);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     */
    public function getIssuedAt(): int
    {
        return parent::getIssuedAt() ?? throw new TrustMarkException('No Issued At claim found.');
    }

    /**
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getLogoUri(): ?string
    {
        $logoUri = $this->getPayloadClaim(ClaimsEnum::LogoUri->value);

        return is_null($logoUri) ?
        null :
        $this->helpers->type()->ensureNonEmptyString($logoUri, ClaimsEnum::LogoUri->value);
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
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getDelegation(): ?string
    {
        $delegation = $this->getPayloadClaim(ClaimsEnum::Delegation->value);

        return is_null($delegation) ?
        null :
        $this->helpers->type()->ensureNonEmptyString($delegation, ClaimsEnum::Delegation->value);
    }

    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     */
    public function getKeyId(): string
    {
        return parent::getKeyId() ?? throw new TrustMarkException('No KeyId header claim found.');
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @return non-empty-string
     */
    public function getType(): string
    {
        $typ = parent::getType() ?? throw new TrustMarkException('No Type header claim found.');

        if ($typ !== $this->expectedJwtType->value) {
            throw new TrustMarkException(sprintf(
                'Invalid Type header claim. Expected %s, got %s.',
                $this->expectedJwtType->value,
                $typ,
            ));
        }

        return $typ;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     */
    protected function validate(): void
    {
        $this->validateByCallbacks(
            $this->getIssuer(...),
            $this->getSubject(...),
            $this->getTrustMarkType(...),
            $this->getIssuedAt(...),
            $this->getLogoUri(...),
            $this->getExpirationTime(...),
            $this->getReference(...),
            $this->getDelegation(...),
            $this->getKeyId(...),
            $this->getType(...),
        );
    }
}
