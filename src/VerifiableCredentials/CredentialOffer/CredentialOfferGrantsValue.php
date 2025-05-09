<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer;

use JsonSerializable;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\GrantTypesEnum;
use SimpleSAML\OpenID\Exceptions\CredentialOfferException;

class CredentialOfferGrantsValue implements JsonSerializable
{
    /**
     * @throws \SimpleSAML\OpenID\Exceptions\CredentialOfferException
     * @param mixed[] $claims
     */
    public function __construct(
        public readonly string $type,
        public readonly array $claims = [],
    ) {
        // https://openid.net/specs/openid-4-verifiable-credential-issuance-1_0.html#name-credential-offer-parameters
        if ($this->type === GrantTypesEnum::AuthorizationCode->value) {
            if (
                isset($this->claims[ClaimsEnum::IssuerState->value]) &&
                !is_string($this->claims[ClaimsEnum::IssuerState->value])
            ) {
                throw new CredentialOfferException('Invalid Issuer State claim.');
            }

            if (
                isset($this->claims[ClaimsEnum::AuthorizationServer->value]) &&
                !is_string($this->claims[ClaimsEnum::AuthorizationServer->value])
            ) {
                throw new CredentialOfferException('Invalid Authorization Server claim.');
            }
        }

        if ($this->type === GrantTypesEnum::PreAuthorizedCode->value) {
            if (
                !isset($this->claims[ClaimsEnum::PreAuthorizedCode->value]) ||
                !is_string($this->claims[ClaimsEnum::PreAuthorizedCode->value])
            ) {
                throw new CredentialOfferException('Invalid Pre-authorized Code claim.');
            }

            if (isset($this->claims[ClaimsEnum::TxCode->value])) {
                if (!is_array($this->claims[ClaimsEnum::TxCode->value])) {
                    throw new CredentialOfferException('Invalid TxCode claim.');
                }

                if (
                    isset($this->claims[ClaimsEnum::TxCode->value][ClaimsEnum::InputMode->value]) &&
                    !in_array(
                        $this->claims[ClaimsEnum::TxCode->value][ClaimsEnum::InputMode->value],
                        ['numeric', 'text'],
                    )
                ) {
                    throw new CredentialOfferException('Invalid Transaction Code Input Mode claim.');
                }

                if (
                    isset($this->claims[ClaimsEnum::TxCode->value][ClaimsEnum::Length->value]) &&
                    !is_int($this->claims[ClaimsEnum::TxCode->value][ClaimsEnum::Length->value])
                ) {
                    throw new CredentialOfferException('Invalid Transaction Code Length claim.');
                }

                if (
                    isset($this->claims[ClaimsEnum::TxCode->value][ClaimsEnum::Description->value]) &&
                    !is_string($this->claims[ClaimsEnum::TxCode->value][ClaimsEnum::Description->value])
                ) {
                    throw new CredentialOfferException('Invalid Transaction Code Description claim.');
                }

                if (
                    isset($this->claims[ClaimsEnum::AuthorizationServer->value]) &&
                    !is_string($this->claims[ClaimsEnum::AuthorizationServer->value])
                ) {
                    throw new CredentialOfferException('Invalid Authorization Server claim.');
                }
            }
        }
    }

    /**
     * @return array<string, mixed[]>
     */
    public function jsonSerialize(): array
    {
        return [
            $this->type => $this->claims,
        ];
    }
}
