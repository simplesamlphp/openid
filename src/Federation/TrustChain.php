<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use JsonSerializable;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\EntityTypesEnum;
use SimpleSAML\OpenID\Codebooks\MetadataPolicyOperatorsEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\EntityStatementException;
use SimpleSAML\OpenID\Exceptions\MetadataPolicyException;
use SimpleSAML\OpenID\Exceptions\TrustChainException;

class TrustChain implements JsonSerializable
{
    /** @var \SimpleSAML\OpenID\Federation\EntityStatement[] Entities which belong to the trust chain. */
    protected array $entities = [];

    /** @var bool Indication if the trust chain is resolved up to the trust anchor. */
    protected bool $isResolved = false;

    /** @var ?int Expiration time (exp) of the trust chain based on the minimum exp from each entity statement. Null
     * if entity statements have not been added to chain.
     */
    protected ?int $expirationTime = null;

    /**
     * Critical metadata policy operators gathered from subordinate statements (claim metadata_policy_crit).
     *
     * @var string[]
     */
    protected array $criticalMetadataPolicyOperators = [];

    /**
     * Metadata policy entries (claim metadata_policy) gathered from subordinate statements. It begins with the one
     * issued by the most Superior Entity and ends with the Subordinate Statement issued by the Immediate Superior
     * of the Trust Chain subject.
     *
     * @var array[]
     */
    protected array $metadataPolicies = [];

    /**
     * Resolved metadata policy per entity type.
     *
     * @var array[]
     */
    protected array $resolvedMetadataPolicy = [];

    /**
     * Resolved metadata (after applying resolved policy) per entity type.
     *
     * @var array<string,null|array>
     */
    protected array $resolvedMetadata = [];

    public function __construct(protected DateIntervalDecorator $timestampValidationLeeway)
    {
    }

