<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Factories;

use SimpleSAML\OpenID\Federation\EntityStatement;
use SimpleSAML\OpenID\Federation\TrustChain;

class TrustChainFactory
{
    public function empty(): TrustChain
    {
        return new TrustChain();
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromStatements(EntityStatement ...$statements): TrustChain
    {
        $trustChain = $this->empty();

        // First item should be the leaf configuration.
        $trustChain->addLeaf(array_shift($statements));

        // Middle items should be subordinate statements.
        while (count($statements) > 1) {
            $trustChain->addSubordinate(array_shift($statements));
        }

        // Last item should be trust anchor configuration.
        $trustChain->addTrustAnchor(array_shift($statements));

        return $trustChain;
    }
}
