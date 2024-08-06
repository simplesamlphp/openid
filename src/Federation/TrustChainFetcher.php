<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\ClaimNamesEnum;
use SimpleSAML\OpenID\Exceptions\TrustChainException;
use SimpleSAML\OpenID\Factories\TrustChainFactory;
use Throwable;

class TrustChainFetcher
{
    protected int $maxConfigurationChainDepth;

    public function __construct(
        protected readonly EntityStatementFetcher $entityStatementFetcher,
        protected readonly TrustChainFactory $trustChainFactory,
        protected readonly ?LoggerInterface $logger = null,
        int $maxConfigurationChainDepth = 9,
    ) {
        $this->maxConfigurationChainDepth = min(20, max(1, $maxConfigurationChainDepth));
    }

    /**
     * @param non-empty-string $leafEntityId ID of the leaf (subject) entity for which to resolve the trust chain.
     * @param non-empty-array<non-empty-string> $validTrustAnchorIds IDs of the valid trust anchors.
     * @return \SimpleSAML\OpenID\Federation\TrustChain
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    public function for(string $leafEntityId, array $validTrustAnchorIds): TrustChain
    {
        $this->validateStart($leafEntityId, $validTrustAnchorIds);

        $this->logger?->info(
            "Trust chain fetch started.",
            compact('leafEntityId', 'validTrustAnchorIds'),
        );

        // Fetch leaf entity configuration and find authority_hints
        $leafEntityConfiguration = $this->entityStatementFetcher->fromCacheOrWellKnownEndpoint($leafEntityId);
        /** @var ?non-empty-string[] $leafEntityAuthorityHints This is expected, validate if necessary. */
        $leafEntityAuthorityHints = $leafEntityConfiguration->getPayloadClaim(ClaimNamesEnum::AuthorityHints->value);

        if (!is_array($leafEntityAuthorityHints) || empty($leafEntityAuthorityHints)) {
            $message = 'No authority hints defined in leaf entity configuration.';
            $this->logger?->error($message, compact('leafEntityId'));
            throw new TrustChainException($message);
        }

        $fetchedConfigurations = [$leafEntityId => $leafEntityConfiguration];
        $resolvedChains = [];

        $this->resolve(
            $leafEntityId,
            $leafEntityConfiguration,
            $leafEntityAuthorityHints,
            $validTrustAnchorIds,
            $fetchedConfigurations,
            $resolvedChains,
        );

        if (empty($resolvedChains)) {
            $message = 'Could not resolve trust chains or no common trust anchors found.';
            $this->logger?->error($message, compact('leafEntityId', 'validTrustAnchorIds'));
            throw new TrustChainException($message);
        }

        $this->logger?->debug(
            'Trust chains resolved.',
            compact('leafEntityId', 'validTrustAnchorIds'),
        );

        // Order the chains from shortest to longest one.
        usort($resolvedChains, function (array $a, array $b) {
            return count($a) - count($b);
        });

        ($shortestChain = reset($resolvedChains)) || throw new TrustChainException('Invalid trust chain.');


        // TODO mivanci trust chain expiration check
        // TODO mivanci cache trust chain (and check if in cache before resolving)
        return $this->trustChainFactory->fromStatements(...$shortestChain);
    }

    /**
     * Recursively resolve all possible trust chains.
     *
     * @param string $subordinateEntityId ID of starting subordinate entity.
     * @param \SimpleSAML\OpenID\Federation\EntityStatement $subordinateStatement Entity statement of starting
     * subordinate entity.
     * @param non-empty-string[] $authorityHints Entity IDs of current authorities for which to resolve the chains.
     * @param non-empty-string[] $validTrustAnchorIds Entity IDs of Trust Anchors that are considered valid.
     * @param array<string,\SimpleSAML\OpenID\Federation\EntityStatement> &$fetchedConfigurations Array which will be
     * populated with fetched entity configurations, with the  key being the entity ID, and value being entity
     * configuration statement.
     * @param array<int, array<int,\SimpleSAML\OpenID\Federation\EntityStatement>> $resolvedChains Array which will be
     * populated with resolved configuration chains. Empty array means that no common trust anchors have been found
     * or there were problems in entity statements.
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
        array &$fetchedConfigurations,
        array &$resolvedChains,
        array $chainElements = [],
        int $chainId = 0,
        int $depth = 0,
    ): void {
        $this->logger?->debug(
            'Resolving chain.',
            compact('subordinateEntityId', 'authorityHints', 'chainId', 'depth'),
        );
        if ($depth > $this->getMaxConfigurationChainDepth()) {
            $this->logger?->error(
                'Maximum allowed depth reached.',
                compact('subordinateEntityId', 'authorityHints', 'chainId', 'depth'),
            );
            return;
        }

        // Prepare / ensure array for chain elements.
        isset($chainElements[$chainId]) ?: $chainElements[$chainId] = [];
        $chainElements[$chainId][] = $subordinateStatement;

        foreach ($authorityHints as $authorityHint) {
            if (array_key_exists($authorityHint, $fetchedConfigurations)) {
                // Avoid cycles, and possibility for entities declaring authority over themselves.
                $this->logger?->info('Skipping already fetched configuration.', compact('authorityHint'));
                return;
            }
            try {
                $authorityConfiguration = $this->entityStatementFetcher->fromCacheOrWellKnownEndpoint($authorityHint);
                $fetchedConfigurations[$authorityHint] = $authorityConfiguration;
            } catch (Throwable $exception) {
                $this->logger?->info(
                    'Unable to fetch configuration: ' . $exception->getMessage(),
                    compact('authorityHint'),
                );
                return;
            }

            try {
                $subordinateStatement = $this->entityStatementFetcher->fromCacheOrFetchEndpoint(
                    $subordinateEntityId,
                    $authorityConfiguration,
                );
            } catch (Throwable $exception) {
                $this->logger?->error(
                    'Unable to fetch subordinate statement: ' . $exception->getMessage(),
                    compact('subordinateEntityId', 'authorityHint'),
                );
                return;
            }

            if (in_array($authorityHint, $validTrustAnchorIds)) {
                $this->logger?->info('Common trust anchor found.', compact('authorityHint'));
                /** @psalm-suppress MixedAssignment */
                $chainElements[$chainId][] = $subordinateStatement;
                $chainElements[$chainId][] = $authorityConfiguration;
                $resolvedChains[] = $chainElements[$chainId];
                // This is common trust anchor, we don't have to bother with its authorities.
                return;
            }

            /** @var ?array<non-empty-string> $authorityAuthorityHints */
            $authorityAuthorityHints = $authorityConfiguration->getPayloadClaim(ClaimNamesEnum::AuthorityHints->value);

            // If no authority hints, and this is no common trust anchor, disregard.
            if (!is_array($authorityAuthorityHints) || empty($authorityAuthorityHints)) {
                $this->logger?->info('No common trust anchor in this path.', compact('authorityHint'));
                return;
            }

            $this->logger?->info(
                'There are more authority hints to process.',
                compact('authorityHint', 'authorityAuthorityHints'),
            );

            $this->resolve(
                $authorityHint,
                $subordinateStatement,
                $authorityAuthorityHints,
                $validTrustAnchorIds,
                $fetchedConfigurations,
                $resolvedChains,
                $chainElements,
                $chainId,
                $depth + 1,
            );

            // Each authority at single level means a new chain.
            $newChain = $chainElements[$chainId];
            $chainId++;
            $chainElements[$chainId] = $newChain;
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

    public function getMaxConfigurationChainDepth(): int
    {
        return $this->maxConfigurationChainDepth;
    }
}
