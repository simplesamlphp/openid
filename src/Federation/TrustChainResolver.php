<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\TrustChainException;
use SimpleSAML\OpenID\Federation\Factories\TrustChainBagFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustChainFactory;
use Throwable;

class TrustChainResolver
{
    protected int $maxTrustChainDepth;

    protected int $maxAuthorityHints;

    public function __construct(
        protected readonly EntityStatementFetcher $entityStatementFetcher,
        protected readonly TrustChainFactory $trustChainFactory,
        protected readonly TrustChainBagFactory $trustChainBagFactory,
        protected readonly DateIntervalDecorator $maxCacheDurationDecorator,
        protected readonly ?CacheDecorator $cacheDecorator = null,
        protected readonly ?LoggerInterface $logger = null,
        int $maxTrustChainDepth = 10,
        int $maxAuthorityHints = 6,
    ) {
        $this->maxTrustChainDepth = min(20, max(1, $maxTrustChainDepth));
        $this->maxAuthorityHints = min(12, max(1, $maxAuthorityHints));
    }

    /**
     * Get entity configuration statements chains up to given Trust Anchors.
     *
     * @param non-empty-string $entityId
     * @param non-empty-array<non-empty-string> $trustAnchorIds
     * @param \SimpleSAML\OpenID\Federation\EntityStatement[] $populatedChain Recursively populated with configuration
     * entity statements for one chain path.
     * @param int $depth Recursively defined chain depth.
     * @return array<array<non-empty-string,\SimpleSAML\OpenID\Federation\EntityStatement>>
     */
    public function getConfigurationChains(
        string $entityId,
        array $trustAnchorIds,
        array $populatedChain = [],
        int $depth = 1,
    ): array {
        $populatedChainEntityIds = array_keys($populatedChain);
        $debugStartInfo = [
            'depth' => $depth,
            'entityId' => $entityId,
            'trustAnchorIds' => $trustAnchorIds,
            'populatedChainEntityIds' => $populatedChainEntityIds,
        ];
        $this->logger?->debug('Start getting configuration chains.', $debugStartInfo);

        $configurationChains = [];

        try {
            $this->validateStart($entityId, $trustAnchorIds);
        } catch (Throwable $throwable) {
            $this->logger?->error(
                'Error validating configuration chain fetch start condition: ' . $throwable->getMessage(),
                $debugStartInfo,
            );
            return $configurationChains;
        }

        // Check for maximum allowed depth.
        if ($depth > $this->getMaxTrustChainDepth()) {
            $this->logger?->error(
                'Maximum allowed depth reached while getting configuration chains.',
                $debugStartInfo,
            );
            return $configurationChains;
        }

        // Avoid cycles, and possibility for entities declaring authority over themselves.
        if (in_array($entityId, $populatedChainEntityIds)) {
            $this->logger?->error(
                'Possible loop detected. Duplicate configuration in chain path encountered, disregarding path.',
                $debugStartInfo,
            );
            return $configurationChains;
        }

        try {
            $this->logger?->debug('Fetching entity configuration statement.', $debugStartInfo);
            $entityConfig = $this->entityStatementFetcher->fromCacheOrWellKnownEndpoint($entityId);
            $this->logger?->debug(
                'Fetched entity configuration statement.',
                [...$debugStartInfo, 'entityConfigPayload' => $entityConfig->getPayload(),],
            );
        } catch (Throwable $throwable) {
            $this->logger?->error(
                'Unable to fetch entity configuration statement, error was: ' . $throwable->getMessage(),
                $debugStartInfo,
            );
            return $configurationChains;
        }

        if (in_array($entityId, $trustAnchorIds, true)) {
            $this->logger?->info(
                'Common trust anchor found: ' . $entityId,
                $debugStartInfo,
            );
            /** @var array<non-empty-string, \SimpleSAML\OpenID\Federation\EntityStatement> $fullConfigChain */
            $fullConfigChain = array_merge($populatedChain, [$entityId => $entityConfig]);
            $configurationChains[] = $fullConfigChain;
            $this->logger?->debug(
                'Returning configuration chain.',
                [...$debugStartInfo, 'returnedConfigChainEntityIds' => array_keys($fullConfigChain),],
            );
            return $configurationChains;
        }

        try {
            $entityAuthorityHints = $entityConfig->getAuthorityHints();

            if ((!is_array($entityAuthorityHints)) || $entityAuthorityHints === []) {
                $this->logger?->info('No common trust anchor in this path.', $debugStartInfo);
                return $configurationChains;
            }

            $this->logger?->debug(
                'There are more authority hints to process on this path.',
                [$debugStartInfo, 'entityAuthorityHints' => $entityAuthorityHints],
            );

            if (
                ($entityAuthorityHintsCount = count($entityAuthorityHints)) >
                $this->maxAuthorityHints
            ) {
                $message = sprintf(
                    'Encountered %s entity authority hints, while max %s is allowed, stopping with this path.',
                    $entityAuthorityHintsCount,
                    $this->maxAuthorityHints,
                );

                $this->logger?->error($message, $debugStartInfo);
                return $configurationChains;
            }

            foreach ($entityAuthorityHints as $authorityHint) {
                /** @var array<non-empty-string, \SimpleSAML\OpenID\Federation\EntityStatement> $currentPath */
                $currentPath = array_merge($populatedChain, [$entityId => $entityConfig]);
                $configurationChains = array_merge(
                    $configurationChains,
                    $this->getConfigurationChains($authorityHint, $trustAnchorIds, $currentPath, $depth + 1),
                );
            }
        } catch (Throwable $throwable) {
            $this->logger?->error(
                'Unable to handle entity authority hints, error was: ' . $throwable->getMessage(),
                $debugStartInfo,
            );
        }

        return $configurationChains;
    }

