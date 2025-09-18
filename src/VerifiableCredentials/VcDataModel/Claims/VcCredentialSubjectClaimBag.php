<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use SimpleSAML\OpenID\Claims\ClaimInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

class VcCredentialSubjectClaimBag implements ClaimInterface
{
    /** @var \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSubjectClaimValue[] */
    protected array $vcCredentialSubjectClaimValueValues;


    public function __construct(
        VcCredentialSubjectClaimValue $vcCredentialSubjectClaimValue,
        VcCredentialSubjectClaimValue ...$vcCredentialSubjectClaimValueValues,
    ) {
        $this->vcCredentialSubjectClaimValueValues = [
            $vcCredentialSubjectClaimValue,
            ...$vcCredentialSubjectClaimValueValues,
        ];
    }


    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return array_map(
            fn(
                VcCredentialSubjectClaimValue $vcCredentialSubjectClaimValue,
            ): array => $vcCredentialSubjectClaimValue->jsonSerialize(),
            $this->getValue(),
        );
    }


    public function getName(): string
    {
        return ClaimsEnum::Credential_Subject->value;
    }


    /**
     * @return \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSubjectClaimValue[]
     */
    public function getValue(): array
    {
        return $this->vcCredentialSubjectClaimValueValues;
    }
}
