<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\ValueAbstracts\Factories;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\HashAlgorithmsEnum;
use SimpleSAML\OpenID\Codebooks\PublicKeyUseEnum;
use SimpleSAML\OpenID\Jwk;
use SimpleSAML\OpenID\ValueAbstracts\KeyPair;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPair;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPairConfig;

class SignatureKeyPairFactory
{
    public function __construct(
        protected readonly Jwk $jwk = new Jwk(),
    ) {
    }


    public function fromConfig(
        SignatureKeyPairConfig $signatureKeyPairConfig,
        HashAlgorithmsEnum $jwkThumbprintHashAlgo = HashAlgorithmsEnum::SHA_256,
    ): SignatureKeyPair {
        $publicKeyJwkDecorator = $this->jwk->jwkDecoratorFactory()->fromPkcs1Or8Key(
            $signatureKeyPairConfig->getKeyPairConfig()->getPublicKeyString(),
            additionalData: [
                ClaimsEnum::Use->value => PublicKeyUseEnum::Signature->value,
                ClaimsEnum::Alg->value => $signatureKeyPairConfig->getSignatureAlgorithm()->value,
            ],
        );

        $keyId = $signatureKeyPairConfig->getKeyPairConfig()->getKeyId() ??
        $publicKeyJwkDecorator->jwk()->thumbprint($jwkThumbprintHashAlgo->phpName());

        $publicKeyJwkDecorator->addAdditionalData(ClaimsEnum::Kid->value, $keyId);

        return new SignatureKeyPair(
            $signatureKeyPairConfig->getSignatureAlgorithm(),
            new KeyPair(
                $this->jwk->jwkDecoratorFactory()->fromPkcs1Or8Key(
                    $signatureKeyPairConfig->getKeyPairConfig()->getPrivateKeyString(),
                ),
                $publicKeyJwkDecorator,
                $keyId,
                $signatureKeyPairConfig->getKeyPairConfig()->getPrivateKeyPassword(),
            ),
        );
    }
}
