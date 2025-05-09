<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\Factories;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\CredentialOfferException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer;
use SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer\CredentialOfferGrantsBag;
use SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer\CredentialOfferGrantsValue;
use SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer\CredentialOfferParameters;

class CredentialOfferFactory
{
    public function __construct(
        protected readonly Helpers $helpers,
    ) {
    }

    /**
     * @param mixed[]|null $parameters
     * @throws \SimpleSAML\OpenID\Exceptions\CredentialOfferException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function from(
        ?array $parameters = null,
        ?string $uri = null,
    ): CredentialOffer {
        if (
            ($parameters !== null && $uri !== null) ||
            ($parameters === null && $uri === null)
        ) {
            throw new CredentialOfferException('Only one of parameters or uri must be provided.');
        }

        if ($parameters !== null) {
            $credentialIssuer = $parameters[ClaimsEnum::CredentialIssuer->value] ?? null;
            $credentialIssuer = $this->helpers->type()->ensureNonEmptyString(
                $credentialIssuer,
                ClaimsEnum::CredentialIssuer->value,
            );

            $credentialConfigurationIds = $parameters[ClaimsEnum::CredentialConfigurationIds->value] ?? null;
            $credentialConfigurationIds = $this->helpers->type()->enforceNonEmptyArrayWithValuesAsNonEmptyStrings(
                $this->helpers->type()->ensureArray($credentialConfigurationIds),
                ClaimsEnum::CredentialConfigurationIds->value,
            );

            $grants = $parameters[ClaimsEnum::Grants->value] ?? null;
            $credentialOfferGrantsBag = null;

            if (is_array($grants)) {
                $credentialOfferGrantsValues = [];

                foreach ($grants as $type => $claims) {
                    if (!is_string($type) || !is_array($claims)) {
                        throw new CredentialOfferException('Invalid Grants claim.');
                    }

                    $credentialOfferGrantsValues[] = new CredentialOfferGrantsValue($type, $claims);
                }

                $credentialOfferGrantsBag = new CredentialOfferGrantsBag(...$credentialOfferGrantsValues);
            }

            return new CredentialOffer(
                credentialOfferParameters: new CredentialOfferParameters(
                    credentialIssuer: $credentialIssuer,
                    credentialConfigurationIds: $credentialConfigurationIds,
                    credentialOfferGrantsBag: $credentialOfferGrantsBag,
                ),
            );
        }

        if ($uri !== null) {
            return new CredentialOffer(
                uri: $uri,
            );
        }

        throw new CredentialOfferException('Invalid parameters or uri.');
    }
}
