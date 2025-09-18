<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\TrustMarkException;
use SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimValue;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkDelegationFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkFactory;
use Throwable;

class TrustMarkValidator
{
    public function __construct(
        protected readonly TrustChainResolver $trustChainResolver,
        protected readonly TrustMarkFactory $trustMarkFactory,
        protected readonly TrustMarkDelegationFactory $trustMarkDelegationFactory,
        protected readonly DateIntervalDecorator $maxCacheDurationDecorator,
        protected readonly ?CacheDecorator $cacheDecorator = null,
        protected readonly ?LoggerInterface $logger = null,
    ) {
    }


    /**
     * If cached, validation has already been performed.
     *
     * @param non-empty-string $trustMarkType
     * @param non-empty-string $leafEntityId
     * @param non-empty-string $trustAnchorId
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function isValidationCachedFor(
        string $trustMarkType,
        string $leafEntityId,
        string $trustAnchorId,
    ): bool {
        if (is_null($this->cacheDecorator)) {
            $this->logger?->debug('Cache not available, skipping.');
            return false;
        }

        $this->logger?->debug(
            sprintf(
                'Checking cached Trust Mark %s validation for leaf entity %s under Trust Anchor %s.',
                $trustMarkType,
                $leafEntityId,
                $trustAnchorId,
            ),
        );

        if (
            !is_null($cachedValue = $this->cacheDecorator->get(
                null,
                $trustMarkType,
                $leafEntityId,
                $trustAnchorId,
            )) && $cachedValue === $trustMarkType
        ) {
            $this->logger?->debug(
                sprintf(
                    'Trust Mark %s cached validation found for leaf entity %s under Trust Anchor %s.',
                    $trustMarkType,
                    $leafEntityId,
                    $trustAnchorId,
                ),
            );
            return true;
        }

        $this->logger?->debug(
            sprintf(
                'Trust Mark %s validation not cached for leaf entity %s under Trust Anchor %s.',
                $trustMarkType,
                $leafEntityId,
                $trustAnchorId,
            ),
        );

        return false;
    }


    /**
     * @param non-empty-string $trustMarkType
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function fromCacheOrDoForTrustMarkType(
        string $trustMarkType,
        EntityStatement $leafEntityConfiguration,
        EntityStatement $trustAnchorEntityConfiguration,
        JwtTypesEnum $expectedJwtType = JwtTypesEnum::TrustMarkJwt,
    ): void {
        if (
            $this->isValidationCachedFor(
                $trustMarkType,
                $leafEntityConfiguration->getIssuer(),
                $trustAnchorEntityConfiguration->getIssuer(),
            )
        ) {
            return;
        }

        $this->doForTrustMarkType(
            $trustMarkType,
            $leafEntityConfiguration,
            $trustAnchorEntityConfiguration,
            $expectedJwtType,
        );
    }


    /**
     * @param non-empty-string $trustMarkType
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     */
    public function doForTrustMarkType(
        string $trustMarkType,
        EntityStatement $leafEntityConfiguration,
        EntityStatement $trustAnchorEntityConfiguration,
        JwtTypesEnum $expectedJwtType = JwtTypesEnum::TrustMarkJwt,
    ): void {
        $this->logger?->debug(
            sprintf(
                'Validating Trust Mark %s for leaf entity %s under Trust Anchor %s.',
                $trustMarkType,
                $leafEntityConfiguration->getIssuer(),
                $trustAnchorEntityConfiguration->getIssuer(),
            ),
        );

        $trustMarksClaimBag = $leafEntityConfiguration->getTrustMarks();

        if (is_null($trustMarksClaimBag)) {
            $error = sprintf(
                'Leaf entity %s does not have any Trust Marks available.',
                $leafEntityConfiguration->getIssuer(),
            );

            $this->logger?->error($error);
            throw new TrustMarkException($error);
        }

        $this->logger?->debug(
            sprintf(
                'Leaf entity %s has Trust Marks available, checking for Trust Mark %s.',
                $leafEntityConfiguration->getIssuer(),
                $trustMarkType,
            ),
            ['trustMarksClaimBag' => $trustMarksClaimBag->jsonSerialize()],
        );

        $trustMarksClaimValues = $trustMarksClaimBag->getAllFor($trustMarkType);

        if ($trustMarksClaimValues === []) {
            $error = sprintf(
                'Leaf entity %s has no claims for Trust Mark %s.',
                $leafEntityConfiguration->getIssuer(),
                $trustMarkType,
            );
            $this->logger?->debug($error);
            throw new TrustMarkException($error);
        }

        $this->logger?->debug(
            sprintf(
                'Leaf entity %s has %s claim/claims for Trust Mark %s.',
                $leafEntityConfiguration->getIssuer(),
                count($trustMarksClaimValues),
                $trustMarkType,
            ),
        );

        foreach ($trustMarksClaimValues as $idx => $trustMarksClaimValue) {
            $this->logger?->debug(
                sprintf(
                    'Validating Trust Mark %s using claim %s for leaf entity %s, under Trust Anchor %s.',
                    $trustMarkType,
                    $idx,
                    $leafEntityConfiguration->getIssuer(),
                    $trustAnchorEntityConfiguration->getIssuer(),
                ),
                ['trustMarkClaim' => $trustMarksClaimValue->jsonSerialize()],
            );

            try {
                $this->doForTrustMarksClaimValue(
                    $trustMarksClaimValue,
                    $leafEntityConfiguration,
                    $trustAnchorEntityConfiguration,
                    $expectedJwtType,
                );

                $this->logger?->debug(
                    sprintf(
                        'Trust Mark %s validated using claim %s for leaf entity %s, under Trust Anchor %s.',
                        $trustMarkType,
                        $idx,
                        $leafEntityConfiguration->getIssuer(),
                        $trustAnchorEntityConfiguration->getIssuer(),
                    ),
                );
                return;
            } catch (Throwable $exception) {
                $this->logger?->error(
                    sprintf(
                        'Trust Mark %s validation failed using claim %s for leaf entity %s, under Trust Anchor' .
                        ' %s. Error was %s. Trying next if available.',
                        $trustMarkType,
                        $idx,
                        $leafEntityConfiguration->getIssuer(),
                        $trustAnchorEntityConfiguration->getIssuer(),
                        $exception->getMessage(),
                    ),
                );
                continue;
            }
        }

        throw new TrustMarkException(
            sprintf(
                'Could not validate Trust Mark %s for leaf entity %s under Trust Anchor %s.',
                $trustMarkType,
                $leafEntityConfiguration->getIssuer(),
                $trustAnchorEntityConfiguration->getIssuer(),
            ),
        );
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\JwksException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function fromCacheOrDoForTrustMarksClaimValue(
        TrustMarksClaimValue $trustMarksClaimValue,
        EntityStatement $leafEntityConfiguration,
        EntityStatement $trustAnchorEntityConfiguration,
        JwtTypesEnum $expectedJwtType = JwtTypesEnum::TrustMarkJwt,
    ): void {
        if (
            $this->isValidationCachedFor(
                $trustMarksClaimValue->getTrustMarkType(),
                $leafEntityConfiguration->getIssuer(),
                $trustAnchorEntityConfiguration->getIssuer(),
            )
        ) {
            return;
        }

        $this->doForTrustMarksClaimValue(
            $trustMarksClaimValue,
            $leafEntityConfiguration,
            $trustAnchorEntityConfiguration,
            $expectedJwtType,
        );
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\JwksException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkDelegationException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     */
    public function doForTrustMarksClaimValue(
        TrustMarksClaimValue $trustMarksClaimValue,
        EntityStatement $leafEntityConfiguration,
        EntityStatement $trustAnchorEntityConfiguration,
        JwtTypesEnum $expectedJwtType = JwtTypesEnum::TrustMarkJwt,
    ): void {
        $trustMark = $this->validateTrustMarksClaimValue($trustMarksClaimValue, $expectedJwtType);

        $this->doForTrustMark(
            $trustMark,
            $leafEntityConfiguration,
            $trustAnchorEntityConfiguration,
        );
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     */
    public function validateTrustMarksClaimValue(
        TrustMarksClaimValue $trustMarksClaimValue,
        JwtTypesEnum $expectedJwtType = JwtTypesEnum::TrustMarkJwt,
    ): TrustMark {
        $this->logger?->debug(
            'Validating Trust Mark claim value.',
            [
                'type' => $trustMarksClaimValue->getTrustMarkType(),
                'trustMark' => $trustMarksClaimValue->getTrustMark(),
                'otherClaims' => $trustMarksClaimValue->getOtherClaims(),
            ],
        );

        $this->logger?->debug('Building Trust Mark instance.');

        $trustMark = $this->trustMarkFactory->fromToken(
            $trustMarksClaimValue->getTrustMark(),
            $expectedJwtType,
        );
        $trustMarkPayload = $trustMark->getPayload();

        $this->logger?->debug(
            'Trust Mark instance built.',
            ['trustMarkPayload' => $trustMarkPayload],
        );

        if ($trustMarksClaimValue->getTrustMarkType() !== $trustMark->getTrustMarkType()) {
            throw new TrustMarkException(
                sprintf(
                    'Invalid Trust Mark Type identifier: %s != %s.',
                    $trustMarksClaimValue->getTrustMarkType(),
                    $trustMark->getTrustMarkType(),
                ),
            );
        }

        // All the claims in the JSON object MUST have the same values as those contained in the Trust Mark JWT.
        $commonClaims = array_intersect_key($trustMarksClaimValue->getOtherClaims(), $trustMarkPayload);
        $this->logger?->debug(
            'Validating common values from Trust Mark instance and claim itself.',
            ['commonClaims' => $commonClaims],
        );

        foreach ($commonClaims as $key => $value) {
            if ($value !== $trustMarkPayload[$key]) {
                throw new TrustMarkException(
                    sprintf(
                        "Trust Mark Claim value for '%s' is different from Trust Mark JWT value: %s != %s",
                        $key,
                        var_export($value, true),
                        var_export($trustMarkPayload[$key], true),
                    ),
                );
            }
        }

        $this->logger?->debug('Trust Mark claim validated.');

        return $trustMark;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\JwksException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function fromCacheOrDoForTrustMark(
        TrustMark $trustMark,
        EntityStatement $leafEntityConfiguration,
        EntityStatement $trustAnchorEntityConfiguration,
    ): void {
        if (
            $this->isValidationCachedFor(
                $trustMark->getTrustMarkType(),
                $leafEntityConfiguration->getIssuer(),
                $trustAnchorEntityConfiguration->getIssuer(),
            )
        ) {
            return;
        }

        $this->doForTrustMark(
            $trustMark,
            $leafEntityConfiguration,
            $trustAnchorEntityConfiguration,
        );
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkDelegationException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     * @throws \SimpleSAML\OpenID\Exceptions\JwksException
     */
    public function doForTrustMark(
        TrustMark $trustMark,
        EntityStatement $leafEntityConfiguration,
        EntityStatement $trustAnchorEntityConfiguration,
    ): void {
        $this->logger?->debug(
            'Validating Trust Mark.',
            [
                'trustMarkPayload' => $trustMark->getPayload(),
                'leafEntityConfigurationPayload' => $leafEntityConfiguration->getPayload(),
                'trustAnchorEntityConfigurationPayload' => $trustAnchorEntityConfiguration->getPayload(),
            ],
        );

        $this->validateSubjectClaim($trustMark, $leafEntityConfiguration);

        // If Trust Mark Issuer is the Trust Anchor itself, we don't have to resolve chain, as Trust Anchor is trusted
        // out-of-band. Otherwise, we have to resolve trust for Trust Mark Issuer.
        $trustMarkIssuerEntityConfiguration =
        $trustMark->getIssuer() === $trustAnchorEntityConfiguration->getIssuer() ?
        $trustAnchorEntityConfiguration :
        $this->validateTrustChainForTrustMarkIssuer(
            $trustMark,
            $trustAnchorEntityConfiguration,
        )->getResolvedLeaf();

        $this->validateTrustMarkSignature($trustMark, $trustMarkIssuerEntityConfiguration);

        $this->validateTrustMarkDelegation($trustMark, $trustAnchorEntityConfiguration);

        $this->logger?->debug(sprintf(
            'Trust Mark %s validated for leaf entity %s under Trust Anchor %s.',
            $trustMark->getTrustMarkType(),
            $leafEntityConfiguration->getIssuer(),
            $trustAnchorEntityConfiguration->getIssuer(),
        ));

        if (!is_null($this->cacheDecorator)) {
            $expirationTime = $leafEntityConfiguration->getExpirationTime();
            $leafEntityConfigurationExpirationTime = $expirationTime;
            if (!is_null($trustMarkExpirationTime = $trustMark->getExpirationTime())) {
                $expirationTime = min(
                    $leafEntityConfigurationExpirationTime,
                    $trustMarkExpirationTime,
                );
            }

            $cacheTtl = $this->maxCacheDurationDecorator->lowestInSecondsComparedToExpirationTime($expirationTime);
            $this->logger?->debug(sprintf(
                'Caching Trust Mark %s validation for leaf entity %s under Trust Anchor %s with TTL %s.',
                $trustMark->getTrustMarkType(),
                $leafEntityConfiguration->getIssuer(),
                $trustAnchorEntityConfiguration->getIssuer(),
                $cacheTtl,
            ));
            try {
                $this->cacheDecorator->set(
                    $trustMark->getTrustMarkType(),
                    $cacheTtl,
                    $trustMark->getTrustMarkType(),
                    $leafEntityConfiguration->getIssuer(),
                    $trustAnchorEntityConfiguration->getIssuer(),
                );
            } catch (Throwable $exception) {
                $this->logger?->error(sprintf(
                    'Error caching Trust Mark %s validation for leaf entity %s under Trust Anchor %s with TTL' .
                    ' %s. Error wa: %s.',
                    $trustMark->getTrustMarkType(),
                    $leafEntityConfiguration->getIssuer(),
                    $trustAnchorEntityConfiguration->getIssuer(),
                    $cacheTtl,
                    $exception->getMessage(),
                ));
            }
        }
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     */
    public function validateSubjectClaim(
        TrustMark $trustMark,
        EntityStatement $leafEntityConfiguration,
    ): void {
        $this->logger?->debug('Validating Trust Mark subject.');

        if ($trustMark->getSubject() !== $leafEntityConfiguration->getIssuer()) {
            $error = sprintf(
                'Leaf entity %s is different than the subject %s of Trust Mark %s',
                $leafEntityConfiguration->getIssuer(),
                $trustMark->getSubject(),
                $trustMark->getTrustMarkType(),
            );
            $this->logger?->error($error);
            throw new TrustMarkException($error);
        }

        $this->logger?->debug(
            sprintf(
                'Leaf entity %s is the subject of the Trust Mark %s.',
                $leafEntityConfiguration->getIssuer(),
                $trustMark->getTrustMarkType(),
            ),
        );
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    public function validateTrustChainForTrustMarkIssuer(
        TrustMark $trustMark,
        EntityStatement $trustAnchorEntityConfiguration,
    ): TrustChain {
        $this->logger?->debug(
            sprintf(
                'Validating Trust Mark Issuer %s by resolving its Trust Chain with Trust Anchor %s.',
                $trustMark->getIssuer(),
                $trustAnchorEntityConfiguration->getIssuer(),
            ),
        );

        try {
            $trustMarkIssuerChain = $this->trustChainResolver->for(
                $trustMark->getIssuer(),
                [$trustAnchorEntityConfiguration->getIssuer()],
            )->getShortest();
        } catch (Throwable $throwable) {
            $error = sprintf(
                'Error resolving Trust Chain for Issuer %s using Trust Anchor %s. Error was: %s',
                $trustMark->getIssuer(),
                $trustAnchorEntityConfiguration->getIssuer(),
                $throwable->getMessage(),
            );

            $this->logger?->error($error);
            throw new TrustMarkException($error);
        }

        $this->logger?->debug(
            sprintf(
                'Successfully resolved Trust Chain for Issuer %s using Trust Anchor %s',
                $trustMark->getIssuer(),
                $trustAnchorEntityConfiguration->getIssuer(),
            ),
            ['resolvedTrustChainForIssuer' => $trustMarkIssuerChain->jsonSerialize()],
        );

        return $trustMarkIssuerChain;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\JwksException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     */
    public function validateTrustMarkSignature(
        TrustMark $trustMark,
        EntityStatement $trustMarkIssuerEntityConfiguration,
    ): void {
        $this->logger?->debug('Validating Trust Mark signature.');
        try {
            $trustMark->verifyWithKeySet($trustMarkIssuerEntityConfiguration->getJwks()->getValue());
        } catch (Throwable $throwable) {
            $error = sprintf(
                'Trust Mark signature validation failed with error: %s',
                $throwable->getMessage(),
            );
            $this->logger?->error(
                $error,
                ['trustMarkIssuerJwks' => $trustMarkIssuerEntityConfiguration->getJwks()],
            );
            throw new TrustMarkException($error);
        }

        $this->logger?->debug('Trust Mark signature validated.');
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkDelegationException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     */
    public function validateTrustMarkDelegation(
        TrustMark $trustMark,
        EntityStatement $trustAnchorEntityConfiguration,
    ): void {
        $this->logger?->debug('Validating Trust Mark delegation.');
        // If the Trust Mark Type identifier appears in the trust_mark_owners claim of the Trust Anchor's Entity
        // Configuration, verify that the Trust Mark contains a delegation claim.
        $trustMarkOwnersBag = $trustAnchorEntityConfiguration->getTrustMarkOwners();

        if (is_null($trustMarkOwnersBag)) {
            $this->logger?->debug(
                sprintf(
                    'Trust Anchor %s does not define Trust Mark Owners. Skipping delegation validation.',
                    $trustAnchorEntityConfiguration->getIssuer(),
                ),
            );
            return;
        }

        $this->logger?->debug(
            sprintf('Trust Anchor %s defines Trust Mark Owners.', $trustAnchorEntityConfiguration->getIssuer()),
            ['trustMarkOwners' => $trustMarkOwnersBag->jsonSerialize()],
        );

        $trustMarkOwnersClaimValue = $trustMarkOwnersBag->get($trustMark->getTrustMarkType());

        if (is_null($trustMarkOwnersClaimValue)) {
            $this->logger?->debug(
                sprintf(
                    'Trust Anchor %s does not define owner of Trust Mark %s. Skipping delegation validation.',
                    $trustAnchorEntityConfiguration->getIssuer(),
                    $trustMark->getTrustMarkType(),
                ),
            );
            return;
        }

        $this->logger?->debug(
            sprintf(
                'Trust Anchor %s defines owner %s of Trust Mark %s. Continuing delegation validation.',
                $trustAnchorEntityConfiguration->getIssuer(),
                $trustMarkOwnersClaimValue->getSubject(),
                $trustMark->getTrustMarkType(),
            ),
        );

        $trustMarkDelegationClaim = $trustMark->getDelegation();

        if (is_null($trustMarkDelegationClaim)) {
            $error = sprintf(
                'Trust Mark %s is missing a Delegation claim.',
                $trustMark->getTrustMarkType(),
            );
            $this->logger?->error($error);
            throw new TrustMarkException($error);
        }

        $this->logger?->debug(
            sprintf('Trust Mark %s has a Delegation claim.', $trustMark->getTrustMarkType()),
            ['trustMarkDelegationClaim' => $trustMarkDelegationClaim],
        );

        $trustMarkDelegation = $this->trustMarkDelegationFactory->fromToken($trustMarkDelegationClaim);

        // The signature of the delegation JWT MUST verify with a key from jwks claim.
        $this->logger?->debug('Validating Trust Mark Delegation signature.');
        try {
            $trustMarkDelegation->verifyWithKeySet($trustMarkOwnersClaimValue->getJwks()->getValue());
        } catch (Throwable $throwable) {
            $error = sprintf(
                'Trust Mark Delegation signature validation failed with error: %s',
                $throwable->getMessage(),
            );
            $this->logger?->error(
                $error,
                ['trustMarkOwnersJwks' => $trustMarkOwnersClaimValue->getJwks()->getValue()],
            );
            throw new TrustMarkException($error);
        }

        $this->logger?->debug('Trust Mark Delegation signature validated.');

        // The issuer of the delegation JWT MUST match the sub value in this set of claims.
        $this->logger?->debug('Validating Trust Mark Delegation Issuer claim.');
        if ($trustMarkDelegation->getIssuer() !== $trustMarkOwnersClaimValue->getSubject()) {
            $error = sprintf(
                'Trust Mark Delegation Issuer claim validation failed. Value was %s, but expected %s.',
                $trustMarkDelegation->getIssuer(),
                $trustMarkOwnersClaimValue->getSubject(),
            );

            $this->logger?->error($error);
            throw new TrustMarkException($error);
        }

        $this->logger?->debug('Trust Mark Delegation Issuer claim validated.');

        // The subject of the delegation JWT MUST match the iss value in the Trust Mark.
        $this->logger?->debug('Validating Trust Mark Issuer claim.');
        if ($trustMark->getIssuer() !== $trustMarkDelegation->getSubject()) {
            $error = sprintf(
                'Trust Mark Issuer claim validation failed. Value was %s, but expected %s.',
                $trustMark->getIssuer(),
                $trustMarkDelegation->getSubject(),
            );

            $this->logger?->error($error);
            throw new TrustMarkException($error);
        }

        $this->logger?->debug('Trust Mark Issuer claim validated.');

        // The Type of the delegation JWT MUST match the Type value in the Trust Mark.
        $this->logger?->debug('Validating Trust Mark Type claim.');
        if ($trustMark->getTrustMarkType() !== $trustMarkDelegation->getTrustMarkType()) {
            $error = sprintf(
                'Trust Mark Type claim validation failed. Value was %s, but expected %s.',
                $trustMark->getTrustMarkType(),
                $trustMarkDelegation->getTrustMarkType(),
            );

            $this->logger?->error($error);
            throw new TrustMarkException($error);
        }

        $this->logger?->debug('Trust Mark Type claim validated.');

        $this->logger?->debug('Trust Mark delegation validated.');
    }
}
