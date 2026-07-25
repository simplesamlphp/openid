<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\TrustChainException;
use SimpleSAML\OpenID\Exceptions\TrustChainResolutionBudgetException;
use SimpleSAML\OpenID\Federation\Factories\TrustChainBagFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustChainFactory;
use Throwable;

/**
 * @see \SimpleSAML\Test\OpenID\Federation\TrustChainResolverTest
 */
class TrustChainResolver
{
    protected int $maxTrustChainDepth;

    protected int $maxAuthorityHints;

    protected int $maxTrustChainFetches;

    protected int $trustChainResolveTimeout;

    /**
     * Number of entity statement fetches still allowed for the resolution currently in progress, or null if no
     * resolution is in progress.
     */
    protected ?int $remainingTrustChainFetches = null;

    /**
     * Timestamp (seconds, with microsecond fraction) after which the resolution currently in progress must abort,
     * or null if no resolution is in progress.
     */
    protected ?float $trustChainResolveDeadline = null;


    public function __construct(
        protected readonly EntityStatementFetcher $entityStatementFetcher,
        protected readonly TrustChainFactory $trustChainFactory,
        protected readonly TrustChainBagFactory $trustChainBagFactory,
        protected readonly DateIntervalDecorator $maxCacheDurationDecorator,
        protected readonly ?CacheDecorator $cacheDecorator = null,
        protected readonly ?LoggerInterface $logger = null,
        int $maxTrustChainDepth = 10,
        int $maxAuthorityHints = 6,
        int $maxTrustChainFetches = 100,
        int $trustChainResolveTimeout = 30,
    ) {
        $this->maxTrustChainDepth = min(20, max(1, $maxTrustChainDepth));
        $this->maxAuthorityHints = min(12, max(1, $maxAuthorityHints));
        $this->maxTrustChainFetches = min(1000, max(1, $maxTrustChainFetches));
        $this->trustChainResolveTimeout = min(300, max(1, $trustChainResolveTimeout));
    }


    /**
     * Current timestamp, in seconds with microsecond fraction. Extracted so it can be controlled in tests.
     */
    protected function currentTimestamp(): float
    {
        return microtime(true);
    }


    /**
     * Open a budget window for a single resolution. Depth and authority hints are per-node limits, so on their own
     * they still allow a node count that is exponential in the depth. These two are the resolution-wide limits.
     */
    protected function startResolutionBudget(): void
    {
        $this->remainingTrustChainFetches = $this->maxTrustChainFetches;
        $this->trustChainResolveDeadline = $this->currentTimestamp() + $this->trustChainResolveTimeout;
    }


    protected function clearResolutionBudget(): void
    {
        $this->remainingTrustChainFetches = null;
        $this->trustChainResolveDeadline = null;
    }


    protected function isResolutionBudgetActive(): bool
    {
        return !is_null($this->remainingTrustChainFetches);
    }


    /**
     * Check the wall clock deadline of the resolution in progress, without charging a fetch against the budget.
     * Use before work that is potentially slow but is not an entity statement fetch.
     *
     * @param array<string,mixed> $debugInfo
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainResolutionBudgetException
     */
    protected function checkResolutionDeadline(array $debugInfo = []): void
    {
        $deadline = $this->trustChainResolveDeadline;

        // No resolution in progress, or still within the deadline.
        if (is_null($deadline) || ($this->currentTimestamp() <= $deadline)) {
            return;
        }

        $message = sprintf(
            'Trust chain resolution exceeded the maximum allowed duration of %s seconds.',
            $this->trustChainResolveTimeout,
        );
        $this->logger?->error($message, $debugInfo);
        throw new TrustChainResolutionBudgetException($message);
    }


