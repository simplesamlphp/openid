<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use JsonSerializable;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\EntityTypesEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\EntityStatementException;
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
     * @var array<array<string,array<string,array<string,mixed>>>>
     */
    protected array $metadataPolicies = [];

    /**
     * Resolved metadata policy per entity type.
     *
     * @var array<string,array<string,array<string,mixed>>>
     */
    protected array $resolvedMetadataPolicy = [];

    /**
     * Resolved metadata (after applying resolved policy) per entity type.
     *
     * @var array<string,null|array<string,mixed>>
     */
    protected array $resolvedMetadata = [];

    public function __construct(
        protected readonly DateIntervalDecorator $timestampValidationLeewayDecorator,
        protected readonly MetadataPolicyResolver $metadataPolicyResolver,
        protected readonly MetadataPolicyApplicator $metadataPolicyApplicator,
    ) {
    }

    /**
     * Check if the trust chain is (currently) empty, meaning there are no entity statements present in the chain.
     */
    public function isEmpty(): bool
    {
        return $this->entities === [];
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
     * @return ?array<string,mixed>
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
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
     * Get resolved chain length.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    public function getResolvedLength(): int
    {
        $this->validateIsResolved();

        return count($this->entities);
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

        if ($this->expirationTime + $this->timestampValidationLeewayDecorator->getInSeconds() < time()) {
            throw new TrustChainException(
                "Trust Chain expiration time ($this->expirationTime) is lesser than current time.",
            );
        }
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
        if ($this->entities !== []) {
            throw new TrustChainException('Trust Chain is expected to be empty at this point.');
        }
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    protected function validateIsNotEmpty(): void
    {
        if ($this->entities === []) {
            throw new TrustChainException('Trust Chain is expected to be non-empty at this point.');
        }
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    protected function validateAtLeastNumberOfEntities(int $count): void
    {
        if (count($this->entities) < $count) {
            throw new TrustChainException("Trust Chain is expected to have at least $count entity/ies at this point.");
        }
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

        if (!($previousStatement instanceof EntityStatement)) {
            throw new EntityStatementException('Unexpected statement value');
        }

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
     * @throws \SimpleSAML\OpenID\Exceptions\MetadataPolicyException
     */
    protected function gatherMetadataPolicies(EntityStatement $entityStatement): void
    {
        $policy = $this->metadataPolicyResolver->ensureFormat(
            $entityStatement->getMetadataPolicy() ?? [],
        );

        if ($policy !== []) {
            array_unshift($this->metadataPolicies, $policy);
        }
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\MetadataPolicyException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    protected function resolveMetadataFor(EntityTypesEnum $entityTypeEnum): void
    {
        // In order to be able to resolve metadata, we need to have resolved metadata policy.
        if (!array_key_exists($entityTypeEnum->value, $this->resolvedMetadataPolicy)) {
            $this->resolvedMetadataPolicy[$entityTypeEnum->value] = $this->metadataPolicyResolver->for(
                $entityTypeEnum,
                $this->metadataPolicies,
                $this->criticalMetadataPolicyOperators,
            );
        }

        // When an Entity participates in a federation or federations with one or more Entity Types, its Entity
        // Configuration MUST contain a metadata claim with JSON object values for each of the corresponding
        // Entity Type Identifiers, even if the values are the empty JSON object {} (when the Entity Type
        // has no associated metadata or Immediate Superiors supply any needed metadata).
        $leafMetadata = $this->getResolvedLeaf()->getMetadata();
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
        $immediateSuperiorMetadata = $this->getResolvedImmediateSuperior()->getMetadata();
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
            /** @var array<string,mixed> $leafMetadataEntityType */
            $this->resolvedMetadata[$entityTypeEnum->value] = $leafMetadataEntityType;
            return;
        }

        // Policy application to leaf metadata.
        /** @var array<string,mixed> $leafMetadataEntityType */
        $this->resolvedMetadata[$entityTypeEnum->value] = $this->metadataPolicyApplicator->for(
            $this->resolvedMetadataPolicy[$entityTypeEnum->value],
            $leafMetadataEntityType,
        );
    }

    /**
     * @return \SimpleSAML\OpenID\Federation\EntityStatement[]
     */
    public function getEntities(): array
    {
        return $this->entities;
    }
}
