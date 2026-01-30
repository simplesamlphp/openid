<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Utils;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Exceptions\OpenIdException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPair;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPairBag;

class KeyPairResolver
{
    public function __construct(
        protected readonly Helpers $helpers,
        protected readonly ?LoggerInterface $logger = null,
    ) {
    }


    /**
     * @param mixed[] $receiverEntityMetadata
     * @param mixed[] $senderEntityMetadata
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function resolveSignatureKeyPairByAlgorithm(
        SignatureKeyPairBag $signatureKeyPairBag,
        array $receiverEntityMetadata = [],
        array $senderEntityMetadata = [],
        ?string $receiverDesignatedSignatureAlgorithmMetadataKey = null,
        ?string $receiverSupportedSignatureAlgorithmsMetadataKey = null,
        ?string $senderDesignatedSignatureAlgorithmMetadataKey = null,
        ?string $senderSupportedSignatureAlgorithmsMetadataKey = null,
    ): SignatureKeyPair {
        $signatureKeyPair = $signatureKeyPairBag->getFirstOrFail();
        $this->logger?->debug(
            'Default Signature Key Pair: ',
            [
                'algorithm' => $signatureKeyPair->getSignatureAlgorithm()->value,
                'keyId' => $signatureKeyPair->getKeyPair()->getKeyId(),
            ],
        );

        $targetAlgorithms = [];

        // Designated algorithms take precedence.
        if (
            is_string($receiverDesignatedSignatureAlgorithmMetadataKey) &&
            array_key_exists($receiverDesignatedSignatureAlgorithmMetadataKey, $receiverEntityMetadata) &&
            is_string($receiverDesignatedAlg = $receiverEntityMetadata[
            $receiverDesignatedSignatureAlgorithmMetadataKey
            ])
        ) {
            $this->logger?->debug('Receiver designated signature algorithm: ' . $receiverDesignatedAlg);
            $targetAlgorithms['receiver'] = $receiverDesignatedAlg;
        }

        if (
            is_string($senderDesignatedSignatureAlgorithmMetadataKey) &&
            array_key_exists($senderDesignatedSignatureAlgorithmMetadataKey, $senderEntityMetadata) &&
            is_string($senderDesignatedAlg = $senderEntityMetadata[$senderDesignatedSignatureAlgorithmMetadataKey])
        ) {
            $this->logger?->debug('Sender designated signature algorithm: ' . $senderDesignatedAlg);
            $targetAlgorithms['sender'] = $senderDesignatedAlg;
        }

        // If both sides have designated algorithms, they MUST match.
        if (count($targetAlgorithms) === 2 && $targetAlgorithms['receiver'] !== $targetAlgorithms['sender']) {
            $this->logger?->error(
                'Conflict in designated signature algorithms between receiver and sender.',
                $targetAlgorithms,
            );
            throw new OpenIdException('Conflict in designated signature algorithms between receiver and sender.');
        }

        if ($targetAlgorithms !== []) {
            $algorithm = reset($targetAlgorithms);
            return $signatureKeyPairBag->getFirstByAlgorithmOrFail(
                SignatureAlgorithmEnum::from($algorithm),
            );
        }

        // No designated algorithm, check supported ones.
        $commonlySupportedAlgorithms = $signatureKeyPairBag->getAllAlgorithmNamesUnique();
        $this->logger?->debug('Local supported signature algorithms: ' . implode(', ', $commonlySupportedAlgorithms));

        if (
            is_string($receiverSupportedSignatureAlgorithmsMetadataKey) &&
            array_key_exists($receiverSupportedSignatureAlgorithmsMetadataKey, $receiverEntityMetadata) &&
            is_array($receiverSupportedAlgs = $receiverEntityMetadata[$receiverSupportedSignatureAlgorithmsMetadataKey])
        ) {
            $receiverSupportedAlgs = $this->helpers->type()
                ->enforceNonEmptyArrayWithValuesAsNonEmptyStrings($receiverSupportedAlgs);
            $this->logger?->debug('Receiver supported signature algorithms: ' . implode(', ', $receiverSupportedAlgs));

            $commonlySupportedAlgorithms = array_intersect($commonlySupportedAlgorithms, $receiverSupportedAlgs);
        }

        if (
            is_string($senderSupportedSignatureAlgorithmsMetadataKey) &&
            array_key_exists($senderSupportedSignatureAlgorithmsMetadataKey, $senderEntityMetadata) &&
            is_array($senderSupportedAlgs = $senderEntityMetadata[$senderSupportedSignatureAlgorithmsMetadataKey])
        ) {
            $senderSupportedAlgs = $this->helpers->type()
                ->ensureArrayWithValuesAsNonEmptyStrings($senderSupportedAlgs);
            $this->logger?->debug('Sender supported signature algorithms: ' . implode(', ', $senderSupportedAlgs));

            $commonlySupportedAlgorithms = array_intersect($commonlySupportedAlgorithms, $senderSupportedAlgs);
        }

        if ($commonlySupportedAlgorithms !== []) {
            $commonlySupportedAlgorithms = $this->helpers->type()
                ->enforceNonEmptyArrayWithValuesAsNonEmptyStrings($commonlySupportedAlgorithms);

            $this->logger?->debug(
                'Commonly supported signature algorithms found: ' . implode(', ', $commonlySupportedAlgorithms),
            );

            return $signatureKeyPairBag->getFirstByAlgorithmOrFail(
                SignatureAlgorithmEnum::from(reset($commonlySupportedAlgorithms)),
            );
        }

        $this->logger?->debug('No commonly supported signature algorithms found. Using default.');

        $this->logger?->debug(
            'Signature Key Pair after algorithm selection: ',
            [
                'algorithm' => $signatureKeyPair->getSignatureAlgorithm()->value,
                'keyId' => $signatureKeyPair->getKeyPair()->getKeyId(),
            ],
        );

        return $signatureKeyPair;
    }
}
