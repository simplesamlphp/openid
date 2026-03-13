<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\ValueAbstracts\ClaimInterface;

class VcRefreshServiceClaimBag implements ClaimInterface
{
    /** @var \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims\VcRefreshServiceClaimValue[] */
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
     * @return \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims\VcRefreshServiceClaimValue[]
     */
    public function getValue(): array
    {
        return $this->vcRefreshServiceClaimValueValues;
    }
}
