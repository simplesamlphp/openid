<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\ValueAbstracts;

use Traversable;

/**
 * @implements \IteratorAggregate<non-empty-string,\SimpleSAML\OpenID\ValueAbstracts\TrustAnchorConfig>
 */
class TrustAnchorConfigBag implements \IteratorAggregate
{
    /**
     * @var array<non-empty-string,\SimpleSAML\OpenID\ValueAbstracts\TrustAnchorConfig>
     */
    protected array $trustAnchorConfigs;


    public function __construct(
        TrustAnchorConfig ...$trustAnchorConfigs,
    ) {
        $this->add(...$trustAnchorConfigs);
    }


    public function add(TrustAnchorConfig ...$trustAnchorConfigs): void
    {
        foreach ($trustAnchorConfigs as $trustAnchorConfig) {
            $this->trustAnchorConfigs[$trustAnchorConfig->getEntityId()] = $trustAnchorConfig;
        }
    }


    public function getByEntityId(string $entityId): ?TrustAnchorConfig
    {
        return $this->trustAnchorConfigs[$entityId] ?? null;
    }


    public function getByEntityIdOrFail(string $entityId): TrustAnchorConfig
    {
        return $this->trustAnchorConfigs[$entityId] ??
        throw new \InvalidArgumentException(sprintf("No trust anchor config found for entity ID '%s'.", $entityId));
    }


    /**
     * @return array<non-empty-string,\SimpleSAML\OpenID\ValueAbstracts\TrustAnchorConfig>
     */
    public function getAll(): array
    {
        return $this->trustAnchorConfigs;
    }


    /**
     * @return array<non-empty-string>
     */
    public function getAllEntityIds(): array
    {
        return array_keys($this->trustAnchorConfigs);
    }


    public function has(string $entityId): bool
    {
        return isset($this->trustAnchorConfigs[$entityId]);
    }


    /**
     * @return array<non-empty-string,\SimpleSAML\OpenID\ValueAbstracts\TrustAnchorConfig>
     */
    public function getInCommonWith(TrustAnchorConfigBag $otherBag): array
    {
        return array_intersect_key($this->getAll(), $otherBag->getAll());
    }


    /**
     * @return \ArrayIterator<string,\SimpleSAML\OpenID\ValueAbstracts\TrustAnchorConfig>
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->getAll());
    }
}
