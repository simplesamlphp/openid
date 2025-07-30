<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\SdJwt\Factories;

use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\HashAlgorithmsEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;
use SimpleSAML\OpenID\Codebooks\SdJwtDisclosureType;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\SdJwt\DisclosureBag;
use SimpleSAML\OpenID\SdJwt\KbJwt;
use SimpleSAML\OpenID\SdJwt\SdJwt;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

class SdJwtFactory extends ParsedJwsFactory
{
    public function __construct(
        JwsDecoratorBuilder $jwsDecoratorBuilder,
        JwsVerifierDecorator $jwsVerifierDecorator,
        JwksDecoratorFactory $jwksDecoratorFactory,
        JwsSerializerManagerDecorator $jwsSerializerManagerDecorator,
        DateIntervalDecorator $timestampValidationLeeway,
        Helpers $helpers,
        ClaimFactory $claimFactory,
        protected readonly DisclosureFactory $disclosureFactory,
    ) {
        parent::__construct(
            $jwsDecoratorBuilder,
            $jwsVerifierDecorator,
            $jwksDecoratorFactory,
            $jwsSerializerManagerDecorator,
            $timestampValidationLeeway,
            $helpers,
            $claimFactory,
        );
    }

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
        JwtTypesEnum $jwtTypesEnum = JwtTypesEnum::ExampleSdJwt,
        HashAlgorithmsEnum $hashAlgorithmsEnum = HashAlgorithmsEnum::SHA_256,
    ): SdJwt {
        $header[ClaimsEnum::Typ->value] = $jwtTypesEnum->value;

        if ($disclosureBag instanceof DisclosureBag) {
            $payload = $this->updatePayloadWithDisclosures($payload, $disclosureBag, $hashAlgorithmsEnum);
        }

        /** @var array<non-empty-string,mixed> $payload */

        return new SdJwt(
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

    /**
     * @param array<non-empty-string,mixed> $payload
     * @return array<non-empty-string,mixed>
     * @throws \SimpleSAML\OpenID\Exceptions\SdJwtException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function updatePayloadWithDisclosures(
        array $payload,
        DisclosureBag $disclosureBag,
        HashAlgorithmsEnum $hashAlgorithmsEnum,
        int $minDecoys = 1,
        int $maxDecoys = 5,
    ): array {
        $disclosures = $disclosureBag->all();

        if ($disclosures === []) {
            return $payload;
        }

        $payload[ClaimsEnum::_SdAlg->value] = $hashAlgorithmsEnum->ianaName();

        $disclosurePaths = [];

        foreach ($disclosures as $disclosure) {
            $disclosurePath = $disclosure->getPath();

            $disclosureType = $disclosure->getType();

            if ($disclosureType === SdJwtDisclosureType::ObjectProperty) {
                $disclosurePath = [...$disclosure->getPath(), ClaimsEnum::_Sd->value];
            }

            if (!in_array($disclosurePath, $disclosurePaths, true)) {
                $disclosurePaths[] = $disclosurePath;
            }

            $this->helpers->arr()->addNestedValue(
                $payload,
                $disclosure->getDigestRepresentation(),
                ...$disclosurePath,
            );

            // Randomly add decoys.
            if (random_int(0, 1) !== 0) {
                $disclosurePathReference =& $this->helpers->arr()->getNestedValueReference(
                    $payload,
                    ...$disclosurePath,
                );

                if (is_array($disclosurePathReference)) {
                    $disclosureDecoy = $this->disclosureFactory->buildDecoy(
                        $disclosureType,
                        $disclosurePath,
                        $hashAlgorithmsEnum,
                        $disclosureBag->salts(),
                    );

                    // Make sure that we never add duplicates.
                    if (!in_array($disclosureDecoy->getDigestRepresentation(), $disclosurePathReference, true)) {
                        $disclosurePathReference[] = $disclosureDecoy->getDigestRepresentation();
                    }

                    if ($disclosureType === SdJwtDisclosureType::ObjectProperty) {
                        shuffle($disclosurePathReference);
                    }
                }
            }
        }

        /** @var array<non-empty-string, mixed> $payload */

        return $payload;
    }
}
