<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\ValueAbstracts;

class SignatureKeyPairBag
{
    /**
     * @var array<string,\SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPair>
     */
    protected array $signatureKeyPairs = [];


    public function __construct(
        SignatureKeyPair ...$signatureKeyPairs,
    ) {
        $this->add(...$signatureKeyPairs);
    }


    public function add(SignatureKeyPair ...$signatureKeyPairs): void
    {
        foreach ($signatureKeyPairs as $signatureKeyPair) {
            $this->signatureKeyPairs[$signatureKeyPair->getKeyPair()->getKeyId()] = $signatureKeyPair;
        }
    }


    public function getByKeyId(string $keyId): ?SignatureKeyPair
    {
        return $this->signatureKeyPairs[$keyId] ?? null;
    }


    /**
     * @return array<string,\SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPair>
     */
    public function getAll(): array
    {
        return $this->signatureKeyPairs;
    }


    public function hasKeyId(string $keyId): bool
    {
        return isset($this->signatureKeyPairs[$keyId]);
    }
}
