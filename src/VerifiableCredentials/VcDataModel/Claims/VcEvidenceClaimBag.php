<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\ValueAbstracts\ClaimInterface;

class VcEvidenceClaimBag implements ClaimInterface
{
    /** @var \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcEvidenceClaimValue[] */
    protected array $vcEvidenceClaimValueValues;


    public function __construct(
        VcEvidenceClaimValue $vcEvidenceClaimValue,
        VcEvidenceClaimValue ...$vcEvidenceClaimValueValues,
    ) {
        $this->vcEvidenceClaimValueValues = [
            $vcEvidenceClaimValue,
            ...$vcEvidenceClaimValueValues,
        ];
    }


    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return array_map(
            fn(
                VcEvidenceClaimValue $vcEvidenceClaimValue,
            ): array => $vcEvidenceClaimValue->jsonSerialize(),
            $this->getValue(),
        );
    }


    public function getName(): string
    {
        return ClaimsEnum::Evidence->value;
    }


    /**
     * @return \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcEvidenceClaimValue[]
     */
    public function getValue(): array
    {
        return $this->vcEvidenceClaimValueValues;
    }
}
