<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\CredentialFormatIdentifiersEnum;
use SimpleSAML\OpenID\Exceptions\JwtVcJsonException;
use SimpleSAML\OpenID\Jws\ParsedJws;

class JwtVcJson extends ParsedJws implements VerifiableCredentialInterface
{
    public function getCredentialFormatIdentifier(): CredentialFormatIdentifiersEnum
    {
        return CredentialFormatIdentifiersEnum::JwtVcJson;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\JwtVcJsonException
     * @return mixed[]
     */
    public function getVc(): array
    {
        $claimKey = ClaimsEnum::Vc->value;

        $vc = $this->getPayloadClaim($claimKey) ?? throw new JwtVcJsonException('No vc claim found.');

        if (is_array($vc)) {
            return $vc;
        }

        throw new JwtVcJsonException('Invalid vc claim.');
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function validate(): void
    {
        $this->validateByCallbacks(
            $this->getVc(...),
        );
    }
}
