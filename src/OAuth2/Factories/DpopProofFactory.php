<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\OAuth2\Factories;

use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;
use SimpleSAML\OpenID\Exceptions\DpopProofException;
use SimpleSAML\OpenID\Exceptions\InvalidValueException;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\OAuth2\DpopProof;

/**
 * @see \SimpleSAML\Test\OpenID\OAuth2\Factories\DpopProofFactoryTest
 */
class DpopProofFactory extends ParsedJwsFactory
{
    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsParseException When the token is not a JWS in any supported
     * serialization.
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException When it is one, and fails a check on construction.
     */
    public function fromToken(string $token): DpopProof
    {
        return new DpopProof(
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
     * Signs a DPoP proof. The "typ" and "jwk" header parameters are written by the factory, whatever the given
     * header carries: RFC 9449 section 4.2 has "typ" be "dpop+jwt", and "jwk" the public key of the key the
     * proof is signed with, which is what the recipient verifies the signature with. The public key has to be
     * one Helpers\Jwk::enforcePublicKey() accepts, so a symmetric key, whose public form would be the secret, is
     * refused before anything is signed. Only its required members go into the header ("kty" and the key
     * material, Helpers\Jwk::getRequiredMembers()): the signing key's "kid", "use", "key_ops" and the like
     * describe the private key, and a "key_ops" of ["sign"] would leave the proof unverifiable.
     *
     * The payload is the caller's: "jti", "htm", "htu" and "iat", and "ath" and "nonce" when they apply.
     *
     * @param array<non-empty-string,mixed> $payload
     * @param array<non-empty-string,mixed> $header
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromData(
        JwkDecorator $signingKey,
        SignatureAlgorithmEnum $signatureAlgorithm,
        array $payload,
        array $header = [],
    ): DpopProof {
        try {
            $publicKey = $this->helpers->jwk()->enforcePublicKey(
                $signingKey->jwk()->toPublic()->all(),
                ClaimsEnum::Jwk->value,
            );
        } catch (InvalidValueException $invalidValueException) {
            throw new DpopProofException(
                'The signing key has no public key a DPoP proof can carry.',
                (int)$invalidValueException->getCode(),
                $invalidValueException,
            );
        }

        $header[ClaimsEnum::Typ->value] = JwtTypesEnum::DpopJwt->value;
        $header[ClaimsEnum::Jwk->value] = $this->helpers->jwk()->getRequiredMembers($publicKey);

        return new DpopProof(
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
