<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\ValueAbstracts\ClaimInterface;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialStatusClaimValue;

class VcCredentialStatusClaimBag implements ClaimInterface
{
    /** @var \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialStatusClaimValue[] */
    protected array $vcCredentialStatusClaimValueValues;


    public function __construct(
        VcCredentialStatusClaimValue $vcCredentialStatusClaimValue,
        VcCredentialStatusClaimValue ...$vcCredentialStatusClaimValueValues,
    ) {
        $this->vcCredentialStatusClaimValueValues = [
            $vcCredentialStatusClaimValue,
            ...$vcCredentialStatusClaimValueValues,
        ];
    }


    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return array_map(
            fn(
                VcCredentialStatusClaimValue $vcCredentialStatusClaimValue,
            ): array => $vcCredentialStatusClaimValue->jsonSerialize(),
            $this->getValue(),
        );
    }


    public function getName(): string
    {
        return ClaimsEnum::Credential_Status->value;
    }


    /**
     * @return \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialStatusClaimValue[]
     */
    public function getValue(): array
    {
        return $this->vcCredentialStatusClaimValueValues;
    }
}
