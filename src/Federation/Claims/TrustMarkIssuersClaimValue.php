<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Claims;

use JsonSerializable;

class TrustMarkIssuersClaimValue implements JsonSerializable
{
    /**
     * @param non-empty-string $trustMarkType
     * @param array<non-empty-string> $trustMarkIssuers
     */
    public function __construct(
        protected readonly string $trustMarkType,
        protected readonly array $trustMarkIssuers,
    ) {
    }


    /**
     * @return non-empty-string
     */
    public function getTrustMarkType(): string
    {
        return $this->trustMarkType;
    }


    /**
     * @return array<non-empty-string>
     */
    public function getTrustMarkIssuers(): array
    {
        return $this->trustMarkIssuers;
    }


    /**
     * @return array<non-empty-string,array<non-empty-string>>
     */
    public function jsonSerialize(): array
    {
        return [
            $this->trustMarkType => $this->getTrustMarkIssuers(),
        ];
    }
}
