<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use SimpleSAML\OpenID\Claims\ClaimInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

class VcTermsOfUseClaimBag implements ClaimInterface
{
    /** @var \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcTermsOfUseClaimValue[] */
    protected array $vcTermsOfUseClaimValueValues;


    public function __construct(
        VcTermsOfUseClaimValue $vcTermsOfUseClaimValue,
        VcTermsOfUseClaimValue ...$vcTermsOfUseClaimValueValues,
    ) {
        $this->vcTermsOfUseClaimValueValues = [
            $vcTermsOfUseClaimValue,
            ...$vcTermsOfUseClaimValueValues,
        ];
    }


    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return array_map(
            fn(
                VcTermsOfUseClaimValue $vcTermsOfUseClaimValue,
            ): array => $vcTermsOfUseClaimValue->jsonSerialize(),
            $this->getValue(),
        );
    }


    public function getName(): string
    {
        return ClaimsEnum::Terms_Of_Use->value;
    }


    /**
     * @return \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcTermsOfUseClaimValue[]
     */
    public function getValue(): array
    {
        return $this->vcTermsOfUseClaimValueValues;
    }
}
