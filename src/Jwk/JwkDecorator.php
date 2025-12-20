<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Jwk;

use Jose\Component\Core\JWK;

class JwkDecorator implements \JsonSerializable
{
    /**
     * @param mixed[] $additionalData
     */
    public function __construct(
        protected readonly JWK $jwk,
        protected readonly array $additionalData = [],
    ) {
    }


    public function jwk(): JWK
    {
        return $this->jwk;
    }


    /**
     * @return mixed[]
     */
    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }


    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        return array_merge($this->jwk->jsonSerialize(), $this->additionalData);
    }
}
