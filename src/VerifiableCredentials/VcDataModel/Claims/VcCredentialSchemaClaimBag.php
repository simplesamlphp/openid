<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\ValueAbstracts\ClaimInterface;

class VcCredentialSchemaClaimBag implements ClaimInterface
{
    /** @var \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSchemaClaimValue[] */
    protected array $vcCredentialSchemaClaimValueValues;


    public function __construct(
        VcCredentialSchemaClaimValue $vcCredentialSchemaClaimValue,
        VcCredentialSchemaClaimValue ...$vcCredentialSchemaClaimValueValues,
    ) {
        $this->vcCredentialSchemaClaimValueValues = [
            $vcCredentialSchemaClaimValue,
            ...$vcCredentialSchemaClaimValueValues,
        ];
    }


    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return array_map(
            fn(
                VcCredentialSchemaClaimValue $vcCredentialSchemaClaimValue,
            ): array => $vcCredentialSchemaClaimValue->jsonSerialize(),
            $this->getValue(),
        );
    }


    public function getName(): string
    {
        return ClaimsEnum::Credential_Schema->value;
    }


    /**
     * @return \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSchemaClaimValue[]
     */
    public function getValue(): array
    {
        return $this->vcCredentialSchemaClaimValueValues;
    }
}
