<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\ValueAbstracts;

use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;

class SignatureKeyPairConfig
{
    /**
     * @param \SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum $signatureAlgorithm Signature algorithm
     * to use for signing JWTs.
     * @param \SimpleSAML\OpenID\ValueAbstracts\KeyPairConfigInterface $keyPairConfig Key pair configuration to use for
     * signing JWTs.
     */
    public function __construct(
        protected readonly SignatureAlgorithmEnum $signatureAlgorithm,
        protected readonly KeyPairConfigInterface $keyPairConfig,
    ) {
    }


    public function getSignatureAlgorithm(): SignatureAlgorithmEnum
    {
        return $this->signatureAlgorithm;
    }


    public function getKeyPairConfig(): KeyPairConfigInterface
    {
        return $this->keyPairConfig;
    }
}
