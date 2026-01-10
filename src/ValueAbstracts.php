<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID;

use SimpleSAML\OpenID\ValueAbstracts\Factories\SignatureKeyPairBagFactory;
use SimpleSAML\OpenID\ValueAbstracts\Factories\SignatureKeyPairFactory;

class ValueAbstracts
{
    protected ?SignatureKeyPairFactory $signatureKeyPairFactory = null;

    protected ?SignatureKeyPairBagFactory $signatureKeyPairBagFactory = null;


    public function __construct(
        protected readonly Jwk $jwk = new Jwk(),
    ) {
    }


    public function jwk(): Jwk
    {
        return $this->jwk;
    }


    public function signatureKeyPairFactory(): SignatureKeyPairFactory
    {
        return $this->signatureKeyPairFactory ??= new SignatureKeyPairFactory(
            $this->jwk(),
        );
    }


    public function signatureKeyPairBagFactory(): SignatureKeyPairBagFactory
    {
        return $this->signatureKeyPairBagFactory ??= new SignatureKeyPairBagFactory(
            $this->signatureKeyPairFactory(),
        );
    }
}
