<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Factories;

use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;
use SimpleSAML\OpenID\Federation\TrustMark;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;

class TrustMarkFactory extends ParsedJwsFactory
{
    public function fromToken(
        string $token,
        JwtTypesEnum $expectedJwtType = JwtTypesEnum::TrustMarkJwt,
    ): TrustMark {
        return new TrustMark(
            $this->jwsDecoratorBuilder->fromToken($token),
            $this->jwsVerifierDecorator,
            $this->jwksDecoratorFactory,
            $this->jwsSerializerManagerDecorator,
            $this->timestampValidationLeeway,
            $this->helpers,
            $this->claimFactory,
            $expectedJwtType,
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
        JwtTypesEnum $expectedJwtType = JwtTypesEnum::TrustMarkJwt,
    ): TrustMark {
        return new TrustMark(
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
            $expectedJwtType,
        );
    }
}
