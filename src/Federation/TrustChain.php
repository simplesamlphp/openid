<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use JsonSerializable;
use SimpleSAML\OpenID\Codebooks\ClaimNamesEnum;
use SimpleSAML\OpenID\Codebooks\EntityTypeEnum;
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
     * @var array
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
        throw new TrustChainException('Empty entity statement encountered.');

        return $leaf;
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
     */
    public function getResolvedMetadata(EntityTypeEnum $entityTypeEnum): ?array
    {
        $this->validateIsResolved();

        // If we have already resolved the metadata for the given entity type, return it.
        if (
            array_key_exists($entityTypeEnum->value, $this->resolvedMetadata) &&
            is_array($this->resolvedMetadata[$entityTypeEnum->value])
        ) {
            return $this->resolvedMetadata[$entityTypeEnum->value];
        }

        // In order to be able to resolve metadata, we need to have resolved metadata policy.
        if (!array_key_exists($entityTypeEnum->value, $this->resolvedMetadataPolicy)) {
            $this->resolveMetadataPolicyFor($entityTypeEnum);
        }

        dd($this->resolvedMetadataPolicy);

        // TODO mivanci Resolve metadata

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
        $operators = (array)($entityStatement->getPayloadClaim(ClaimNamesEnum::MetadataPolicyCritical->value) ?? []);
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
        $policy = (array)($entityStatement->getPayloadClaim(ClaimNamesEnum::MetadataPolicy->value) ?? []);

        if (!empty($policy)) {
            array_unshift($this->metadataPolicies, $policy);
        }
    }

    /**
     * // TODO mivanci move to metadata policy resolver, inject it.
     * @throws \SimpleSAML\OpenID\Exceptions\MetadataPolicyException
     */
    protected function resolveMetadataPolicyFor(EntityTypeEnum $entityTypeEnum): void
    {
        $currentPolicy = [];
        $supportedOperators = MetadataPolicyOperatorsEnum::values();

        foreach ($this->metadataPolicies as $metadataPolicy) {
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
            foreach ($nextPolicy as $nextPolicyParameter => $nextPolicyParameterOperations) {
                $nextPolicyParameterOperatorKeys = array_keys($nextPolicyParameterOperations);
                // Order of operators is important, per specification. Method cases() will return as cases are defined.
                foreach (MetadataPolicyOperatorsEnum::cases() as $metadataPolicyOperatorEnum) {
                    if (in_array($metadataPolicyOperatorEnum->value, $nextPolicyParameterOperatorKeys)) {
                        $operatorValue = $nextPolicyParameterOperations[$metadataPolicyOperatorEnum->value];
                        $operatorValueType = gettype($operatorValue);
                        // Check common policy resolving rules for each supported operator.
                        // If operator value type is not supported, throw.
                        $metadataPolicyOperatorEnum->isOperatorValueTypeSupported($operatorValueType) ||
                        throw new MetadataPolicyException(
                            sprintf(
                                'Unsupported operator type encountered for %s: %s',
                                $metadataPolicyOperatorEnum->value,
                                $operatorValueType,
                            ),
                        );
                        // If operator combination is not allowed, throw.
                        $metadataPolicyOperatorEnum->isOperatorCombinationSupported($nextPolicyParameterOperatorKeys) ||
                        throw new MetadataPolicyException(
                            sprintf(
                                'Unsupported operator combination encountered for %s: %s',
                                $metadataPolicyOperatorEnum->value,
                                implode(', ', $nextPolicyParameterOperatorKeys),
                            ),
                        );

                        // Check specific policy resolving rules for each supported operator.
                        // If everything is ok, set is as / merge it with current policy.
                        if ($metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::Value) {
                            // No special resolving rules, we can try and merge it.
                            if (!isset($currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value])) {
                                // It doesn't exist yet, so we can simply add it.
                                $this->ensureArrayDepth(
                                    $currentPolicy,
                                    $nextPolicyParameter,
                                    $metadataPolicyOperatorEnum->value,
                                );
                                $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value] =
                                $operatorValue;
                            } elseif (
                                $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value] !==
                                $operatorValue
                            ) {
                                // The values can only be the same, otherwise we have to throw.
                                throw new MetadataPolicyException(
                                    sprintf(
                                        'Different operator values encountered for operator %s: %s !== %s.',
                                        $metadataPolicyOperatorEnum->value,
                                        $currentPolicy[$nextPolicyParameter][$metadataPolicyOperatorEnum->value],
                                        $operatorValue,
                                    ),
                                );
                            }
                        } elseif ($metadataPolicyOperatorEnum === MetadataPolicyOperatorsEnum::Add) {
                            // TODO mivanci continue
                        }

                        // TODO For enforcing:
                        // TODO If param is defined but type is not supported, throw (when enforcing)
                        // TODO special case for scope parameter (when enforcing)
                    }
                }
            }

            // TODO
        }

        $this->resolvedMetadataPolicy[$entityTypeEnum->value] = $currentPolicy;
    }

    /**
     * TODO mivanci move to Arr helper method, inject helpers
     */
    protected function ensureArrayDepth(array &$array, int|string ...$keys,): void
    {
        if (count($keys) > 99) {
            throw new TrustChainException('Refusing to recurse to given depth.');
        }

        /** @var int|string|null $key */
        $key = array_shift($keys);

        if (!$key) {
            return;
        }

        if (!isset($array[$key]) || !is_array($array[$key])) {
            $array[$key] = [];
        }

        $this->ensureArrayDepth($array[$key], ...$keys);
    }
}
