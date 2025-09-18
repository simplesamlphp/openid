<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\SdJwtVc;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;
use SimpleSAML\OpenID\Exceptions\EntityStatementException;
use SimpleSAML\OpenID\Exceptions\SdJwtVcException;
use SimpleSAML\OpenID\SdJwt\Disclosure;
use SimpleSAML\OpenID\SdJwt\DisclosureBag;
use SimpleSAML\OpenID\SdJwt\SdJwt;

class SdJwtVc extends SdJwt
{
    /**
     * As per https://datatracker.ietf.org/doc/html/draft-ietf-oauth-sd-jwt-vc#section-3.2.2.2
     * @var string[]
     */
    public const NON_SELECTIVELY_DISCLOSABLE_CLAIMS = [
        ClaimsEnum::Iss->value,
        ClaimsEnum::Nbf->value,
        ClaimsEnum::Exp->value,
        ClaimsEnum::Cnf->value,
        ClaimsEnum::Vct->value,
        ClaimsEnum::Status->value,
    ];


    public function getType(): string
    {
        $typ = parent::getType() ?? throw new SdJwtVcException('No Type header claim found.');

        if (!in_array($typ, [JwtTypesEnum::DcSdJwt->value, JwtTypesEnum::VcSdJwt->value], true)) {
            throw new EntityStatementException('Invalid Type header claim.');
        }

        return $typ;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\SdJwtVcException
     */
    public function getVerifiableCredentialType(): string
    {
        $claimKey = ClaimsEnum::Vct->value;

        ($vct = $this->getPayloadClaim($claimKey)) ?? throw new SdJwtVcException(
            'No Verifiable Credential Type claim found.',
        );

        return $this->helpers->type()->ensureNonEmptyString($vct, $claimKey);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\SdJwtVcException
     */
    protected function ensureNonSelectivelyDisclosableClaims(): void
    {
        if (! $this->disclosureBag instanceof DisclosureBag) {
            return;
        }

        $disclosureNames = array_filter(array_map(
            fn (Disclosure $disclosure): ?string => $disclosure->getName(),
            $this->disclosureBag->all(),
        ));

        $nonDisclosableClaims = array_intersect($disclosureNames, self::NON_SELECTIVELY_DISCLOSABLE_CLAIMS);

        if ($nonDisclosableClaims !== []) {
            throw new SdJwtVcException(
                sprintf(
                    'The following claims are not selectively disclosable: %s',
                    implode(', ', $nonDisclosableClaims),
                ),
            );
        }
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\SdJwtVcException
     */
    protected function ensureNoSdClaimWhenNoDisclosures(): void
    {
        if (
            $this->disclosureBag instanceof DisclosureBag &&
            ($this->disclosureBag->all() !== [])
        ) {
            return;
        }

        if (
            $this->helpers->arr()->containsKey(
                $this->getPayload(),
                ClaimsEnum::_Sd->value,
            )
        ) {
            throw new SdJwtVcException(
                'The _Sd claim is not allowed when no disclosures are specified.',
            );
        }
    }


    protected function validate(): void
    {
        $this->validateByCallbacks(
            $this->getType(...),
            $this->getVerifiableCredentialType(...),
            $this->ensureNonSelectivelyDisclosableClaims(...),
            $this->ensureNoSdClaimWhenNoDisclosures(...),
        );
    }
}
