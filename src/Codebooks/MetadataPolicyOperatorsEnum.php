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

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return string[]
     */
    public function getSupportedOperatorValueTypes(): array
    {
        return match ($this) {
            self::Value => [
                PhpBasicTypesEnum::String->value,
                PhpBasicTypesEnum::Integer->value,
                PhpBasicTypesEnum::Double->value,
                PhpBasicTypesEnum::Boolean->value,
                PhpBasicTypesEnum::Object->value,
                PhpBasicTypesEnum::Array->value,
                PhpBasicTypesEnum::Null->value,
            ],
            self::Add, self::OneOf, self::SubsetOf, self::SupersetOf => [
                PhpBasicTypesEnum::Array->value,
            ],
            self::Default => [
                PhpBasicTypesEnum::String->value,
                PhpBasicTypesEnum::Integer->value,
                PhpBasicTypesEnum::Double->value,
                PhpBasicTypesEnum::Boolean->value,
                PhpBasicTypesEnum::Object->value,
                PhpBasicTypesEnum::Array->value,
            ],
            self::Essential => [
                PhpBasicTypesEnum::Boolean->value,
            ],
        };
    }

    /**
     * @return string[]
     */
    public function getSupportedParameterValueTypes(): array
    {
        return match ($this) {
            self::Value, self::Default, self::Essential => [
                PhpBasicTypesEnum::String->value,
                PhpBasicTypesEnum::Integer->value,
                PhpBasicTypesEnum::Double->value,
                PhpBasicTypesEnum::Boolean->value,
                PhpBasicTypesEnum::Object->value,
                PhpBasicTypesEnum::Array->value,
            ],
            self::Add, self::SubsetOf, self::SupersetOf => [
                PhpBasicTypesEnum::Array->value,
            ],
            self::OneOf => [
                PhpBasicTypesEnum::String->value,
                PhpBasicTypesEnum::Integer->value,
                PhpBasicTypesEnum::Double->value,
                PhpBasicTypesEnum::Object->value,
                PhpBasicTypesEnum::Array->value,
            ],
        };
    }

    /**
     * @return string[]
     * @throws \SimpleSAML\OpenID\Exceptions\MetadataPolicyException
     */
    public function getSupportedOperatorContainedValueTypes(): array
    {
        return match ($this) {
            self::Add, self::OneOf, self::SubsetOf, self::SupersetOf => [
                PhpBasicTypesEnum::String->value,
                PhpBasicTypesEnum::Integer->value,
                PhpBasicTypesEnum::Double->value,
                PhpBasicTypesEnum::Object->value,
                PhpBasicTypesEnum::Array->value,
            ],
            self::Value, self::Default, self::Essential => throw new MetadataPolicyException('Not implemented.'),
        };
    }

    /**
     * @return string[]
     * @throws \SimpleSAML\OpenID\Exceptions\MetadataPolicyException
     */
    public function getSupportedParameterContainedValueTypes(): array
    {
        return match ($this) {
            self::Add, self::SubsetOf, self::SupersetOf => [
                PhpBasicTypesEnum::String->value,
                PhpBasicTypesEnum::Integer->value,
                PhpBasicTypesEnum::Double->value,
                PhpBasicTypesEnum::Object->value,
                PhpBasicTypesEnum::Array->value,
            ],
            self::Value, self::Default, self::OneOf, self::Essential =>
            throw new MetadataPolicyException('Not implemented.'),
        };
    }

    /**
     * @phpstan-ignore missingType.iterableValue (We can handle mixed type using array_diff)
     */
    public function isValueSubsetOf(mixed $value, array $superset): bool
    {
        $value = is_array($value) ? $value : [$value];

        return array_diff($value, $superset) === [];
    }

    /**
     * @phpstan-ignore missingType.iterableValue (We can handle mixed type using array_diff)
     */
    public function isValueSupersetOf(mixed $value, array $subset): bool
    {
        $value = is_array($value) ? $value : [$value];

        // Like subset, but from different perspective.
        return array_diff($subset, $value) === [];
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\MetadataPolicyException
     */
    public function isOperatorValueTypeSupported(mixed $operatorValue): bool
    {
        $operatorValueType = gettype($operatorValue);

        if (! in_array($operatorValueType, $this->getSupportedOperatorValueTypes(), true)) {
            return false;
        }

        // Check contained values for declared types.
        if (in_array($this, [self::Add, self::OneOf, self::SubsetOf, self::SupersetOf], true)) {
            foreach ((array)$operatorValue as $containedValue) {
                $containedValueType = gettype($containedValue);
                if (! in_array($containedValueType, $this->getSupportedOperatorContainedValueTypes(), true)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\MetadataPolicyException
     */
    public function isParameterValueTypeSupported(mixed $parameterValue): bool
    {
        $parameterValueType = gettype($parameterValue);

        if (! in_array($parameterValueType, $this->getSupportedParameterValueTypes(), true)) {
            return false;
        }

        // Check contained values for declared types.
        if (in_array($this, [self::Add, self::SubsetOf, self::SupersetOf], true)) {
            foreach ((array)$parameterValue as $containedValue) {
                $containedValueType = gettype($containedValue);
                if (! in_array($containedValueType, $this->getSupportedParameterContainedValueTypes(), true)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return string[]
     */
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

    /**
     * @param string[] $operatorKeys
     */
    public function isOperatorCombinationSupported(array $operatorKeys): bool
    {
        return array_diff($operatorKeys, $this->getSupportedOperatorCombinations()) === [];
    }

    /**
     * Validate general parameter operation rules like operator combinations and operator value type.
     *
     * @param array<string,mixed> $parameterOperations
     *
     * @throws \SimpleSAML\OpenID\Exceptions\MetadataPolicyException
     */
    public static function validateGeneralParameterOperationRules(array $parameterOperations): void
    {
        $parameterOperatorKeys = array_keys($parameterOperations);

        // Order of operators is important, per specification. Method cases() will return as cases are defined.
        // Common checks - operator value types and operator combinations must be allowed.
        foreach (MetadataPolicyOperatorsEnum::cases() as $metadataPolicyOperatorsEnum) {
            if (!in_array($metadataPolicyOperatorsEnum->value, $parameterOperatorKeys, true)) {
                continue;
            }

            $operatorValue = $parameterOperations[$metadataPolicyOperatorsEnum->value];
            // Check common policy resolving rules for each supported operator.
            // If operator value type is not supported, throw.
            if (!$metadataPolicyOperatorsEnum->isOperatorValueTypeSupported($operatorValue)) {
                throw new MetadataPolicyException(
                    sprintf(
                        'Unsupported operator value type (or contained value type) encountered for %s: %s',
                        $metadataPolicyOperatorsEnum->value,
                        var_export($operatorValue, true),
                    ),
                );
            }
            // If operator combination is not allowed, throw.
            if (!$metadataPolicyOperatorsEnum->isOperatorCombinationSupported($parameterOperatorKeys)) {
                throw new MetadataPolicyException(
                    sprintf(
                        'Unsupported operator combination encountered for %s: %s',
                        $metadataPolicyOperatorsEnum->value,
                        implode(', ', $parameterOperatorKeys),
                    ),
                );
            }
        }
    }

    /**
     * @param array<string,mixed> $parameterOperations
     * @throws \SimpleSAML\OpenID\Exceptions\MetadataPolicyException
     */
    public static function validateSpecificParameterOperationRules(array $parameterOperations): void
    {
        $parameterOperatorKeys = array_keys($parameterOperations);

        // Check specific policy resolving rules for each supported operator.
        foreach (MetadataPolicyOperatorsEnum::cases() as $metadataPolicyOperatorEnum) {
            if (!in_array($metadataPolicyOperatorEnum->value, $parameterOperatorKeys, true)) {
                continue;
            }

            $operatorValue = $parameterOperations[$metadataPolicyOperatorEnum->value];

            // No special resolving rules for operator 'value', continue with 'add'.
            if ($metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::Add) {
                /** @var array<mixed> $operatorValue We ensured this is array. */
                // If add is combined with subset_of, the values of add MUST be a subset of the values of
                // subset_of.
                if (
                    in_array(MetadataPolicyOperatorsEnum::SubsetOf->value, $parameterOperatorKeys, true)
                ) {
                    /** @var array<mixed> $superset We ensured this is array. */
                    $superset = $parameterOperations[
                    MetadataPolicyOperatorsEnum::SubsetOf->value
                    ];
                    if (!MetadataPolicyOperatorsEnum::Add->isValueSubsetOf($operatorValue, $superset)) {
                        throw new MetadataPolicyException(
                            sprintf(
                                'Operator %s, value %s is not subset of %s.',
                                $metadataPolicyOperatorEnum->value,
                                var_export($operatorValue, true),
                                var_export($superset, true),
                            ),
                        );
                    }
                }
                // If add is combined with superset_of, the values of add MUST be a superset of the values
                // of superset_of.
                if (
                    in_array(
                        MetadataPolicyOperatorsEnum::SupersetOf->value,
                        $parameterOperatorKeys,
                        true,
                    )
                ) {
                    /** @var array<mixed> $subset We ensured this is array. */
                    $subset = $parameterOperations[
                    MetadataPolicyOperatorsEnum::SupersetOf->value
                    ];
                    if (!MetadataPolicyOperatorsEnum::Add->isValueSupersetOf($operatorValue, $subset)) {
                        throw new MetadataPolicyException(
                            sprintf(
                                'Operator %s, value %s is not superset of %s.',
                                $metadataPolicyOperatorEnum->value,
                                var_export($operatorValue, true),
                                var_export($subset, true),
                            ),
                        );
                    }
                }
            } elseif ($metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::Default) {
                // If default is combined with one_of, the default value MUST be among the one_of values.
                if (
                    in_array(MetadataPolicyOperatorsEnum::OneOf->value, $parameterOperatorKeys, true)
                ) {
                    /** @var array<mixed> $superset We ensured this is array. */
                    $superset = $parameterOperations[
                    MetadataPolicyOperatorsEnum::OneOf->value
                    ];
                    if (!MetadataPolicyOperatorsEnum::OneOf->isValueSubsetOf($operatorValue, $superset)) {
                        throw new MetadataPolicyException(
                            sprintf(
                                'Operator %s, value %s is not one of %s.',
                                $metadataPolicyOperatorEnum->value,
                                var_export($operatorValue, true),
                                var_export($superset, true),
                            ),
                        );
                    }
                }
                // If default is combined with subset_of, the value of default MUST be a subset of the
                // values of subset_of.
                if (
                    in_array(MetadataPolicyOperatorsEnum::SubsetOf->value, $parameterOperatorKeys, true)
                ) {
                    /** @var array<mixed> $superset We ensured this is array. */
                    $superset = $parameterOperations[
                    MetadataPolicyOperatorsEnum::SubsetOf->value
                    ];
                    if (!MetadataPolicyOperatorsEnum::Default->isValueSubsetOf($operatorValue, $superset)) {
                        throw new MetadataPolicyException(
                            sprintf(
                                'Operator %s, value %s is not subset of %s.',
                                $metadataPolicyOperatorEnum->value,
                                var_export($operatorValue, true),
                                var_export($superset, true),
                            ),
                        );
                    }
                }
                // If default is combined with superset_of, the values of default MUST be a superset of
                // the values of superset_of.
                if (
                    in_array(
                        MetadataPolicyOperatorsEnum::SupersetOf->value,
                        $parameterOperatorKeys,
                        true,
                    )
                ) {
                    /** @var array<mixed> $subset We ensured this is array. */
                    $subset = $parameterOperations[
                    MetadataPolicyOperatorsEnum::SupersetOf->value
                    ];
                    if (!MetadataPolicyOperatorsEnum::Default->isValueSupersetOf($operatorValue, $subset)) {
                        throw new MetadataPolicyException(
                            sprintf(
                                'Operator %s, value %s is not superset of %s.',
                                $metadataPolicyOperatorEnum->value,
                                var_export($operatorValue, true),
                                var_export($subset, true),
                            ),
                        );
                    }
                }

                // Operator one_of has special rule when combined with default, but we already handled that
                // when we encountered default. We can continue to subset_of.
            } elseif ($metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::SubsetOf) {
                // Operator subset_of has special rule when combined with add or default, but we already
                // handled that. We'll only handle special case for superset_of.
                // If subset_of is combined with superset_of, the values of subset_of MUST be a superset of
                // the values of superset_of.
                if (
                    in_array(
                        MetadataPolicyOperatorsEnum::SupersetOf->value,
                        $parameterOperatorKeys,
                        true,
                    )
                ) {
                    /** @var array<mixed> $subset We ensured this is array. */
                    $subset = $parameterOperations[
                    MetadataPolicyOperatorsEnum::SupersetOf->value
                    ];
                    if (!MetadataPolicyOperatorsEnum::SubsetOf->isValueSupersetOf($operatorValue, $subset)) {
                        throw new MetadataPolicyException(
                            sprintf(
                                'Operator %s, value %s is not superset of %s.',
                                $metadataPolicyOperatorEnum->value,
                                var_export($operatorValue, true),
                                var_export($subset, true),
                            ),
                        );
                    }
                }

                // Operator superset_of has special rules when combined with add, default and subset_of,
                // but we already handle those. Operator essential doesn't have any special rules.
                // We can continue with merging.
            }
        }
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\MetadataPolicyException
     */
    public function validateMetadataParameterValueType(mixed $parameterValue, string $parameterName): void
    {
        if (!$this->isParameterValueTypeSupported($parameterValue)) {
            throw new MetadataPolicyException(
                sprintf(
                    'Unsupported parameter %s value type (or contained value type) encountered for %s: %s',
                    $parameterName,
                    $this->value,
                    var_export($parameterValue, true),
                ),
            );
        }
    }
}
