<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use SimpleSAML\OpenID\Exceptions\VcDataModelException;
use SimpleSAML\OpenID\ValueAbstracts\ClaimInterface;

class LocalizableStringValueBag implements ClaimInterface
{
    /** @var \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\LocalizableStringValue[] */
    protected array $values;


    public function __construct(
        LocalizableStringValue $firstValue,
        LocalizableStringValue ...$values,
    ) {
        $this->values = [$firstValue, ...$values];
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
     */
    public function getFirstValueOrFail(): LocalizableStringValue
    {
        return $this->values[0] ?? throw new VcDataModelException('No values found');
    }


    public function getName(): string
    {
        return $this->getFirstValueOrFail()->getName();
    }


    /**
     * @return \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\LocalizableStringValue[]
     */
    public function getValue(): array
    {
        return $this->values;
    }


    /**
     * Get a value by language tag (optional language filtering).
     *
     * @param non-empty-string|null $language
     */
    public function getValueByLanguage(?string $language = null): ?LocalizableStringValue
    {
        if ($language === null) {
            // Return first value if no language specified
            return $this->values[0] ?? null;
        }

        foreach ($this->values as $value) {
            if ($value->getValue()->getLanguage() === $language) {
                return $value;
            }
        }

        return null;
    }


    /**
     * @return array<array<string, string>>
     */
    public function jsonSerialize(): array
    {
        return array_map(
            fn(LocalizableStringValue $value): array => $value->jsonSerialize(),
            $this->values,
        );
    }
}
