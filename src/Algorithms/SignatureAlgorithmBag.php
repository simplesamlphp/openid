<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Algorithms;

class SignatureAlgorithmBag
{
    /** @var \SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum[]  */
    protected array $algorithms;

    public function __construct(SignatureAlgorithmEnum ...$algorithms)
    {
        $this->algorithms = $algorithms;
    }

    public function add(SignatureAlgorithmEnum $algorithm): void
    {
        $this->algorithms[] = $algorithm;
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
            function (SignatureAlgorithmEnum $signatureAlgorithmEnum) {
                return $signatureAlgorithmEnum->instance();
            },
            $this->getAll(),
        );
    }
}
