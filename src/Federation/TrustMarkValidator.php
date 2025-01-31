<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
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
        protected readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    public function forTrustMarksClaimValue(
        TrustMarksClaimValue $trustMarksClaimValue,
        EntityStatement $leafEntityConfiguration,
        EntityStatement $trustAnchorEntityConfiguration,
    ): void {

        $trustMark = $this->validateTrustMarksClaimValue($trustMarksClaimValue);

        $this->forTrustMark(
            $trustMark,
            $leafEntityConfiguration,
            $trustAnchorEntityConfiguration,
        );
    }

    public function validateTrustMarksClaimValue(
        TrustMarksClaimValue $trustMarksClaimValue,
    ): TrustMark {
        $this->logger?->debug(
            'Validating Trust Mark claim value.',
            [
                'id' => $trustMarksClaimValue->getTrustMarkId(),
                'trustMark' => $trustMarksClaimValue->getTrustMark(),
                'otherClaims' => $trustMarksClaimValue->getOtherClaims(),
            ],
        );

        $this->logger?->debug('Building Trust Mark instance.');

        $trustMark = $this->trustMarkFactory->fromToken($trustMarksClaimValue->getTrustMark());
        $trustMarkPayload = $trustMark->getPayload();

        $this->logger?->debug(
            'Trust Mark instance built.',
            ['trustMarkPayload' => $trustMarkPayload],
        );

        // TODO mivanci Enable this check once the testbed starts conforming.
//        if ($trustMarksClaimValue->getId() !== $trustMark->getIdentifier()) {
//            throw new TrustMarkException(
//                sprintf(
//                    'Invalid TrustMark identifier: %s != %s.',
//                    $trustMarksClaimValue->getId(),
//                    $trustMark->getIdentifier(),
//                ),
//            );
//        }

        // All the claims in the JSON object MUST have the same values as those contained in the Trust Mark JWT.
        $commonClaims = array_intersect_key($trustMarksClaimValue->getOtherClaims(), $trustMarkPayload);
        $this->logger?->debug(
            'Validating common values from Trust Mark instance and claim itselt.',
            ['commonClaims' => $commonClaims],
        );

        foreach ($commonClaims as $key => $value) {
            if ($value !== $trustMarkPayload[$key]) {
                throw new TrustMarkException(
                    sprintf(
                        "Trust Mark Claim value for '$key' is different from Trust Mark JWT value: %s != %s",
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
     */
    public function forTrustMark(
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

        $trustMarkIssuerTrustChain = $this->validateTrustChainForTrustMarkIssuer(
            $trustMark,
            $trustAnchorEntityConfiguration,
        );

        $trustMarkIssuerEntityStatement = $trustMarkIssuerTrustChain->getResolvedLeaf();

        $this->validateTrustMarkSignature($trustMark, $trustMarkIssuerEntityStatement);

        $this->validateTrustMarkDelegation($trustMark, $trustAnchorEntityConfiguration);

        $this->logger?->debug('Trust Mark validated.');
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
                'Leaf entity ID %s is different than the subject %s of Trust Mark %s',
                $leafEntityConfiguration->getIssuer(),
                $trustMark->getSubject(),
                $trustMark->getIdentifier(),
            );
            $this->logger?->error($error);
            throw new TrustMarkException($error);
        }

        $this->logger?->debug(
            sprintf(
                'Leaf entity ID %s is the subject of the Trust Mark %s.',
                $leafEntityConfiguration->getIssuer(),
                $trustMark->getIdentifier(),
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
        } catch (Throwable $exception) {
            $error = sprintf(
                'Error resolving Trust Chain for Issuer %s using Trust Anchor %s. Error was: %s',
                $trustMark->getIssuer(),
                $trustAnchorEntityConfiguration->getIssuer(),
                $exception->getMessage(),
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
        EntityStatement $trustMarkIssuerEntityStatement,
    ): void {
        $this->logger?->debug('Validating Trust Mark signature.');
        try {
            $trustMark->verifyWithKeySet($trustMarkIssuerEntityStatement->getJwks()->getValue());
        } catch (Throwable $exception) {
            $error = sprintf(
                'Trust Mark signature validation failed with error: %s',
                $exception->getMessage(),
            );
            $this->logger?->error(
                $error,
                ['trustMarkIssuerJwks' => $trustMarkIssuerEntityStatement->getJwks()],
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
        // If the Trust Mark identifier appears in the trust_mark_owners claim of the Trust Anchor's Entity
        // Configuration, verify that the Trust Mark contains a delegation claim.
        $trustMarkOwnersBag = $trustAnchorEntityConfiguration->getTrustMarkOwners();

        if (is_null($trustMarkOwnersBag)) {
            $this->logger?->debug(
                sprintf(
                    'Trust Anchor %s does not define Trust Mark Owners, skipping delegation validation.',
                    $trustAnchorEntityConfiguration->getIssuer(),
                ),
            );
            return;
        }

        $this->logger?->debug(
            sprintf('Trust Anchor %s defines Trust Mark Owners.', $trustAnchorEntityConfiguration->getIssuer()),
            ['trustMarkOwners' => $trustMarkOwnersBag->jsonSerialize()],
        );

        $trustMarkOwnersClaimValue = $trustMarkOwnersBag->get($trustMark->getIdentifier());

        if (is_null($trustMarkOwnersClaimValue)) {
            $this->logger?->debug(
                sprintf(
                    'Trust Anchor %s does not define owner of Trust Mark %s. Skipping delegation validation.',
                    $trustAnchorEntityConfiguration->getIssuer(),
                    $trustMark->getIdentifier(),
                ),
            );
            return;
        }

        $this->logger?->debug(
            sprintf(
                'Trust Anchor %s defines owner %s of Trust Mark %s. Continuing delegation validation.',
                $trustAnchorEntityConfiguration->getIssuer(),
                $trustMarkOwnersClaimValue->getSubject(),
                $trustMark->getIdentifier(),
            ),
        );

        $trustMarkDelegationClaim = $trustMark->getDelegation();

        if (is_null($trustMarkDelegationClaim)) {
            $error = sprintf(
                'Trust Mark %s is missing a Delegation claim.',
                $trustMark->getIdentifier(),
            );
            $this->logger?->error($error);
            throw new TrustMarkException($error);
        }

        $this->logger?->debug(
            sprintf('Trust Mark %s has a Delegation claim.', $trustMark->getIdentifier()),
            ['trustMarkDelegationClaim' => $trustMarkDelegationClaim],
        );

        $trustMarkDelegation = $this->trustMarkDelegationFactory->fromToken($trustMarkDelegationClaim);

        // The signature of the delegation JWT MUST verify with a key from jwks claim.
        $this->logger?->debug('Validating Trust Mark Delegation signature.');
        try {
            $trustMarkDelegation->verifyWithKeySet($trustMarkOwnersClaimValue->getJwks()->getValue());
        } catch (Throwable $exception) {
            $error = sprintf(
                'Trust Mark Delegation signature validation failed with error: %s',
                $exception->getMessage(),
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

        // The ID of the delegation JWT MUST match the id value in the Trust Mark.
        $this->logger?->debug('Validating Trust Mark ID claim.');
        if ($trustMark->getIdentifier() !== $trustMarkDelegation->getIdentifier()) {
            $error = sprintf(
                'Trust Mark ID claim validation failed. Value was %s, but expected %s.',
                $trustMark->getIdentifier(),
                $trustMarkDelegation->getIdentifier(),
            );

            $this->logger?->error($error);
            throw new TrustMarkException($error);
        }
        $this->logger?->debug('Trust Mark ID claim validated.');

        $this->logger?->debug('Trust Mark delegation validated.');
    }
}
