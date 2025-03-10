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
     * @phpstan-ignore missingType.iterableValue (We validate it here)
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
     * @return array<string,array<string,mixed>>
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
            if (!array_key_exists($entityTypeEnum->value, $metadataPolicy)) {
                continue;
            }

            /** @phpstan-ignore booleanNot.alwaysFalse (Let's check for validity here.) */
            if (!is_array($nextPolicy = $metadataPolicy[$entityTypeEnum->value])) {
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
                ($unsupportedCriticalOperators = array_values(array_intersect(
                    $criticalMetadataPolicyOperators,
                    array_diff($allNextPolicyOperators, $supportedOperators), // Unsupported operators, but ignored
                ))) !== []
            ) {
                throw new MetadataPolicyException(
                    'Unsupported critical metadata policy operator(s) encountered: ' .
                    implode(', ', $unsupportedCriticalOperators),
                );
            }

            // Go over each metadata parameter and resolve the policy.
            foreach ($nextPolicy as $nextPolicyParameter => $nextPolicyParameterOperations) {
                MetadataPolicyOperatorsEnum::validateGeneralParameterOperationRules($nextPolicyParameterOperations);
                MetadataPolicyOperatorsEnum::validateSpecificParameterOperationRules($nextPolicyParameterOperations);

                // Check special merging rules, if any. If everything is ok, set it as is / merge with current policy.
                $nextPolicyParameterOperatorKeys = array_keys($nextPolicyParameterOperations);
                foreach (MetadataPolicyOperatorsEnum::cases() as $metadataPolicyOperatorEnum) {
                    if (!in_array($metadataPolicyOperatorEnum->value, $nextPolicyParameterOperatorKeys, true)) {
                        continue;
                    }

                    $operatorValue = $nextPolicyParameterOperations[$metadataPolicyOperatorEnum->value];

                    // If it doesn't exist, we can simply set it as is.
                    if (
                        (!isset($currentPolicy[$nextPolicyParameter])) ||
                        (!is_array($currentPolicy[$nextPolicyParameter])) ||
                        (!array_key_exists($metadataPolicyOperatorEnum->value, $currentPolicy[$nextPolicyParameter]))
                    ) {
                        $this->helpers->arr()->ensureArrayDepth(
                            $currentPolicy,
                            $nextPolicyParameter,
                            $metadataPolicyOperatorEnum->value,
                        );
                        // @phpstan-ignore offsetAccess.nonOffsetAccessible (We ensured this is array.)
                        $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value] =
                        $operatorValue;

                        // It exists, so we have to check special cases for merging.
                    } elseif (
                        $metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::Value ||
                        $metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::Default
                    ) {
                        // These must have the same value in different policies.
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
                        $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value] =
                        array_values(array_unique(array_merge(
                                /** @phpstan-ignore argument.type (We ensured this is array.) */
                            $operatorValue,
                            /** @phpstan-ignore argument.type (We ensured this is array.) */
                                $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value],
                        )));
                    } elseif (
                        $metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::OneOf
                    ) {
                        // The result of merging the values of two operators is the intersection of the
                        // operator values.
                        $intersection = array_values(array_intersect(
                            /** @phpstan-ignore argument.type (We ensured this is array.) */
                            $operatorValue,
                            /** @phpstan-ignore argument.type (We ensured this is array.) */
                            $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value],
                        ));

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
                        $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value] =
                        $intersection;
                    } elseif (
                        $metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::SubsetOf
                    ) {
                        // The result of merging the values of two operators is the intersection of the
                        // operator values.
                        $intersection = array_values(array_intersect(
                        /** @phpstan-ignore argument.type (We ensured this is array.) */
                            $operatorValue,
                            /** @phpstan-ignore argument.type (We ensured this is array.) */
                            $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value],
                        ));

                        // Set it as new operator value, even if its empty.
                        $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value] =
                        $intersection;
                    } elseif ($currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value] === false) {
                        // This is operator essential.
                        // If a Superior has specified essential=true, then a Subordinate MUST NOT change that.
                        // If a Superior has specified essential=false, then a Subordinate is allowed to change
                        // that to essential=true.
                        $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value] =
                        (bool)$operatorValue;
                    } elseif ($operatorValue !== true) {
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
