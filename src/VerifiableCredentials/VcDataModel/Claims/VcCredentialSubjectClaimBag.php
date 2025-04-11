<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use JsonSerializable;

class VcCredentialSubjectClaimBag implements JsonSerializable
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
            $this->vcCredentialSubjectClaimValueValues,
        );
    }
}
