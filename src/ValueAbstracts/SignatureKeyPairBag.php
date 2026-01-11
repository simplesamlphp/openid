<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\ValueAbstracts;

use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Exceptions\OpenIdException;

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


    public function getFirst(): ?SignatureKeyPair
    {
        return $this->signatureKeyPairs[array_key_first($this->signatureKeyPairs)] ?? null;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function getFirstOrFail(): SignatureKeyPair
    {
        return $this->getFirst() ?? throw new OpenIdException(
            'Signature key pair is not set.',
        );
    }


    public function getFirstByAlgorithm(SignatureAlgorithmEnum $signatureAlgorithm): ?SignatureKeyPair
    {
        foreach ($this->signatureKeyPairs as $signatureKeyPair) {
            if ($signatureKeyPair->getSignatureAlgorithm() === $signatureAlgorithm) {
                return $signatureKeyPair;
            }
        }

        return null;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function getFirstByAlgorithmOrFail(SignatureAlgorithmEnum $signatureAlgorithm): SignatureKeyPair
    {
        return $this->getFirstByAlgorithm($signatureAlgorithm) ?? throw new OpenIdException(
            sprintf(
                'Signature key pair for algorithm %s is not set.',
                $signatureAlgorithm->value,
            ),
        );
    }


    /**
     * @return string[]
     */
    public function getAllAlgorithmNamesUnique(): array
    {
        return array_unique(
            array_values(
                array_map(
                    fn(SignatureKeyPair $signatureKeyPair): string => $signatureKeyPair->getSignatureAlgorithm()->value,
                    $this->getAll(),
                ),
            ),
        );
    }
}
