<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\Factories;

use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\VerifiableCredentials\JwtVcJson;

class JwtVcJsonFactory extends ParsedJwsFactory
{
    public function fromData(): JwtVcJson
    {
    }
}