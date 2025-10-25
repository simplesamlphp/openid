<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\Factories;

use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\TrustChainException;
use SimpleSAML\OpenID\Federation\EntityStatement;
use SimpleSAML\OpenID\Federation\MetadataPolicyApplicator;
use SimpleSAML\OpenID\Federation\MetadataPolicyResolver;
use SimpleSAML\OpenID\Federation\TrustChain;
use SimpleSAML\OpenID\Helpers;

class TrustChainFactory
{
    public function __construct(
        protected readonly EntityStatementFactory $entityStatementFactory,
        protected readonly DateIntervalDecorator $timestampValidationLeeway,
        protected readonly MetadataPolicyResolver $metadataPolicyResolver,
        protected readonly MetadataPolicyApplicator $metadataPolicyApplicator,
        protected readonly Helpers $helpers,
    ) {
    }


    public function empty(): TrustChain
    {
        return new TrustChain(
            $this->timestampValidationLeeway,
            $this->metadataPolicyResolver,
            $this->metadataPolicyApplicator,
            $this->helpers,
        );
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromStatements(EntityStatement ...$statements): TrustChain
    {
        if (count($statements) < 3) {
            throw new TrustChainException(
                sprintf('TrustChain must have at least 3 statements, %s given.', count($statements)),
            );
        }

        $trustChain = $this->empty();

        // First item should be the leaf configuration.
        $trustChain->addLeaf(array_shift($statements));

        // Middle items should be subordinate statements.
        while (count($statements) > 1) {
            $trustChain->addSubordinate(array_shift($statements));
        }

        // Last item should be trust anchor configuration.
        ($trustAnchorStatement = array_shift($statements)) || throw new TrustChainException(
            'No Trust Anchor statement present.',
        );
        $trustChain->addTrustAnchor($trustAnchorStatement);

        return $trustChain;
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    public function fromTokens(string ...$tokens): TrustChain
    {
        $statements = array_map(
            $this->entityStatementFactory->fromToken(...),
            $tokens,
        );

        return $this->fromStatements(...$statements);
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    public function forTrustAnchor(EntityStatement $trustAnchorStatement): TrustChain
    {
        $trustChain = $this->empty();

        $trustChain->addForTrustAnchorOnly($trustAnchorStatement);

        return $trustChain;
    }
}
