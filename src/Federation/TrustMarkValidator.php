<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Exceptions\TrustMarkException;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkDelegationFactory;
use Throwable;

class TrustMarkValidator
{
    public function __construct(
        protected readonly TrustChainResolver $trustChainResolver,
        protected readonly TrustMarkDelegationFactory $trustMarkDelegationFactory,
        protected readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustMarkException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    public function for(
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

        // TODO mivanci continue Validate delegation
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

    public function validateTrustMarkSignature(
        TrustMark $trustMark,
        EntityStatement $trustMarkIssuerEntityStatement,
    ): void {
        $this->logger?->debug('Validating Trust Mark signature.');
        try {
            $trustMark->verifyWithKeySet($trustMarkIssuerEntityStatement->getJwks());
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
}
