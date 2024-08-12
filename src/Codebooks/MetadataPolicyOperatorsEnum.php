<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

use SimpleSAML\OpenID\Exceptions\MetadataPolicyException;

enum MetadataPolicyOperatorsEnum: string
{
    /**
     * IMPORTANT: the order in which the cases are defined must be in line with the execution order from the
     * federation specification.
     */
    case Value = 'value';
    case Add = 'add';
    case Default = 'default';
    case OneOf = 'one_of';
    case SubsetOf = 'subset_of';
    case SupersetOf = 'superset_of';
    case Essential = 'essential';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getSupportedOperatorValueTypes(): array
    {
        return match ($this) {
            self::Value => [
                BasicTypesEnum::String->value,
                BasicTypesEnum::Integer->value,
                BasicTypesEnum::Double->value,
                BasicTypesEnum::Boolean->value,
                BasicTypesEnum::Object->value,
                BasicTypesEnum::Array->value,
                BasicTypesEnum::Null->value,
            ],
            self::Add, self::OneOf, self::SubsetOf, self::SupersetOf => [
                BasicTypesEnum::Array->value,
            ],
            self::Default => [
                BasicTypesEnum::String->value,
                BasicTypesEnum::Integer->value,
                BasicTypesEnum::Double->value,
                BasicTypesEnum::Boolean->value,
                BasicTypesEnum::Object->value,
                BasicTypesEnum::Array->value,
            ],
            self::Essential => [
                BasicTypesEnum::Boolean->value,
            ],
        };
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\MetadataPolicyException
     */
    public function getSupportedOperatorContainedValueTypes(): array
    {
        return match ($this) {
            self::Add, self::OneOf, self::SubsetOf, self::SupersetOf => [
                BasicTypesEnum::String->value,
                BasicTypesEnum::Integer->value,
                BasicTypesEnum::Double->value,
            ],
            self::Value, self::Default, self::Essential => throw new MetadataPolicyException('Not implemented.'),
        };
    }

    public function isValueSubsetOf(mixed $value, array $subset): bool
    {
        $value = is_array($value) ? $value : [$value];

        return empty(array_diff($value, $subset));
    }

    public function isValueSupersetOf(mixed $value, array $superset): bool
    {
        $value = is_array($value) ? $value : [$value];

        // Like subset, but from different perspective.
        return empty(array_diff($superset, $value));
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\MetadataPolicyException
     */
    public function isOperatorValueTypeSupported(mixed $operatorValue): bool
    {
        $operatorValueType = gettype($operatorValue);

        if (! in_array($operatorValueType, $this->getSupportedOperatorValueTypes())) {
            return false;
        }

        // Check contained values for declared types.
        if (in_array($this, [self::Add, self::OneOf, self::SubsetOf, self::SupersetOf])) {
            /** @psalm-suppress MixedAssignment We'll check the type of $containedValue. */
            foreach ((array)$operatorValue as $containedValue) {
                $containedValueType = gettype($containedValue);
                if (! in_array($containedValueType, $this->getSupportedOperatorContainedValueTypes())) {
                    return false;
                }
            }
        }

        return true;
    }

    public function getSupportedOperatorCombinations(): array
    {
        return [
            $this->value,
            ...match ($this) {
                self::Value => [
                    self::Essential->value,
                ],
                self::Add => [
                    self::Default->value,
                    self::SubsetOf->value,
                    self::SupersetOf->value,
                    self::Essential->value,
                ],
                self::Default => [
                    self::Add->value,
                    self::OneOf->value,
                    self::SubsetOf->value,
                    self::SupersetOf->value,
                    self::Essential->value,
                ],
                self::OneOf => [
                    self::Default->value,
                    self::Essential->value,
                ],
                self::SubsetOf => [
                    self::Add->value,
                    self::Default->value,
                    self::SupersetOf->value,
                    self::Essential->value,
                ],
                self::SupersetOf => [
                    self::Add->value,
                    self::Default->value,
                    self::SubsetOf->value,
                    self::Essential->value,
                ],
                self::Essential => [
                    self::Add->value,
                    self::Default->value,
                    self::OneOf->value,
                    self::SubsetOf->value,
                    self::SupersetOf->value,
                ],
            },
        ];
    }

    public function isOperatorCombinationSupported(array $operatorKeys): bool
    {
        return empty(array_diff($operatorKeys, $this->getSupportedOperatorCombinations()));
    }
}
