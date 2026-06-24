<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\ValueAbstracts;

use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;

/**
 * @see \SimpleSAML\Test\OpenID\ValueAbstracts\SignatureKeyPairTest
 */
class SignatureKeyPair
{
    public function __construct(
        protected readonly SignatureAlgorithmEnum $signatureAlgorithm,
        protected readonly KeyPair $keyPair,
    ) {
    }


    public function getSignatureAlgorithm(): SignatureAlgorithmEnum
    {
        return $this->signatureAlgorithm;
    }


    public function getKeyPair(): KeyPair
    {
        return $this->keyPair;
    }
}
