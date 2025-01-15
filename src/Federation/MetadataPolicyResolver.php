<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use SimpleSAML\OpenID\Codebooks\EntityTypesEnum;
use SimpleSAML\OpenID\Codebooks\MetadataPolicyOperatorsEnum;
use SimpleSAML\OpenID\Exceptions\MetadataPolicyException;
use SimpleSAML\OpenID\Helpers;

class MetadataPolicyResolver
{
    public function __construct(
        protected readonly Helpers $helpers,
    ) {
    }

    /**
     * @return array<string,array<string,array<string,mixed>>>
     * @throws \SimpleSAML\OpenID\Exceptions\MetadataPolicyException
     * @psalm-suppress MixedAssignment
     */
    public function ensureFormat(array $metadataPolicies): array
    {
        foreach ($metadataPolicies as $entityType => $metadataPolicyEntityType) {
            if (!is_string($entityType)) {
                throw new MetadataPolicyException('Invalid metadata policy format (entity type key).');
            }
            if (!is_array($metadataPolicyEntityType)) {
                throw new MetadataPolicyException('Invalid metadata policy format (entity type value).');
            }

            foreach ($metadataPolicyEntityType as $parameter => $metadataPolicyParameter) {
                if (!is_string($parameter)) {
                    throw new MetadataPolicyException('Invalid metadata policy format (parameter key).');
                }
                if (!is_array($metadataPolicyParameter)) {
                    throw new MetadataPolicyException('Invalid metadata policy format (parameter value).');
                }

                $operators = array_keys($metadataPolicyParameter);
                foreach ($operators as $operator) {
                    if (!is_string($operator)) {
                        throw new MetadataPolicyException('Invalid metadata policy format (operator key).');
                    }
                }
            }
        }

        /** @var array<string,array<string,array<string,mixed>>> $metadataPolicies */
        return $metadataPolicies;
    }

    /**
     * @param array<array<string,array<string,array<string,mixed>>>> $metadataPolicies
     * @param string[] $criticalMetadataPolicyOperators
     *
     * @throws \SimpleSAML\OpenID\Exceptions\MetadataPolicyException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function for(
        EntityTypesEnum $entityTypeEnum,
        array $metadataPolicies,
        array $criticalMetadataPolicyOperators = [],
    ): array {
        /** @var array<string,array<string,mixed>> $currentPolicy */
        $currentPolicy = [];
        $supportedOperators = MetadataPolicyOperatorsEnum::values();

