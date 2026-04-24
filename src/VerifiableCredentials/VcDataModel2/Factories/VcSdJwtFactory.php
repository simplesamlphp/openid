<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Factories;

use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\HashAlgorithmsEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\SdJwt\DisclosureBag;
use SimpleSAML\OpenID\SdJwt\Factories\SdJwtFactory;
use SimpleSAML\OpenID\SdJwt\KbJwt;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\VcSdJwt;

class VcSdJwtFactory extends SdJwtFactory
{
    /**
     * @param array<non-empty-string,mixed> $payload
     * @param array<non-empty-string,mixed> $header
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function fromData(
        JwkDecorator $signingKey,
        SignatureAlgorithmEnum $signatureAlgorithm,
        array $payload,
        array $header,
        ?DisclosureBag $disclosureBag = null,
        ?KbJwt $kbJwt = null,
        JwtTypesEnum $jwtTypesEnum = JwtTypesEnum::VcSdJwt,
        HashAlgorithmsEnum $hashAlgorithmsEnum = HashAlgorithmsEnum::SHA_256,
    ): VcSdJwt {
        $header[ClaimsEnum::Typ->value] = $jwtTypesEnum->value;

        if ($disclosureBag instanceof DisclosureBag) {
            $payload = $this->updatePayloadWithDisclosures($payload, $disclosureBag, $hashAlgorithmsEnum);
        }

        /** @var array<non-empty-string,mixed> $payload */

        return new VcSdJwt(
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
            $disclosureBag,
            $kbJwt,
        );
    }
}