    /**
     * Resolve trust chains for given entity and trust anchor IDs.
     *
     * @param non-empty-string $entityId ID of the leaf (subject) entity for which to resolve the trust chain.
     * @param non-empty-array<non-empty-string> $validTrustAnchorIds IDs of the valid trust anchors.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    public function for(string $entityId, array $validTrustAnchorIds): TrustChainBag
    {
        $this->validateStart($entityId, $validTrustAnchorIds);
        $debugStartInfo = ['entityId' => $entityId, 'validTrustAnchorIds' => $validTrustAnchorIds];
        $this->logger?->debug('Trust chain resolving started.', $debugStartInfo);

        $resolvedChains = [];

        foreach ($validTrustAnchorIds as $index => $validTrustAnchorId) {
            $debugCacheQueryInfo = ['entityId' => $entityId, 'validTrustAnchorId' => $validTrustAnchorId];
            $this->logger?->debug('Checking if the trust chain exists in cache.', $debugCacheQueryInfo);
            try {
                /** @var ?string[] $tokens */
                $tokens = $this->cacheDecorator?->get(null, $entityId, $validTrustAnchorId);
                if (is_array($tokens)) {
                    $this->logger?->debug(
                        'Found trust chain tokens in cache, using it to build trust chain.',
                        [...$debugCacheQueryInfo, 'tokens' => $tokens],
                    );
                    $resolvedChains[] = $this->trustChainFactory->fromTokens(...$tokens);
                    // Unset it as valid trust anchor ID so that it is not taken into account at regular resolving.
                    unset($validTrustAnchorIds[$index]);
                    continue;
                }