    /**
     * Account for a single entity statement fetch against the budget of the resolution in progress.
     *
     * Cache hits are charged as well as network fetches. A warm cache removes the network cost, but the recursion
     * itself (a JWS parse and claim validation per node, plus a chain accumulated per path) is what needs bounding.
     *
     * @param array<string,mixed> $debugInfo
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainResolutionBudgetException
     */
    protected function consumeResolutionBudget(array $debugInfo = []): void
    {
        $this->checkResolutionDeadline($debugInfo);

        $remainingFetches = $this->remainingTrustChainFetches;

        if (is_null($remainingFetches)) {
            // No resolution in progress, so there is no budget to account against.
            return;
        }

        if ($remainingFetches < 1) {
            $message = sprintf(
                'Trust chain resolution exceeded the maximum allowed number of %s entity statement fetches.',
                $this->maxTrustChainFetches,
            );
            $this->logger?->error($message, $debugInfo);
            throw new TrustChainResolutionBudgetException($message);
        }

        $this->remainingTrustChainFetches = $remainingFetches - 1;
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
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainResolutionBudgetException
     */
    public function getConfigurationChains(
        string $entityId,
        array $trustAnchorIds,
        array $populatedChain = [],
        int $depth = 1,
    ): array {
        // Only the outermost call opens (and closes) the budget window, so that a call made from within for()
        // shares the budget already opened there instead of starting a fresh one.
        $ownsResolutionBudget = !$this->isResolutionBudgetActive();

        if ($ownsResolutionBudget) {
            $this->startResolutionBudget();
        }

        try {
            return $this->populateConfigurationChains($entityId, $trustAnchorIds, $populatedChain, $depth);
        } finally {
            if ($ownsResolutionBudget) {
                $this->clearResolutionBudget();
            }
        }
    }


    /**
     * Recursive worker behind getConfigurationChains(). Expects a resolution budget to be open.
     *
     * @param non-empty-string $entityId
     * @param non-empty-array<non-empty-string> $trustAnchorIds
     * @param \SimpleSAML\OpenID\Federation\EntityStatement[] $populatedChain
     * @return array<array<non-empty-string,\SimpleSAML\OpenID\Federation\EntityStatement>>
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainResolutionBudgetException
     */
    protected function populateConfigurationChains(
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

        // Deliberately outside the try below, so that budget exhaustion aborts the resolution instead of being
        // handled as a fetch error for this path.
        $this->consumeResolutionBudget($debugStartInfo);

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
                    $this->populateConfigurationChains($authorityHint, $trustAnchorIds, $currentPath, $depth + 1),
                );
            }
        } catch (TrustChainResolutionBudgetException $trustChainResolutionBudgetException) {
            // The resolution-wide budget is spent, so the whole resolution has to stop, not only this path.
            throw $trustChainResolutionBudgetException;
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
        // Every call gets its own budget window, so a long lived resolver instance (a worker reused across
        // requests) is not left with the budget spent by an earlier resolution.
        $this->startResolutionBudget();

        try {
            return $this->resolveTrustChainBag($entityId, $validTrustAnchorIds);
        } finally {
            $this->clearResolutionBudget();
        }
    }


    /**
     * Worker behind for(). Expects a resolution budget to be open.
     *
     * @param non-empty-string $entityId
     * @param non-empty-array<non-empty-string> $validTrustAnchorIds
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    protected function resolveTrustChainBag(string $entityId, array $validTrustAnchorIds): TrustChainBag
    {
        $this->validateStart($entityId, $validTrustAnchorIds);
        $debugStartInfo = ['entityId' => $entityId, 'validTrustAnchorIds' => $validTrustAnchorIds];
        $this->logger?->debug('Trust chain resolving started.', $debugStartInfo);

        $resolvedChains = [];

        foreach ($validTrustAnchorIds as $index => $validTrustAnchorId) {
            $debugCacheQueryInfo = ['entityId' => $entityId, 'validTrustAnchorId' => $validTrustAnchorId];
            // A cache lookup does not fetch anything, but it is not free either: a slow cache backend and a JWS
            // parse per cached trust chain still have to stay inside the deadline. Checked outside the try below
            // so that budget exhaustion is not handled as a cache error.
            $this->checkResolutionDeadline($debugCacheQueryInfo);
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
                    // If we only have one element in the configuration chain, check if we are dealing with the
                    // Trust Chain for Trust Anchor itself.
                    if (
                        (count($configurationChain) === 1) &&
                        (array_key_first($configurationChain) === $entityId)
                    ) {
                        // Handle the special Trust Anchor Trust Chain case.
                        $trustAnchorStatement = current($configurationChain);
                        $resolvedChains[] = $this->trustChainFactory->forTrustAnchor($trustAnchorStatement);
                    } else {
                        // Handle normal Trust Chain resolution.
                        // Reverse order so we can start from the Trust Anchor.
                        $configurationChain = array_reverse($configurationChain);
                        $currenChainElements = [];
                        $previousEntity = null;
                        foreach ($configurationChain as $id => $configurationStatement) {
                            if (array_key_first($configurationChain) === $id) {
                                // This is Trust Anchor configuration statement, we need to add it.
                                array_unshift($currenChainElements, $configurationStatement);
                            } elseif (!is_null($previousEntity)) {
                                // We have moved on from the first configuration entity in the chain, so we need to
                                // start populating subordinate statements.
                                $this->consumeResolutionBudget($debugConfigChainResolveInfo);
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
                    }
                } catch (TrustChainResolutionBudgetException $trustChainResolutionBudgetException) {
                    // The resolution-wide budget is spent, so stop instead of moving on to the next chain.
                    throw $trustChainResolutionBudgetException;
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
            throw new TrustChainException($message, $throwable->getCode(), $throwable);
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


    public function getMaxAuthorityHints(): int
    {
        return $this->maxAuthorityHints;
    }


    public function getMaxTrustChainFetches(): int
    {
        return $this->maxTrustChainFetches;
    }


    public function getTrustChainResolveTimeout(): int
    {
        return $this->trustChainResolveTimeout;
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
