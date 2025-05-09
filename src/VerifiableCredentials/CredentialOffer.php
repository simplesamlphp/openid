<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials;

use SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer\CredentialOfferParameters;

class CredentialOffer
{
    public function __construct(
        protected readonly ?CredentialOfferParameters $credentialOfferParameters = null,
        protected readonly ?string $uri = null,
    ) {
    }
}