                $this->logger?->debug('Trust chain does not exist in cache.', $debugCacheQueryInfo);
            } catch (Throwable $exception) {
                $this->logger?->warning(
                    'Error while trying to get trust chain from cache: ' . $exception->getMessage(),
                    $debugCacheQueryInfo,
                );
            }
        }

        if ($validTrustAnchorIds !== []) {
            $debugStandardResolveInfo = ['entityId' => $entityId, 'validTrustAnchorIds' => $validTrustAnchorIds];
            $this->logger?->debug(
                'Continuing with standard resolving for remaining valid trust anchor IDs.',
                ['entityId' => $entityId, 'validTrustAnchorIds' => $validTrustAnchorIds],
            );

            $this->logger?->debug('Start fetching all configuration chains.', $debugStandardResolveInfo);
            $configurationChains = $this->getConfigurationChains($entityId, $validTrustAnchorIds);
            $this->logger?->debug(
                sprintf('Fetched %s configuration chains.', count($configurationChains)),
                $debugStandardResolveInfo,
            );

            foreach ($configurationChains as $configurationChain) {
                $debugConfigChainResolveInfo = [
                    ...$debugStandardResolveInfo,
                    'configurationChainEntityIds' => array_keys($configurationChain),
                ];
                $this->logger?->debug('Start resolving for configuration chain.', $debugConfigChainResolveInfo);
                try {
                    // Reverse order so we can start from the Trust Anchor.
                    $configurationChain = array_reverse($configurationChain);
                    $currenChainElements = [];
                    $previousEntity = null;
                    foreach ($configurationChain as $id => $configurationStatement) {
                        if (array_key_first($configurationChain) === $id) {
                            // This is Trust Anchor configuration statement, we need to add it.
                            array_unshift($currenChainElements, $configurationStatement);
                        } elseif (!is_null($previousEntity)) {
                            // We have moved on from the first configuration entity in the chain, so we need to start
                            // populating subordinate statements.
                            array_unshift(
                                $currenChainElements,
                                $this->entityStatementFetcher->fromCacheOrFetchEndpoint($id, $previousEntity),
                            );
                        }

                        // We need to add leaf entity configuration statement as the last item in the trust chain.
                        if (array_key_last($configurationChain) === $id) {
                            array_unshift($currenChainElements, $configurationStatement);
                        }

                        $previousEntity = $configurationStatement;
                    }

                    $resolvedChains[] = $this->trustChainFactory->fromStatements(...$currenChainElements);
                } catch (Throwable $exception) {
                    $this->logger?->error(
                        sprintf(
                            'Error resolving trust chain from configuration chain, skipping. Error was: %s',
                            $exception->getMessage(),
                        ),
                        $debugConfigChainResolveInfo,
                    );
                    continue;
                }
            }
        }

        if ($resolvedChains === []) {
            $message = 'Could not resolve trust chains or no common trust anchors found.';
            $this->logger?->error($message, $debugStartInfo);
            throw new TrustChainException($message);
        }

        $this->logger?->debug(
            sprintf('Found %s trust chains, building its bag.', count($resolvedChains)),
            $debugStartInfo,
        );

        try {
            $trustChainBag = $this->trustChainBagFactory->build($this->cacheTrustChain(array_pop($resolvedChains)));
            while ($trustChain = array_pop($resolvedChains)) {
                $trustChainBag->add($this->cacheTrustChain($trustChain));
            }
        } catch (Throwable $throwable) {
            $message = 'Error building Trust Chain Bag: ' . $throwable->getMessage();
            $this->logger?->error($message, $debugStartInfo);
            throw new TrustChainException($message);
        }

        return $trustChainBag;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     * @phpstan-ignore missingType.iterableValue (We validate it here)
     */
    protected function validateStart(string $entityId, array $validTrustAnchorIds): void
    {
        $errors = [];

        if ($entityId === '' || $entityId === '0') {
            $errors[] = 'Empty entity ID.';
        }

        if ($validTrustAnchorIds === []) {
            $errors[] = 'No valid Trust Anchors provided.';
        }

        if ($errors !== []) {
            $message = 'Validation errors encountered: ' . implode(', ', $errors);
            $this->logger?->error($message);
            throw new TrustChainException($message);
        }
    }

    public function getMaxTrustChainDepth(): int
    {
        return $this->maxTrustChainDepth;
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityStatementException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    public function cacheTrustChain(TrustChain $trustChain): TrustChain
    {
        $this->cacheDecorator?->set(
            $trustChain->jsonSerialize(),
            $this->maxCacheDurationDecorator->lowestInSecondsComparedToExpirationTime(
                $trustChain->getResolvedExpirationTime(),
            ),
            $trustChain->getResolvedLeaf()->getIssuer(),
            $trustChain->getResolvedTrustAnchor()->getIssuer(),
        );

        return $trustChain;
    }
}
