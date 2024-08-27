<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Core;

use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\RequestObjectException;
use SimpleSAML\OpenID\Jws\ParsedJws;

class RequestObject extends ParsedJws
{
    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\RequestObjectException
     */
    public function isProtected(): bool
    {
        $algHeader = (string)($this->getHeaderClaim(ClaimsEnum::Alg->value) ??
            throw new RequestObjectException('Alg header is missing.'));

        return $algHeader !== SignatureAlgorithmEnum::none->value;
    }
}
