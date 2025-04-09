<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\Factories;

use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\VerifiableCredentials\JwtVcJson;

class JwtVcJsonFactory extends ParsedJwsFactory
{
    public function fromToken(string $token): JwtVcJson
    {
        return new JwtVcJson(
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
    ): JwtVcJson {
        return new JwtVcJson(
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