        foreach ($metadataPolicies as $metadataPolicy) {
            /** @psalm-suppress MixedAssignment We'll check if $nextPolicy is array type. */
            if (
                (!array_key_exists($entityTypeEnum->value, $metadataPolicy)) ||
                (!is_array($nextPolicy = $metadataPolicy[$entityTypeEnum->value]))
            ) {
                continue;
            }

            // Gather all next policy operators, so we can check if there are any critical ones that we do not support.
            $allNextPolicyOperators = array_reduce(
                $nextPolicy,
                fn(array $carry, array $policy): array => array_merge($carry, array_keys($policy)),
                [],
            );

            // Disregard unsupported if not critical, otherwise throw.
            if (
                ($unsupportedCriticalOperators = array_intersect(
                    $criticalMetadataPolicyOperators,
                    array_diff($allNextPolicyOperators, $supportedOperators), // Unsupported operators, but ignored
                )) !== []
            ) {
                throw new MetadataPolicyException(
                    'Unsupported critical metadata policy operator(s) encountered: ' .
                    implode(', ', $unsupportedCriticalOperators),
                );
            }

            // Go over each metadata parameter and resolve the policy.
            /** @psalm-suppress MixedAssignment We'll check if $nextPolicyParameterOperations is array type. */
            foreach ($nextPolicy as $nextPolicyParameter => $nextPolicyParameterOperations) {
                MetadataPolicyOperatorsEnum::validateGeneralParameterOperationRules($nextPolicyParameterOperations);
                MetadataPolicyOperatorsEnum::validateSpecificParameterOperationRules($nextPolicyParameterOperations);

                // Check special merging rules, if any. If everything is ok, set it as is / merge with current policy.
                $nextPolicyParameterOperatorKeys = array_keys($nextPolicyParameterOperations);
                foreach (MetadataPolicyOperatorsEnum::cases() as $metadataPolicyOperatorEnum) {
                    if (!in_array($metadataPolicyOperatorEnum->value, $nextPolicyParameterOperatorKeys, true)) {
                        continue;
                    }

                    /** @psalm-suppress MixedAssignment */
                    $operatorValue = $nextPolicyParameterOperations[$metadataPolicyOperatorEnum->value];

                    // If it doesn't exist, we can simply set it as is.
                    if (
                        !isset($currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value])
                    ) {
                        $this->helpers->arr()->ensureArrayDepth(
                            $currentPolicy,
                            $nextPolicyParameter,
                            $metadataPolicyOperatorEnum->value,
                        );
                        /** @psalm-suppress MixedAssignment, MixedArrayAssignment We ensured this is array. */
                        $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value] =
                        $operatorValue;

                        // It exists, so we have to check special cases for merging.
                    } elseif (
                        $metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::Value ||
                        $metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::Default
                    ) {
                        // These must have the same value in different policies.
                        /** @psalm-suppress MixedArrayAccess We ensured this is array. */
                        if (
                            $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value] !==
                            $operatorValue
                        ) {
                            // The values are different, we have to throw.
                            throw new MetadataPolicyException(
                                sprintf(
                                    'Different operator values encountered for operator %s: %s !== %s.',
                                    $metadataPolicyOperatorEnum->value,
                                    var_export(
                                        $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value],
                                        true,
                                    ),
                                    var_export($operatorValue, true),
                                ),
                            );
                        }
                        // Values are the same, so it's ok. We can continue.
                    } elseif (
                        $metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::Add ||
                        $metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::SupersetOf
                    ) {
                        // We merge with existing values.
                        /** @var array $operatorValue We ensured this is array. */
                        /** @psalm-suppress MixedAssignment, MixedArgument, MixedArrayAssignment, MixedArrayAccess We ensured this is array. */
                        $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value] =
                        array_unique(
                            array_merge(
                                $operatorValue,
                                $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value],
                            ),
                        );
                    } elseif (
                        $metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::OneOf ||
                        $metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::SubsetOf
                    ) {
                        // The result of merging the values of two operators is the intersection of the
                        // operator values. If the intersection is empty, this MUST result in a policy error.
                        /** @var array $operatorValue We ensured this is array. */
                        /** @psalm-suppress MixedArgument, MixedArrayAccess We ensured this is array. */
                        $intersection = array_intersect(
                            $operatorValue,
                            $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value],
                        );

                        /** @psalm-suppress MixedArrayAccess, MixedArrayAssignment We ensured this is array. */
                        if ($intersection === []) {
                            throw new MetadataPolicyException(
                                sprintf(
                                    'Empty intersection encountered for operator %s: %s | %s.',
                                    $metadataPolicyOperatorEnum->value,
                                    var_export(
                                        $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value],
                                        true,
                                    ),
                                    var_export($operatorValue, true),
                                ),
                            );
                        }

                        // We have values in intersection, so set it as new operator value.
                        /** @psalm-suppress MixedArrayAccess, MixedArrayAssignment We ensured this is array. */
                        $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value] =
                        $intersection;
                    } elseif ($currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value] === false) {
                        // This is operator essential.
                        // If a Superior has specified essential=true, then a Subordinate MUST NOT change that.
                        // If a Superior has specified essential=false, then a Subordinate is allowed to change
                        // that to essential=true.
                        /** @psalm-suppress MixedArrayAccess, MixedArrayAssignment We ensured this is array. */
                        $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value] =
                        (bool)$operatorValue;
                    } elseif ($operatorValue !== true) {
                        /** @psalm-suppress MixedArrayAccess We ensured this is array. */
                        throw new MetadataPolicyException(
                            sprintf(
                                'Invalid change of value for operator %s: %s -> %s.',
                                $metadataPolicyOperatorEnum->value,
                                var_export(
                                    $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value],
                                    true,
                                ),
                                var_export($operatorValue, true),
                            ),
                        );
                    }
                }

                // Check if the current policy is in valid state after merge.
                /** @var array<string,array<string,mixed>> $currentPolicy */
                foreach ($currentPolicy as $currentPolicyParameterOperations) {
                    MetadataPolicyOperatorsEnum::validateGeneralParameterOperationRules(
                        $currentPolicyParameterOperations,
                    );
                    MetadataPolicyOperatorsEnum::validateSpecificParameterOperationRules(
                        $currentPolicyParameterOperations,
                    );
                }
            }
        }

        return $currentPolicy;
    }
}
