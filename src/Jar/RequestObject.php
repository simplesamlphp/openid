<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jar;

use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\ParamsEnum;
use SimpleSAML\OpenID\Exceptions\RequestObjectException;
use SimpleSAML\OpenID\Jws\ParsedJws;

class RequestObject extends ParsedJws
{
    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\RequestObjectException
     * @return non-empty-string
     */
    public function getClientId(): string
    {
        $claimKey = ClaimsEnum::ClientId->value;
        $clientId = $this->getPayloadClaim($claimKey);

        if (is_null($clientId)) {
            throw new RequestObjectException('No Client ID claim found.');
        }

        return $this->helpers->type()->ensureNonEmptyString($clientId, $claimKey);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\RequestObjectException
     */
    protected function validateAlgorithm(): void
    {
        if (is_null($alg = $this->getAlgorithm())) {
            throw new RequestObjectException('Missing Algorithm header claim.');
        }

        $signatureAlgorithm = SignatureAlgorithmEnum::tryFrom($alg) ?? throw new RequestObjectException(
            'Invalid Algorithm header claim.',
        );

        if ($signatureAlgorithm === SignatureAlgorithmEnum::none) {
            throw new RequestObjectException('Algorithm header claim must not be "none".');
        }
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\RequestObjectException
     */
    protected function validateForbiddenRequestObjectParams(): void
    {
        foreach ([ParamsEnum::Request, ParamsEnum::RequestUri] as $param) {
            if (array_key_exists($param->value, $this->getPayload())) {
                throw new RequestObjectException(
                    sprintf('Request Object must not contain %s.', $param->value),
                );
            }
        }
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function validate(): void
    {
        $this->validateByCallbacks(
            $this->getClientId(...),
            $this->validateAlgorithm(...),
            $this->validateForbiddenRequestObjectParams(...),
        );
    }
}
