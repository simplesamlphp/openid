<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jwk;

use Jose\Component\Core\JWK;

class JwkDecorator implements \JsonSerializable
{
    /**
     * @param array<non-empty-string,mixed> $additionalData
     */
    public function __construct(
        protected readonly JWK $jwk,
        protected array $additionalData = [],
    ) {
    }


    public function jwk(): JWK
    {
        return $this->jwk;
    }


    /**
     * @return array<non-empty-string,mixed>
     */
    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }


    /**
     * @param non-empty-string $key
     */
    public function addAdditionalData(string $key, mixed $value): void
    {
        $this->additionalData[$key] = $value;
    }


    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return array_merge($this->jwk->jsonSerialize(), $this->additionalData);
    }
}
