<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use SimpleSAML\OpenID\Claims\ClaimInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

class VcRefreshServiceClaimBag implements ClaimInterface
{
    /** @var \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcRefreshServiceClaimValue[] */
    protected array $vcRefreshServiceClaimValueValues;


    public function __construct(
        VcRefreshServiceClaimValue $vcRefreshServiceClaimValue,
        VcRefreshServiceClaimValue ...$vcRefreshServiceClaimValueValues,
    ) {
        $this->vcRefreshServiceClaimValueValues = [
            $vcRefreshServiceClaimValue,
            ...$vcRefreshServiceClaimValueValues,
        ];
    }


    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return array_map(
            fn(
                VcRefreshServiceClaimValue $vcRefreshServiceClaimValue,
            ): array => $vcRefreshServiceClaimValue->jsonSerialize(),
            $this->getValue(),
        );
    }


    public function getName(): string
    {
        return ClaimsEnum::Refresh_Service->value;
    }


    /**
     * @return \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcRefreshServiceClaimValue[]
     */
    public function getValue(): array
    {
        return $this->vcRefreshServiceClaimValueValues;
    }
}
