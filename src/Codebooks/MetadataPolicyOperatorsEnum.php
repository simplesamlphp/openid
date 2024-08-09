<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Codebooks;

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
                PrimitiveTypesEnum::String->value,
                PrimitiveTypesEnum::Integer->value,
                PrimitiveTypesEnum::Double->value,
                PrimitiveTypesEnum::Boolean->value,
                PrimitiveTypesEnum::Object->value,
                PrimitiveTypesEnum::Array->value,
                PrimitiveTypesEnum::Null->value,
            ],
            self::Add, self::OneOf, self::SubsetOf, self::SupersetOf => [
                PrimitiveTypesEnum::Array->value,
            ],
            self::Default => [
                PrimitiveTypesEnum::String->value,
                PrimitiveTypesEnum::Integer->value,
                PrimitiveTypesEnum::Double->value,
                PrimitiveTypesEnum::Boolean->value,
                PrimitiveTypesEnum::Object->value,
                PrimitiveTypesEnum::Array->value,
            ],
            self::Essential => [
                PrimitiveTypesEnum::Boolean->value,
            ],
        };
    }

    public function isOperatorValueTypeSupported(string $operatorValueType): bool
    {
        return in_array($operatorValueType, $this->getSupportedOperatorValueTypes());
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
