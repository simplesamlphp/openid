<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials;

use SimpleSAML\OpenID\Exceptions\CredentialOfferException;
use SimpleSAML\OpenID\VerifiableCredentials\CredentialOffer\CredentialOfferParameters;

class CredentialOffer implements \JsonSerializable
{
    public function __construct(
        protected readonly ?CredentialOfferParameters $credentialOfferParameters = null,
        protected readonly ?string $uri = null,
    ) {
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\CredentialOfferException
     * @return string|mixed[]
     */
    public function jsonSerialize(): string|array
    {
        if ($this->credentialOfferParameters instanceof CredentialOfferParameters) {
            return $this->credentialOfferParameters->jsonSerialize();
        }

        if ($this->uri !== null) {
            return $this->uri;
        }

        throw new CredentialOfferException('Invalid parameters or uri.');
    }
}
