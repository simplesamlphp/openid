<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\MetadataPolicyOperatorsEnum;
use SimpleSAML\OpenID\Exceptions\MetadataPolicyException;
use SimpleSAML\OpenID\Helpers;

class MetadataPolicyApplicator
{
    public function __construct(
        protected readonly Helpers $helpers,
    ) {
    }

    /**
     * @param array $resolvedMetadataPolicy Resolved (validated) metadata policy.
     * @param array $metadata
     * @return array
     * @throws \SimpleSAML\OpenID\Exceptions\MetadataPolicyException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function for(
        array $resolvedMetadataPolicy,
        array $metadata,
    ): array {
        /**
         * @var string $policyParameterName
         * @var array<string,mixed> $policyOperations
         */
        foreach ($resolvedMetadataPolicy as $policyParameterName => $policyOperations) {
            foreach (MetadataPolicyOperatorsEnum::cases() as $metadataPolicyOperatorEnum) {
                if (!array_key_exists($metadataPolicyOperatorEnum->value, $policyOperations)) {
                    continue;
                }
                /** @psalm-suppress MixedAssignment */
                $operatorValue = $policyOperations[$metadataPolicyOperatorEnum->value];
                /** @psalm-suppress MixedAssignment */
                $metadataParameterValueBeforePolicy = $this->resolveParameterValueBeforePolicy(
                    $metadata,
                    $policyParameterName,
                );

                if ($metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::Value) {
                    // The metadata parameter MUST be assigned the value of the operator. When the value of the operator
                    // is null, the metadata parameter MUST be removed.
                    if (is_null($operatorValue)) {
                        unset($metadata[$policyParameterName]);
                        continue;
                    }
                    $this->helpers->arr()->ensureArrayDepth($metadata, $policyParameterName);
                    /** @psalm-suppress MixedAssignment */
                    $metadata[$policyParameterName] = $this->resolveParameterValueAfterPolicy(
                        $operatorValue,
                        $policyParameterName,
                    );
                } elseif ($metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::Add) {
                    // The value or values of this operator MUST be added to the metadata parameter. Values that are
                    // already present in the metadata parameter MUST NOT be added another time. If the metadata
                    // parameter is absent, it MUST be initialized with the value of this operator.
                    if (!isset($metadata[$policyParameterName])) {
                        /** @psalm-suppress MixedAssignment */
                        $metadata[$policyParameterName] = $operatorValue;
                        continue;
                    }

                    $metadataPolicyOperatorEnum->validateMetadataParameterValueType(
                        $metadataParameterValueBeforePolicy,
                        $policyParameterName,
                    );

                    /** @psalm-suppress MixedArgument */
                    $metadataParameterValue = array_unique(
                        array_merge($metadataParameterValueBeforePolicy, $operatorValue),
                    );

                    /** @psalm-suppress MixedAssignment */
                    $metadata[$policyParameterName] = $this->resolveParameterValueAfterPolicy(
                        $metadataParameterValue,
                        $policyParameterName,
                    );
                } elseif ($metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::Default) {
                    // If the metadata parameter is absent, it MUST be set to the value of the operator. If the metadata
                    // parameter is present, this operator has no effect.
                    if (!isset($metadata[$policyParameterName])) {
                        /** @psalm-suppress MixedAssignment */
                        $metadata[$policyParameterName] = $operatorValue;
                    }
                } elseif ($metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::OneOf) {
                    // If the metadata parameter is present, its value MUST be one of those listed in the operator
                    // value.
                    if (!isset($metadata[$policyParameterName])) {
                        continue;
                    }

                    $metadataPolicyOperatorEnum->validateMetadataParameterValueType(
                        $metadataParameterValueBeforePolicy,
                        $policyParameterName,
                    );

                    /** @var array $operatorValue */
                    (in_array($metadataParameterValueBeforePolicy, $operatorValue, true)) ||
                    throw new MetadataPolicyException(
                        sprintf(
                            'Metadata parameter %s, value %s is not one of %s.',
                            $policyParameterName,
                            var_export($metadataParameterValueBeforePolicy, true),
                            var_export($operatorValue, true),
                        ),
                    );
                } elseif ($metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::SubsetOf) {
                    // If the metadata parameter is present, this operator computes the intersection between the values
                    // of the operator and the metadata parameter. If the intersection is non-empty, the metadata
                    // parameter is set to the values in the intersection. If the intersection is empty, the
                    // metadata parameter MUST be removed. Note that this behavior makes subset_of a
                    // potential value modifier in addition to it being a value check.
                    if (!isset($metadata[$policyParameterName])) {
                        continue;
                    }

                    $metadataPolicyOperatorEnum->validateMetadataParameterValueType(
                        $metadataParameterValueBeforePolicy,
                        $policyParameterName,
                    );

                    /** @psalm-suppress MixedArgument */
                    $intersection = array_intersect(
                        $metadataParameterValueBeforePolicy,
                        $operatorValue,
                    );

                    if (empty($intersection)) {
                        unset($metadata[$policyParameterName]);
                        continue;
                    }
                    /** @psalm-suppress MixedAssignment */
                    $metadata[$policyParameterName] = $this->resolveParameterValueAfterPolicy(
                        $intersection,
                        $policyParameterName,
                    );
                } elseif ($metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::SupersetOf) {
                    // If the metadata parameter is present, its values MUST contain those specified in the operator
                    // value. By mathematically defining supersets, equality is included.
                    if (!isset($metadata[$policyParameterName])) {
                        continue;
                    }

                    $metadataPolicyOperatorEnum->validateMetadataParameterValueType(
                        $metadataParameterValueBeforePolicy,
                        $policyParameterName,
                    );

                    /** @var array $operatorValue */
                    ($metadataPolicyOperatorEnum->isValueSupersetOf(
                        $metadataParameterValueBeforePolicy,
                        $operatorValue,
                    )) || throw new MetadataPolicyException(
                        sprintf(
                            'Parameter %s, operator %s, value %s is not superset of %s.',
                            $policyParameterName,
                            $metadataPolicyOperatorEnum->value,
                            var_export($metadataParameterValueBeforePolicy, true),
                            var_export($operatorValue, true),
                        ),
                    );
                } else {
                    // This is operator 'essential'
                    // If the value of this operator is true, then the metadata parameter MUST be present. If false,
                    // the metadata parameter is voluntary and may be absent. If the essential operator is omitted,
                    // this is equivalent to including it with a value of false.
                    if (!$operatorValue) {
                        continue;
                    }

                    isset($metadata[$policyParameterName]) || throw new MetadataPolicyException(
                        sprintf(
                            'Parameter %s is marked as essential by policy, but not present in metadata.',
                            $policyParameterName,
                        ),
                    );
                }
            }
        }

        return $metadata;
    }

    protected function resolveParameterValueBeforePolicy(array $metadata, string $parameter): mixed
    {
        /** @psalm-suppress MixedAssignment */
        $value = $metadata[$parameter] ?? null;

        // Special case for 'scope' parameter, which needs to be converted to array before policy application.
        if (($parameter === ClaimsEnum::Scope->value) && is_string($value)) {
            $value = explode(' ', $value);
        }

        return $value;
    }

    protected function resolveParameterValueAfterPolicy(mixed $value, string $parameter): mixed
    {
        // Special case for 'scope' parameter, which needs to be converted to string after policy application.
        if (($parameter === ClaimsEnum::Scope->value) && is_array($value)) {
            /** @psalm-suppress MixedArgumentTypeCoercion */
            $value = implode(' ', $value);
        }

        return $value;
    }
}
