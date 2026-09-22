<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\OAuth2\Factories;

use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\OAuth2\JwtAccessToken;

/**
 * @see \SimpleSAML\Test\OpenID\OAuth2\Factories\JwtAccessTokenFactoryTest
 */
class JwtAccessTokenFactory extends ParsedJwsFactory
{
    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromToken(string $token): JwtAccessToken
    {
        return new JwtAccessToken(
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
     * Signs a JWT access token. The "typ" header is written by the factory, whatever the given header carries:
     * RFC 9068 section 2.1 has the value "at+jwt" as the one to use ("Therefore, the "typ" value used SHOULD be
     * "at+jwt"."), and a token this factory signs is one that declares itself as this profile.
     *
     * @param array<non-empty-string,mixed> $payload
     * @param array<non-empty-string,mixed> $header
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromData(
        JwkDecorator $signingKey,
        SignatureAlgorithmEnum $signatureAlgorithm,
        array $payload,
        array $header,
    ): JwtAccessToken {
        $header[ClaimsEnum::Typ->value] = JwtTypesEnum::AtJwt->value;

        return new JwtAccessToken(
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
