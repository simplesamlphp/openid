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
     * Get Algorithm. Overridden to allow the 'none' algorithm.
     *
     * @return ?non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getAlgorithm(): ?string
    {
        $claimKey = ClaimsEnum::Alg->value;

        $alg = $this->getHeaderClaim($claimKey);

        if (is_null($alg)) {
            throw new RequestObjectException('Missing Algorithm header claim.');
        }

        $alg = $this->helpers->type()->ensureNonEmptyString($alg, $claimKey);

        SignatureAlgorithmEnum::tryFrom($alg) ?? throw new RequestObjectException(
            'Invalid Algorithm header claim.',
        );

        return $alg;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\RequestObjectException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function isProtected(): bool
    {
        return $this->getAlgorithm() !== SignatureAlgorithmEnum::none->value;
    }
}
