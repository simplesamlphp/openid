<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

class CredentialOfferParameters implements \JsonSerializable
{
    /**
     * @param non-empty-string $credentialIssuer
     * @param string[] $credentialConfigurationIds
     */
    public function __construct(
        public readonly string $credentialIssuer,
        public readonly array $credentialConfigurationIds,
        public readonly ?CredentialOfferGrantsBag $credentialOfferGrantsBag,
    ) {
    }


    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $value = [
            ClaimsEnum::CredentialIssuer->value => $this->credentialIssuer,
            ClaimsEnum::CredentialConfigurationIds->value => $this->credentialConfigurationIds,
        ];

        if ($this->credentialOfferGrantsBag instanceof CredentialOfferGrantsBag) {
            $value[ClaimsEnum::Grants->value] = $this->credentialOfferGrantsBag->jsonSerialize();
        }

        return $value;
    }
}
