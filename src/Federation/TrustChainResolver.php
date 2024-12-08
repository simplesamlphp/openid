<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\TrustChainException;
use SimpleSAML\OpenID\Federation\Factories\TrustChainBagFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustChainFactory;
use Throwable;

class TrustChainResolver
{
    protected int $maxTrustChainDepth;
    protected int $maxLeafAuthoritiesToProcess;

    public function __construct(
        protected readonly EntityStatementFetcher $entityStatementFetcher,
        protected readonly TrustChainFactory $trustChainFactory,
        protected readonly TrustChainBagFactory $trustChainBagFactory,
        protected readonly DateIntervalDecorator $maxCacheDuration,
        protected readonly ?CacheDecorator $cacheDecorator = null,
        protected readonly ?LoggerInterface $logger = null,
        int $maxTrustChainDepth = 10,
        int $maxLeafAuthoritiesToProcess = 6,
    ) {
        $this->maxTrustChainDepth = min(20, max(1, $maxTrustChainDepth));
        $this->maxLeafAuthoritiesToProcess = min(12, max(1, $maxLeafAuthoritiesToProcess));
    }

    /**
     * @param non-empty-string $leafEntityId ID of the leaf (subject) entity for which to resolve the trust chain.
     * @param non-empty-array<non-empty-string> $validTrustAnchorIds IDs of the valid trust anchors.
     * @return \SimpleSAML\OpenID\Federation\TrustChainBag
     *
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function for(string $leafEntityId, array $validTrustAnchorIds): TrustChainBag
    {
        $this->validateStart($leafEntityId, $validTrustAnchorIds);

        $this->logger?->debug(
            "Trust chain resolving started.",
            compact('leafEntityId', 'validTrustAnchorIds'),
        );

        $resolvedChains = [];

        foreach ($validTrustAnchorIds as $index => $validTrustAnchorId) {
            $this->logger?->debug(
                'Checking if the trust chain exists in cache.',
                compact('leafEntityId', 'validTrustAnchorId'),
            );
            try {
                /** @var ?string[] $tokens */
                $tokens = $this->cacheDecorator?->get(null, $leafEntityId, $validTrustAnchorId);
                if (is_array($tokens)) {
                    $this->logger?->debug(
                        'Found trust chain tokens in cache, using it to build trust chain.',
                        compact('leafEntityId', 'validTrustAnchorId', 'tokens'),
                    );
                    $resolvedChains[] = $this->trustChainFactory->fromTokens(...$tokens);
                    // Unset it as valid trust anchor ID so that it is not taken into account at regular resolving.
                    unset($validTrustAnchorIds[$index]);
                    continue;
                }
                $this->logger?->debug(
                    'Trust chain does not exist in cache.',
                    compact('leafEntityId', 'validTrustAnchorId'),
                );
            } catch (Throwable $exception) {
                $this->logger?->warning(
                    'Error while trying to get trust chain from cache: ' . $exception->getMessage(),
                    compact('leafEntityId', 'validTrustAnchorId'),
                );
            }
        }

        if (!empty($validTrustAnchorIds)) {
            $this->logger?->debug(
                'Continuing with standard resolving for remaining valid trust anchor IDs.',
                compact('leafEntityId', 'validTrustAnchorIds'),
            );
            $this->logger?->debug('Fetching leaf entity configuration.', compact('leafEntityId'));

            $leafEntityConfiguration = $this->entityStatementFetcher->fromCacheOrWellKnownEndpoint($leafEntityId);
            /** @var ?non-empty-string[] $leafEntityAuthorityHints This is expected, validate if necessary. */
            $leafEntityAuthorityHints = $leafEntityConfiguration->getPayloadClaim(ClaimsEnum::AuthorityHints->value);

            if (!is_array($leafEntityAuthorityHints) || empty($leafEntityAuthorityHints)) {
                $message = 'No authority hints defined in leaf entity configuration.';
                $this->logger?->error($message, compact('leafEntityId'));
                throw new TrustChainException($message);
            }

            $this->logger?->debug(
                'Leaf entity configuration fetched, found its authority hints.',
                compact('leafEntityId', 'leafEntityAuthorityHints'),
            );

            if (
                ($leafEntityAuthorityHintsCount = count($leafEntityAuthorityHints)) >
                $this->maxLeafAuthoritiesToProcess
            ) {
                $message = sprintf(
                    'Encountered %s leaf entity authority hints, while %s is allowed, stopping.',
                    $leafEntityAuthorityHintsCount,
                    $this->maxLeafAuthoritiesToProcess,
                );
                $this->logger?->error($message, compact('leafEntityId'));
                throw new TrustChainException($message);
            }

            $this->resolve(
                $leafEntityId,
                $leafEntityConfiguration,
                $leafEntityAuthorityHints,
                $validTrustAnchorIds,
                $resolvedChains,
            );
        }

        if (empty($resolvedChains)) {
            $message = 'Could not resolve trust chains or no common trust anchors found.';
            $this->logger?->error($message, compact('leafEntityId', 'validTrustAnchorIds'));
            throw new TrustChainException($message);
        }

        $this->logger?->debug(
            'Trust chains exist, building its bag.',
            compact('leafEntityId'),
        );

        $trustChainBag = $this->trustChainBagFactory->build($this->cacheTrustChain(array_pop($resolvedChains)));

        while ($trustChain = array_pop($resolvedChains)) {
            $trustChainBag->add($this->cacheTrustChain($trustChain));
        }

        return $trustChainBag;
    }

    /**
     * Recursively resolve all possible trust chains.
     *
     * @param string $subordinateEntityId ID of starting subordinate entity.
     * @param \SimpleSAML\OpenID\Federation\EntityStatement $subordinateStatement Entity statement of starting
     * subordinate entity.
     * @param non-empty-string[] $authorityHints Entity IDs of current authorities for which to resolve the chains.
     * @param non-empty-string[] $validTrustAnchorIds Entity IDs of Trust Anchors that are considered valid.
     * @param array<int, \SimpleSAML\OpenID\Federation\TrustChain> $resolvedChains Array which will be
     * populated with resolved trust chains. Empty array means that no common trust anchors have been found
     * or there were problems in entity statements.
     * @param array<string,\SimpleSAML\OpenID\Federation\EntityStatement>[] &$fetchedConfigurations Array populated
     *  with fetched entity configurations, with the format [chainId => [entityId => configurationStatement]].
     * @param array<int, array<int,\SimpleSAML\OpenID\Federation\EntityStatement>> $chainElements Elements of each
     * possible chain, recursively populated during processing.
     * @param int $chainId ID of particular chain, recursively defined during processing.
     * @param int $depth Depth of the chain, recursively populated during processing.
     * @return void
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function resolve(
        string $subordinateEntityId,
        EntityStatement $subordinateStatement,
        array $authorityHints,
        array $validTrustAnchorIds,
        array &$resolvedChains,
        array &$fetchedConfigurations = [],
        array $chainElements = [],
        int $chainId = 0,
        int $depth = 0,
    ): void {
        $this->logger?->debug(
            'Resolving chain.',
            compact('chainId', 'subordinateEntityId', 'authorityHints', 'depth'),
        );
        if ($depth > $this->getMaxTrustChainDepth()) {
            $this->logger?->error(
                'Maximum allowed depth reached.',
                compact('chainId', 'subordinateEntityId', 'authorityHints', 'depth'),
            );
            return;
        }

        // Prepare / ensure array for chain elements.
        isset($chainElements[$chainId]) ?: $chainElements[$chainId] = [];
        $chainElements[$chainId][] = $subordinateStatement;

        // Prepare / ensure array for fetched entity configuration statements.
        isset($fetchedConfigurations[$chainId]) ?: $fetchedConfigurations[$chainId] = [];
        !$subordinateStatement->isConfiguration() ?:
        $fetchedConfigurations[$chainId][$subordinateEntityId] = $subordinateStatement;

        // Indicate this is initial iteration in this resolve attempt.
        $isInitialIteration = true;

        foreach ($authorityHints as $authorityHint) {
            if ($isInitialIteration) {
                $this->logger?->debug(
                    'Starting with first authority hint.',
                    compact('chainId', 'authorityHint'),
                );
                $isInitialIteration = false;
            } else {
                // Each authority change at one level means a new chain.
                $this->logger?->debug(
                    'Moving on to next authority hint and creating new chain path.',
                    compact('authorityHint'),
                );
                $newChainElements = $chainElements[$chainId];
                // Also note all fetched configurations so we can check for loops.
                $newFetchedConfigurations = $fetchedConfigurations[$chainId];
                $chainId++;
                $chainElements[$chainId] = $newChainElements;
                // First item would is leaf configuration, so leave it.
                if (count($newFetchedConfigurations) > 1) {
                    // Remove upper configuration not relevant for new chain path.
                    array_pop($newFetchedConfigurations);
                }
                $fetchedConfigurations[$chainId] = $newFetchedConfigurations;
            }

            // Avoid cycles, and possibility for entities declaring authority over themselves.
            if (
                isset($fetchedConfigurations[$chainId]) &&
                array_key_exists($authorityHint, $fetchedConfigurations[$chainId])
            ) {
                $this->logger?->warning(
                    'Already fetched configuration in current trust chain path encountered. Stopping with this path.',
                    [
                        'chainId' => $chainId,
                        'authorityHint' => $authorityHint,
                        'fetchedConfigurations' => array_keys($fetchedConfigurations[$chainId]),
                        'depth' => $depth,
                    ],
                );
                continue;
            }

            try {
                $this->logger?->debug(
                    'Fetching authority hint entity configuration.',
                    compact('chainId', 'authorityHint'),
                );
                $authorityConfiguration = $this->entityStatementFetcher->fromCacheOrWellKnownEndpoint($authorityHint);
            } catch (Throwable $exception) {
                $this->logger?->error(
                    'Unable to fetch entity configuration for authority, error was: ' . $exception->getMessage(),
                    compact('chainId', 'authorityHint'),
                );
                continue;
            }

            try {
                $this->logger?->debug(
                    'Fetching subordinate entity statement.',
                    compact('chainId', 'subordinateEntityId', 'authorityHint'),
                );
                $subordinateStatement = $this->entityStatementFetcher->fromCacheOrFetchEndpoint(
                    $subordinateEntityId,
                    $authorityConfiguration,
                );
            } catch (Throwable $exception) {
                $this->logger?->error(
                    'Unable to fetch subordinate entity statement, error was: ' . $exception->getMessage(),
                    compact('chainId', 'subordinateEntityId', 'authorityHint'),
                );
                continue;
            }

            if (in_array($authorityHint, $validTrustAnchorIds)) {
                $this->logger?->info(
                    'Common trust anchor found.',
                    compact('chainId', 'authorityHint'),
                );
                /** @psalm-suppress MixedAssignment */
                $currentChainElements = [...$chainElements[$chainId], $subordinateStatement, $authorityConfiguration];
                $this->logger?->debug(
                    'Chain entity configuration statements: ',
                    [
                        'chainId' => $chainId,
                        'fetchedConfigurations' => array_merge(
                            array_keys($fetchedConfigurations[$chainId]),
                            [$authorityHint],
                        ),
                    ],
                );
                try {
                    $resolvedChains[] = $this->trustChainFactory->fromStatements(...$currentChainElements);
                    // We have reached Trust Anchor, no need to move on this path.
                    continue;
                } catch (Throwable $exception) {
                    $this->logger?->error(
                        'Error building resolved trust chain, error was: ' . $exception->getMessage(),
                        compact('subordinateEntityId', 'authorityHint', 'currentChainElements'),
                    );
                    continue;
                }
            }

            /** @var ?array<non-empty-string> $authorityAuthorityHints */
            $authorityAuthorityHints = $authorityConfiguration->getPayloadClaim(ClaimsEnum::AuthorityHints->value);

            // If no authority hints, and this is no common trust anchor, disregard.
            if (!is_array($authorityAuthorityHints) || empty($authorityAuthorityHints)) {
                $this->logger?->info(
                    'No common trust anchor in this path.',
                    compact('chainId', 'authorityHint'),
                );
                continue;
            }

            $fetchedConfigurations[$chainId][$authorityHint] = $authorityConfiguration;
            $this->logger?->info(
                'There are more authority hints to process, moving on this path.',
                compact('chainId', 'authorityHint', 'authorityAuthorityHints'),
            );

            $this->resolve(
                $authorityHint,
                $subordinateStatement,
                $authorityAuthorityHints,
                $validTrustAnchorIds,
                $resolvedChains,
                $fetchedConfigurations,
                $chainElements,
                $chainId,
                $depth + 1,
            );
        }
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    protected function validateStart(string $leafEntityId, array $validTrustAnchorIds): void
    {
        $errors = [];

        if (empty($leafEntityId)) {
            $errors[] = 'Invalid leaf entity ID.';
        }

        if (empty($validTrustAnchorIds)) {
            $errors[] = 'No valid Trust Anchors provided.';
        }

        if (!empty($errors)) {
            $message = 'Refusing to start Trust Chain fetch: ' . implode(', ', $errors);
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
            $this->maxCacheDuration->lowestInSecondsComparedToExpirationTime($trustChain->getResolvedExpirationTime()),
            $trustChain->getResolvedLeaf()->getIssuer(),
            $trustChain->getResolvedTrustAnchor()->getIssuer(),
        );

        return $trustChain;
    }
}
