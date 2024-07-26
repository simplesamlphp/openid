<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

class TrustChainBag
{
    /** @var array<\SimpleSAML\OpenID\Federation\TrustChain> */
    protected array $bag = [];

    public function add(TrustChain $trustChain): void
    {
        $this->bag[] = $trustChain;

        // Order the chains from shortest to longest one.
        usort($this->bag, function (array $a, array $b) {
            return count($a) - count($b);
        });
    }

    public function getAll(): array
    {
        return $this->bag;
    }
}