    /**
     * Check if the trust chain is (currently) empty, meaning there are no entity statements present in the chain.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->entities);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    public function getResolvedExpirationTime(): int
    {
        $this->validateIsResolved();
        $this->validateExpirationTime();

        return $this->expirationTime ?? throw new TrustChainException('Empty expiration time encountered.');
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    public function getResolvedLeaf(): EntityStatement
    {
        $this->validateIsResolved();

        ($leaf = reset($this->entities)) ||
        throw new TrustChainException('Empty leaf statement encountered.');

        return $leaf;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    public function getResolvedImmediateSuperior(): EntityStatement
    {
        $this->validateIsResolved();

        ($immediateSuperior = $this->entities[1] ?? null)  ||
        throw new TrustChainException('Empty immediate superior statement encountered.');

        return $immediateSuperior;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    public function getResolvedTrustAnchor(): EntityStatement
    {
        $this->validateIsResolved();

        ($trustAnchor = end($this->entities)) ||
        throw new TrustChainException('Empty entity statement encountered.');

        reset($this->entities);

        return $trustAnchor;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getResolvedMetadata(EntityTypesEnum $entityTypeEnum): ?array
    {
        $this->validateIsResolved();

        // If we have already resolved the metadata for the given entity type, return it.
        if (array_key_exists($entityTypeEnum->value, $this->resolvedMetadata)) {
            return $this->resolvedMetadata[$entityTypeEnum->value];
        }

        $this->resolveMetadataFor($entityTypeEnum);

        return $this->resolvedMetadata[$entityTypeEnum->value] ?? null;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     *
     * @internal
     */
    public function addLeaf(EntityStatement $entityStatement): void
    {
        $this->validateIsNotResolved();
        $this->validateIsEmpty();
        $this->validateConfigurationStatement($entityStatement);

        $this->entities[] = $entityStatement;
        $this->updateExpirationTime($entityStatement);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     *
     * @internal
     */
    public function addSubordinate(EntityStatement $entityStatement): void
    {
        $this->validateIsNotResolved();
        $this->validateIsNotEmpty();
        $this->validateSubordinateStatement($entityStatement);

        $this->entities[] = $entityStatement;
        $this->updateExpirationTime($entityStatement);
        $this->gatherCriticalMetadataPolicyOperators($entityStatement);
        $this->gatherMetadataPolicies($entityStatement);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     *
     * @internal
     */
    public function addTrustAnchor(EntityStatement $entityStatement): void
    {
        $this->validateIsNotResolved();
        $this->validateIsNotEmpty();
        $this->validateAtLeastNumberOfEntities(2);
        $this->validateConfigurationStatement($entityStatement);

        $this->entities[] = $entityStatement;
        $this->updateExpirationTime($entityStatement);

        $this->isResolved = true;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    protected function updateExpirationTime(EntityStatement $entityStatement): void
    {
        $newExpirationTime = $entityStatement->getExpirationTime();

        // If we have already updated expiration time previously, take the minimum value.
        if (!is_null($this->expirationTime)) {
            $newExpirationTime = min($newExpirationTime, $this->expirationTime);
        }

        $this->expirationTime = $newExpirationTime;
        $this->validateExpirationTime();
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    public function validateExpirationTime(): void
    {
        if (is_null($this->expirationTime)) {
            return;
        }

        ($this->expirationTime + $this->timestampValidationLeeway->inSeconds >= time()) ||
        throw new TrustChainException(
            "Trust Chain expiration time ($this->expirationTime) is lesser than current time.",
        );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    protected function validateIsResolved(): void
    {
        if (!$this->isResolved) {
            throw new TrustChainException('Trust Chain is expected to be resolved at this point.');
        }
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    protected function validateIsNotResolved(): void
    {
        if ($this->isResolved) {
            throw new TrustChainException('Trust Chain is expected to not be resolved at this point.');
        }
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    protected function validateIsEmpty(): void
    {
        empty($this->entities) ||
        throw new TrustChainException('Trust Chain is expected to be empty at this point.');
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    protected function validateIsNotEmpty(): void
    {
        !empty($this->entities) ||
        throw new TrustChainException('Trust Chain is expected to be non-empty at this point.');
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    protected function validateAtLeastNumberOfEntities(int $count): void
    {
        (count($this->entities) >= $count) ||
        throw new TrustChainException("Trust Chain is expected to have at least $count entity/ies at this point.");
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function validateConfigurationStatement(EntityStatement $entityStatement): void
    {
        // This must be entity configuration (statement about itself).
        if (!$entityStatement->isConfiguration()) {
            throw new EntityStatementException('Configuration statement issuer is expected to match subject.');
        }

        // Verify with own keys from configuration.
        $entityStatement->verifyWithKeySet();
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function validateSubordinateStatement(EntityStatement $entityStatement): void
    {
        // This must not be configuration
        if ($entityStatement->isConfiguration()) {
            throw new EntityStatementException('Subordinate statement issuer is not expected to match subject.');
        }

        // Check if the subject is the issuer of the last statement in the chain.
        $previousStatement = end($this->entities);
        reset($this->entities);
        if ($entityStatement->getSubject() !== $previousStatement->getIssuer()) {
            throw new EntityStatementException(
                'Subordinate statement subject does not match issuer in previous statement.',
            );
        }

        // Verify previous statement using the keys in subordinate statement.
        $previousStatement->verifyWithKeySet($entityStatement->getJwks());
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     *
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        $this->validateIsResolved();

        return array_map(
            fn(EntityStatement $entityStatement): string => $entityStatement->getToken(),
            $this->entities,
        );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function gatherCriticalMetadataPolicyOperators(EntityStatement $entityStatement): void
    {
        $operators = (array)($entityStatement->getPayloadClaim(ClaimsEnum::MetadataPolicyCrit->value) ?? []);
        // Make sure we have strings only
        $operators = array_map(
            fn(mixed $value): string => (string)$value,
            $operators,
        );

        $this->criticalMetadataPolicyOperators = array_merge($this->criticalMetadataPolicyOperators, $operators);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function gatherMetadataPolicies(EntityStatement $entityStatement): void
    {
        $policy = (array)($entityStatement->getPayloadClaim(ClaimsEnum::MetadataPolicy->value) ?? []);

        if (!empty($policy)) {
            array_unshift($this->metadataPolicies, $policy);
        }
    }

    /**
     * // TODO mivanci move to metadata policy resolver, inject it.
     * @throws \SimpleSAML\OpenID\Exceptions\MetadataPolicyException
     */
    protected function resolveMetadataPolicyFor(EntityTypesEnum $entityTypeEnum): void
    {
        $currentPolicy = [];
        $supportedOperators = MetadataPolicyOperatorsEnum::values();

        foreach ($this->metadataPolicies as $metadataPolicy) {
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
            (empty($unsupportedCriticalOperators = array_intersect(
                $this->criticalMetadataPolicyOperators,
                array_diff($allNextPolicyOperators, $supportedOperators), // Unsupported operators, but can be ignored
            )))
            || throw new MetadataPolicyException(
                'Unsupported critical metadata policy operator(s) encountered: ' .
                implode(', ', $unsupportedCriticalOperators),
            );

            // Go over each metadata parameter and resolve the policy.
            /** @psalm-suppress MixedAssignment We'll check if $nextPolicyParameterOperations is array type. */
            foreach ($nextPolicy as $nextPolicyParameter => $nextPolicyParameterOperations) {
                (is_array($nextPolicyParameterOperations)) || throw new MetadataPolicyException(
                    sprintf(
                        'Invalid format for metadata policy operations encountered: %s',
                        var_export($nextPolicyParameterOperations, true),
                    ),
                );

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
                        $this->ensureArrayDepth(
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
                        (!empty($intersection)) || throw new MetadataPolicyException(
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

                        // We have values in intersection, so set it as new operator value.
                        /** @psalm-suppress MixedArrayAccess, MixedArrayAssignment We ensured this is array. */
                        $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value] =
                        $intersection;
                    } else {
                        // This is operator essential.
                        // If a Superior has specified essential=true, then a Subordinate MUST NOT change that.
                        // If a Superior has specified essential=false, then a Subordinate is allowed to change
                        // that to essential=true.
                        /** @psalm-suppress MixedArrayAccess, MixedArrayAssignment We ensured this is array. */
                        if ($currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value] === false) {
                            $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value] =
                            (bool)$operatorValue;
                        } elseif ($operatorValue !== true) {
                            throw new MetadataPolicyException(
                            /** @psalm-suppress MixedArrayAccess We ensured this is array. */
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
                }

                // Check if the current policy is in valid state after merge.
                /** @var array $currentPolicyParameterOperations We ensured this is array. */
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

        $this->resolvedMetadataPolicy[$entityTypeEnum->value] = $currentPolicy;
    }

    /**
     * TODO mivanci move to Arr helper method, inject helpers
     */
    protected function ensureArrayDepth(array &$array, int|string ...$keys): void
    {
        if (count($keys) > 99) {
            throw new TrustChainException('Refusing to recurse to given depth.');
        }

        $key = array_shift($keys);

        /** @psalm-suppress RiskyTruthyFalsyComparison */
        if (!$key) {
            return;
        }

        if (!isset($array[$key]) || !is_array($array[$key])) {
            $array[$key] = [];
        }

        $this->ensureArrayDepth($array[$key], ...$keys);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\MetadataPolicyException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function resolveMetadataFor(EntityTypesEnum $entityTypeEnum): void
    {
        // In order to be able to resolve metadata, we need to have resolved metadata policy.
        if (!array_key_exists($entityTypeEnum->value, $this->resolvedMetadataPolicy)) {
            $this->resolveMetadataPolicyFor($entityTypeEnum);
        }

        // When an Entity participates in a federation or federations with one or more Entity Types, its Entity
        // Configuration MUST contain a metadata claim with JSON object values for each of the corresponding
        // Entity Type Identifiers, even if the values are the empty JSON object {} (when the Entity Type
        // has no associated metadata or Immediate Superiors supply any needed metadata).
        $leafMetadata = $this->getResolvedLeaf()->getPayloadClaim(ClaimsEnum::Metadata->value);
        if (
            (!is_array($leafMetadata)) || // Claim 'metadata' is optional.
            (!isset($leafMetadata[$entityTypeEnum->value])) || // If no metadata for given entity type
            (!is_array($leafMetadata[$entityTypeEnum->value])) // Unexpected value
        ) {
            $this->resolvedMetadata[$entityTypeEnum->value] = null;
            return;
        }

        $leafMetadataEntityType = $leafMetadata[$entityTypeEnum->value];

        // An Immediate Superior MAY provide selected or all metadata parameters for an Immediate Subordinate, by using
        // the metadata claim in a Subordinate Statement. When metadata is used in a Subordinate Statement, it applies
        // only to those Entity Types that are present in the subject's Entity Configuration. Furthermore, the
        // metadata applies only to the subject of the Subordinate Statement and has no effect on the
        // subject's Subordinates. Metadata parameters in a Subordinate Statement have precedence
        // and override identically named parameters under the same Entity Type in the
        // subject's Entity Configuration. If both metadata and metadata_policy
        // appear in a Subordinate Statement, then the stated metadata MUST
        // be applied before the metadata_policy.
        /** @psalm-suppress MixedAssignment We check type manually. */
        $immediateSuperiorMetadata = $this->getResolvedImmediateSuperior()
            ->getPayloadClaim(ClaimsEnum::Metadata->value);
        if (
            is_array($immediateSuperiorMetadata) &&
            isset($immediateSuperiorMetadata[$entityTypeEnum->value]) &&
            is_array($immediateSuperiorMetadata[$entityTypeEnum->value])
        ) {
            $leafMetadataEntityType = array_merge(
                $leafMetadataEntityType,
                $immediateSuperiorMetadata[$entityTypeEnum->value],
            );
        }

        // If the process described in Section 6.1.4.1 found no Subordinate Statements in the Trust Chain with a
        // metadata_policy claim, the metadata of the Trust Chain subject resolves simply to the metadata found
        // in its Entity Configuration, with any metadata parameters provided by the Immediate Superior applied
        // to it.
        /** @psalm-suppress RiskyTruthyFalsyComparison */
        if (empty($this->resolvedMetadataPolicy[$entityTypeEnum->value])) {
            $this->resolvedMetadata[$entityTypeEnum->value] = $leafMetadataEntityType;
            return;
        }

        // Policy application to leaf metadata.
        /**
         * @var string $policyParameterName
         * @var array<string,mixed> $policyOperations
         */
        foreach ($this->resolvedMetadataPolicy[$entityTypeEnum->value] as $policyParameterName => $policyOperations) {
            foreach (MetadataPolicyOperatorsEnum::cases() as $metadataPolicyOperatorEnum) {
                if (!array_key_exists($metadataPolicyOperatorEnum->value, $policyOperations)) {
                    continue;
                }
                /** @psalm-suppress MixedAssignment */
                $operatorValue = $policyOperations[$metadataPolicyOperatorEnum->value];
                /** @psalm-suppress MixedAssignment */
                $metadataParameterValueBeforePolicy = $this->resolveParameterValueBeforePolicy(
                    $leafMetadataEntityType,
                    $policyParameterName,
                );

                if ($metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::Value) {
                    // The metadata parameter MUST be assigned the value of the operator. When the value of the operator
                    // is null, the metadata parameter MUST be removed.
                    if (is_null($operatorValue)) {
                        unset($leafMetadataEntityType[$policyParameterName]);
                        continue;
                    }
                    $this->ensureArrayDepth($leafMetadataEntityType, $policyParameterName);
                    /** @psalm-suppress MixedAssignment */
                    $leafMetadataEntityType[$policyParameterName] = $this->resolveParameterValueAfterPolicy(
                        $operatorValue,
                        $policyParameterName,
                    );
                } elseif ($metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::Add) {
                    // The value or values of this operator MUST be added to the metadata parameter. Values that are
                    // already present in the metadata parameter MUST NOT be added another time. If the metadata
                    // parameter is absent, it MUST be initialized with the value of this operator.
                    if (!isset($leafMetadataEntityType[$policyParameterName])) {
                        /** @psalm-suppress MixedAssignment */
                        $leafMetadataEntityType[$policyParameterName] = $operatorValue;
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
                    $leafMetadataEntityType[$policyParameterName] = $this->resolveParameterValueAfterPolicy(
                        $metadataParameterValue,
                        $policyParameterName,
                    );
                } elseif ($metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::Default) {
                    // If the metadata parameter is absent, it MUST be set to the value of the operator. If the metadata
                    // parameter is present, this operator has no effect.
                    if (!isset($leafMetadataEntityType[$policyParameterName])) {
                        /** @psalm-suppress MixedAssignment */
                        $leafMetadataEntityType[$policyParameterName] = $operatorValue;
                    }
                } elseif ($metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::OneOf) {
                    // If the metadata parameter is present, its value MUST be one of those listed in the operator
                    // value.
                    if (!isset($leafMetadataEntityType[$policyParameterName])) {
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
                    if (!isset($leafMetadataEntityType[$policyParameterName])) {
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
                        unset($leafMetadataEntityType[$policyParameterName]);
                        continue;
                    }
                    /** @psalm-suppress MixedAssignment */
                    $leafMetadataEntityType[$policyParameterName] = $this->resolveParameterValueAfterPolicy(
                        $intersection,
                        $policyParameterName,
                    );
                } elseif ($metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::SupersetOf) {
                    // If the metadata parameter is present, its values MUST contain those specified in the operator
                    // value. By mathematically defining supersets, equality is included.
                    if (!isset($leafMetadataEntityType[$policyParameterName])) {
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

                    isset($leafMetadataEntityType[$policyParameterName]) || throw new MetadataPolicyException(
                        sprintf(
                            'Parameter %s is marked as essential by policy, but not present in metadata.',
                            $policyParameterName,
                        ),
                    );
                }
            }
        }

        $this->resolvedMetadata[$entityTypeEnum->value] = $leafMetadataEntityType;
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
