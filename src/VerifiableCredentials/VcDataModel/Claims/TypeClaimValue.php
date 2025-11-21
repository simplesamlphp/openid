<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use SimpleSAML\OpenID\Claims\ClaimInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

class TypeClaimValue implements ClaimInterface
{
    /**
     * @param non-empty-string[] $types
     */
    public function __construct(
        protected readonly array $types,
    ) {
    }


    public function getName(): string
    {
        return ClaimsEnum::Type->value;
    }


    /**
     * @return non-empty-string[]
     */
    public function getValue(): array
    {
        return $this->types;
    }


    /**
     * @return non-empty-string|non-empty-string[]
     */
    public function jsonSerialize(): string|array
    {
        $value = $this->getValue();

        if (count($value) === 1) {
            return $value[0];
        }

        return $value;
    }


    /**
     * @param non-empty-string $type
     */
    public function has(string $type): bool
    {
        return in_array($type, $this->types);
    }
}
