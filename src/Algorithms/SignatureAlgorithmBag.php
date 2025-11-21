<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Algorithms;

use Jose\Component\Signature\Algorithm\SignatureAlgorithm;

class SignatureAlgorithmBag
{
    /** @var \SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum[]  */
    protected array $algorithms;


    public function __construct(SignatureAlgorithmEnum $algorithm, SignatureAlgorithmEnum ...$algorithms)
    {
        $this->algorithms = array_unique([$algorithm, ...$algorithms]);
    }


    public function add(SignatureAlgorithmEnum $algorithm): void
    {
        if (!in_array($algorithm, $this->algorithms, true)) {
            $this->algorithms[] = $algorithm;
        }
    }


    /**
     * @return \SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum[]
     */
    public function getAll(): array
    {
        return $this->algorithms;
    }


    /**
     * @return \Jose\Component\Signature\Algorithm\SignatureAlgorithm[]
     */
    public function getAllInstances(): array
    {
        return array_map(
            fn(
                SignatureAlgorithmEnum $signatureAlgorithmEnum,
            ): SignatureAlgorithm => $signatureAlgorithmEnum->instance(),
            $this->getAll(),
        );
    }
}
