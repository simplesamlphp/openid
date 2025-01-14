<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use SimpleSAML\OpenID\Exceptions\TrustChainException;

class TrustChainBag
{
    /** @var \SimpleSAML\OpenID\Federation\TrustChain[] */
    protected array $trustChains = [];

    public function __construct(TrustChain $trustChain, TrustChain ...$trustChains)
    {
        $this->add($trustChain, ...$trustChains);
    }

    public function add(TrustChain $trustChain, TrustChain ...$trustChains): void
    {
        $this->trustChains[] = $trustChain;
        $this->trustChains = array_merge($this->trustChains, $trustChains);

        // Order the chains from shortest to longest one.
        usort(
            $this->trustChains,
            fn(TrustChain $a, TrustChain $b) => $a->getResolvedLength() <=> $b->getResolvedLength(),
        );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    public function getShortest(): TrustChain
    {
        ($shortestChain = reset($this->trustChains)) || throw new TrustChainException('Invalid trust chain bag.');
        return $shortestChain;
    }

    /**
     * Get the shortest Trust Chain prioritized by Trust Anchor ID. Returns null if none of the given Trust Anchor IDs
     * is found.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function getShortestByTrustAnchorPriority(string $trustAnchorId, string ...$trustAnchorIds): ?TrustChain
    {
        // Map of trust anchor identifiers to their order.
        $prioritizedTrustAnchorIds = array_flip([$trustAnchorId, ...$trustAnchorIds]);

        $prioritizedChains = $this->trustChains;

        usort($prioritizedChains, function (TrustChain $a, TrustChain $b) use ($prioritizedTrustAnchorIds) {
            // Get defined position, or default to high value if not found.
            $posA = $prioritizedTrustAnchorIds[$a->getResolvedTrustAnchor()->getIssuer()] ?? PHP_INT_MAX;
            $posB = $prioritizedTrustAnchorIds[$b->getResolvedTrustAnchor()->getIssuer()] ?? PHP_INT_MAX;

            return $posA <=> $posB;
        });

        ($prioritizedChain = reset($prioritizedChains)) || throw new TrustChainException('Invalid trust chain bag.');
        return array_key_exists($prioritizedChain->getResolvedTrustAnchor()->getIssuer(), $prioritizedTrustAnchorIds) ?
        $prioritizedChain : null;
    }

    /**
     * @return \SimpleSAML\OpenID\Federation\TrustChain[]
     */
    public function getAll(): array
    {
        return $this->trustChains;
    }

    public function getCount(): int
    {
        return count($this->trustChains);
    }
}
