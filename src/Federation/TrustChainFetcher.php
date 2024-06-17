<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\ClaimNamesEnum;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\Exceptions\TrustChainException;

class TrustChainFetcher
{
    protected int $maxConfigurationChainDepth;

    public function __construct(
        protected readonly EntityStatementFetcher $entityStatementFetcher,
        protected readonly ?LoggerInterface $logger = null,
        int $maxConfigurationChainDepth = 9,
    ) {
        $this->maxConfigurationChainDepth = min(20, max(1, $maxConfigurationChainDepth));
    }

    /**
     * @param non-empty-string $leafEntityId
     * @param non-empty-array<string> $validTrustAnchorIds
     * @return array
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    public function for(string $leafEntityId, array $validTrustAnchorIds): array
    {
        $this->validateStart($leafEntityId, $validTrustAnchorIds);

        $this->logger?->info(
            "Trust chain fetch started for leaf entity ID $leafEntityId.",
            compact('validTrustAnchorIds'),
        );

        // Fetch leaf entity configuration and find authority_hints
        $leafEntityConfiguration = $this->entityStatementFetcher->forWellKnown($leafEntityId);
        $leafEntityAuthorityHints = $leafEntityConfiguration->getPayloadClaim(ClaimNamesEnum::AuthorityHints->value);

        if (!is_array($leafEntityAuthorityHints) || empty($leafEntityAuthorityHints)) {
            $message = 'No authority hints defined in leaf entity configuraiton.';
            $this->logger?->error($message, compact('leafEntityId'));
            throw new TrustChainException($message);
        }

        $fetchedConfigurations = [$leafEntityId => $leafEntityConfiguration];
        $resolvedConfigurationChains = [];

        $this->fetchAuthorityConfigurationChain(
            $leafEntityAuthorityHints,
            $validTrustAnchorIds,
            $fetchedConfigurations,
            $resolvedConfigurationChains,
        );

        if (empty($resolvedConfigurationChains)) {
            $message = 'No common trust anchors found.';
            $this->logger?->error($message, compact('leafEntityId', 'validTrustAnchorIds'));
            throw new TrustChainException($message);
        }

        return $resolvedConfigurationChains;
    }

    public function fetchAuthorityConfigurationChain(
        array $authorityHints,
        array $validTrustAnchorIds,
        array &$fetchedConfigurations,
        array &$resolvedConfigurationChains,
        array $chainElements = [],
        int $depth = 0,
        int $chainId = 0,
    ): void {
        $this->logger?->info(
            'Fetching configurations for authorities.',
            compact('authorityHints', 'depth')
        );
        if ($depth > $this->maxConfigurationChainDepth) {
            $this->logger?->warning('Maximum allowed depth reached.', compact('depth'));
            return;
        }

        foreach ($authorityHints as $authorityHint) {
            if (array_key_exists($authorityHint, $fetchedConfigurations)) {
                // Avoid cycles, and possibility for entities declaring authority over themselves.
                $this->logger?->info('Skipping already fetched configuration.', compact('authorityHint'));
                continue;
            }
            try {
                $authorityConfiguration = $this->entityStatementFetcher->forWellKnown($authorityHint);
            } catch (FetchException) {
                $this->logger?->info('Unable to fetch configuration.', compact('authorityHint'));
                continue;
            }

            $authorityAuthorityHints = $authorityConfiguration->getPayloadClaim(ClaimNamesEnum::AuthorityHints->value);

            // If no authority hints, and this is no common trust anchor, disregard.
            if (
                (!is_array($authorityAuthorityHints) || empty($authorityAuthorityHints)) &&
                (!in_array($authorityHint, $validTrustAnchorIds))
            ) {
                $this->logger?->info('No common trust anchor in this path.', compact('authorityHint'));
                continue;
            }
            // There are authorities, so let's use this config.
            $fetchedConfigurations[$authorityHint] = $authorityConfiguration;
            $chainElements[$chainId][] = $authorityHint;

            if (in_array($authorityHint, $validTrustAnchorIds)) {
                $this->logger?->info('Common trust anchor found.', compact('authorityHint'));
                $resolvedConfigurationChains[] = $chainElements[$chainId];
                // This is common trust anchor, we don't have to bother with its authorities.
                continue;
            }

            $this->logger?->info(
                'There are more authority hints to process.',
                compact('authorityHint', 'authorityAuthorityHints'),
            );

            $this->fetchAuthorityConfigurationChain(
                $authorityAuthorityHints,
                $validTrustAnchorIds,
                $fetchedConfigurations,
                $resolvedConfigurationChains,
                $chainElements,
                $depth + 1,
                $chainId,
            );

            $chainId++;
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
