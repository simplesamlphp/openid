<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer;

/**
 * @see \SimpleSAML\Test\OpenID\VerifiableCredentials\CredentialOffer\CredentialOfferGrantsBagTest
 */
class CredentialOfferGrantsBag implements \JsonSerializable
{
    /**
     * @var \SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer\CredentialOfferGrantsValue[]
     */
    public readonly array $credentialOfferGrantsValues;


    public function __construct(CredentialOfferGrantsValue ...$credentialOfferGrantsValues)
    {
        $this->credentialOfferGrantsValues = $credentialOfferGrantsValues;
    }


    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $value = [];

        foreach ($this->credentialOfferGrantsValues as $credentialOfferGrantsValue) {
            $value = array_merge($value, $credentialOfferGrantsValue->jsonSerialize());
        }

        return $value;
    }
}
