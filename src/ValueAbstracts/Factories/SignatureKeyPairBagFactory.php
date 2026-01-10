<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\ValueAbstracts\Factories;

use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPair;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPairBag;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPairConfig;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPairConfigBag;

class SignatureKeyPairBagFactory
{
    public function __construct(
        protected readonly SignatureKeyPairFactory $signatureKeyPairFactory,
    ) {
    }


    public function fromConfig(SignatureKeyPairConfigBag $signatureKeyPairConfigBag): SignatureKeyPairBag
    {
        return new SignatureKeyPairBag(
            ...array_map(
                fn(
                    SignatureKeyPairConfig $signatureKeyPairConfig,
                ): SignatureKeyPair => $this->signatureKeyPairFactory->fromConfig($signatureKeyPairConfig),
                $signatureKeyPairConfigBag->getAll(),
            ),
        );
    }
}
