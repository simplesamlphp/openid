<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\ValueAbstracts;

use SimpleSAML\OpenID\Exceptions\OpenIdException;

class SignatureKeyPairConfigBag
{
    /**
     * @var \SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPairConfig[]
     */
    protected array $signatureKeyPairConfigs = [];


    public function __construct(
        SignatureKeyPairConfig ...$signatureKeyPairConfigs,
    ) {
        $this->add(...$signatureKeyPairConfigs);
    }


    public function add(SignatureKeyPairConfig ...$signatureKeyPairConfigs): void
    {
        foreach ($signatureKeyPairConfigs as $signatureKeyPairConfig) {
            $keyId = $signatureKeyPairConfig->getKeyPairConfig()->getKeyId();
            if ($keyId === null) {
                $this->signatureKeyPairConfigs[] = $signatureKeyPairConfig;
            } else {
                $this->signatureKeyPairConfigs[$keyId] = $signatureKeyPairConfig;
            }
        }
    }


    public function getByKeyId(string $keyId): ?SignatureKeyPairConfig
    {
        return $this->signatureKeyPairConfigs[$keyId] ?? null;
    }


    /**
     * @return \SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPairConfig[]
     */
    public function getAll(): array
    {
        return $this->signatureKeyPairConfigs;
    }


    /**
     * @return string[]
     */
    public function getAllAlgorithmNamesUnique(): array
    {
        return array_unique(
            array_values(
                array_map(
                    fn(
                        SignatureKeyPairConfig $signatureKeyPairConfig,
                    ): string => $signatureKeyPairConfig->getSignatureAlgorithm()->value,
                    $this->getAll(),
                ),
            ),
        );
    }


    public function getFirst(): ?SignatureKeyPairConfig
    {
        if (is_null($firstKey = array_key_first($this->signatureKeyPairConfigs))) {
            return null;
        }

        return $this->signatureKeyPairConfigs[$firstKey] ?? null;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function getFirstOrFail(): SignatureKeyPairConfig
    {
        return $this->getFirst() ?? throw new OpenIdException(
            'Signature key pair config is not set.',
        );
    }
}
