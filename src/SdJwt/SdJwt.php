<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\SdJwt;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\HashAlgorithmsEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerEnum;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

/**
 * https://datatracker.ietf.org/doc/html/draft-ietf-oauth-selective-disclosure-jwt
 */
class SdJwt extends ParsedJws
{
    public const TILDE = '~';

    public function __construct(
        JwsDecorator $jwsDecorator,
        JwsVerifierDecorator $jwsVerifierDecorator,
        JwksDecoratorFactory $jwksDecoratorFactory,
        JwsSerializerManagerDecorator $jwsSerializerManagerDecorator,
        DateIntervalDecorator $timestampValidationLeeway,
        Helpers $helpers,
        ClaimFactory $claimFactory,
        protected readonly ?DisclosureBag $disclosureBag = null,
        protected readonly ?KbJwt $kbJwt = null,
    ) {
        parent::__construct(
            $jwsDecorator,
            $jwsVerifierDecorator,
            $jwksDecoratorFactory,
            $jwsSerializerManagerDecorator,
            $timestampValidationLeeway,
            $helpers,
            $claimFactory,
        );
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getSelectiveDisclosureAlgorithm(): ?HashAlgorithmsEnum
    {
        $claimKey = ClaimsEnum::_SdAlg->value;

        $_sdAlg = $this->getPayloadClaim($claimKey);

        return is_null($_sdAlg) ?
        null :
        HashAlgorithmsEnum::from($this->helpers->type()->ensureNonEmptyString($_sdAlg, $claimKey));
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @return ?mixed[]
     */
    public function getConfirmation(): ?array
    {
        $claimKey = ClaimsEnum::Cnf->value;

        $cnf = $this->getPayloadClaim($claimKey);

        return is_null($cnf) ?
        null :
        $this->helpers->type()->ensureArray($cnf, $claimKey);
    }

    public function getDisclosureBag(): ?DisclosureBag
    {
        return $this->disclosureBag;
    }

    public function getKbJwt(): ?KbJwt
    {
        return $this->kbJwt;
    }

    /**
     * @throws \JsonException
     */
    public function getDisclosedToken(
        JwsSerializerEnum $jwsSerializerEnum = JwsSerializerEnum::Compact,
        ?int $signatureIndex = null,
        ?DisclosureBag $disclosureBag = null,
    ): string {
        $token = $this->getToken($jwsSerializerEnum, $signatureIndex) . self::TILDE;

        $disclosures = $this->disclosureBag?->all() ?? [];

        if ($disclosureBag instanceof DisclosureBag) {
            $disclosures = array_filter(
                $disclosureBag->all(),
                fn (Disclosure $disclosure): bool => array_key_exists($disclosure->getSalt(), $disclosures),
            );
        }

        foreach ($disclosures as $disclosure) {
            $token .= $this->helpers->base64Url()->encode(
                $this->helpers->json()->encode($disclosure->jsonSerialize()),
            ) . self::TILDE;
        }

        if ($this->kbJwt instanceof KbJwt) {
            $token .= $this->kbJwt->getToken($jwsSerializerEnum, $signatureIndex);
        }

        return $token;
    }




    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    protected function validate(): void
    {
        $this->validateByCallbacks(
            $this->getSelectiveDisclosureAlgorithm(...),
        );
    }
}
