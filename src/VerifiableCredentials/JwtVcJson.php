<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials;

use SimpleSAML\OpenID\Codebooks\CredentialFormatIdentifiersEnum;
use SimpleSAML\OpenID\Jws\ParsedJws;

class JwtVcJson extends ParsedJws implements VerifiableCredentialInterface
{
    public function getCredentialFormatIdentifier(): CredentialFormatIdentifiersEnum
    {
        return CredentialFormatIdentifiersEnum::JwtVcJson;
    }
}