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


    /**
     * @return array<non-empty-string,\SimpleSAML\OpenID\ValueAbstracts\TrustAnchorConfig>
     */
    public function getAll(): array
    {
        return $this->trustAnchorConfigs;
    }


    /**
     * @return \ArrayIterator<string,\SimpleSAML\OpenID\ValueAbstracts\TrustAnchorConfig>
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->getAll());
    }
}
