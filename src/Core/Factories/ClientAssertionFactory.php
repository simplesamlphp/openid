<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Core\Factories;

use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Core\ClientAssertion;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;

class ClientAssertionFactory extends ParsedJwsFactory
{
    public function fromToken(string $token): ClientAssertion
    {
        return new ClientAssertion(
            $this->jwsDecoratorBuilder->fromToken($token),
            $this->jwsVerifierDecorator,
            $this->jwksDecoratorFactory,
            $this->jwsSerializerManagerDecorator,
            $this->timestampValidationLeeway,
            $this->helpers,
            $this->claimFactory,
        );
    }

    /**
     * @param array<non-empty-string,mixed> $payload
     * @param array<non-empty-string,mixed> $header
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromData(
        JwkDecorator $signingKey,
        SignatureAlgorithmEnum $signatureAlgorithm,
        array $payload,
        array $header,
    ): ClientAssertion {
        return new ClientAssertion(
            $this->jwsDecoratorBuilder->fromData(
                $signingKey,
                $signatureAlgorithm,
                $payload,
                $header,
            ),
            $this->jwsVerifierDecorator,
            $this->jwksDecoratorFactory,
            $this->jwsSerializerManagerDecorator,
            $this->timestampValidationLeeway,
            $this->helpers,
            $this->claimFactory,
        );
    }
}
