<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Factories;

use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Federation\EntityStatement;
use SimpleSAML\OpenID\Federation\TrustChain;
use SimpleSAML\OpenID\Helpers;

class TrustChainFactory
{
    public function __construct(
        protected EntityStatementFactory $entityStatementFactory,
        protected DateIntervalDecorator $timestampValidationLeeway,
        protected Helpers $helpers,
    ) {
    }

    public function empty(): TrustChain
    {
        return new TrustChain(
            $this->timestampValidationLeeway,
            $this->helpers,
        );
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

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    public function fromTokens(string ...$tokens): TrustChain
    {
        $statements = array_map(
            fn(string $token): EntityStatement => $this->entityStatementFactory->fromToken($token),
            $tokens,
        );

        return $this->fromStatements(...$statements);
    }
}
