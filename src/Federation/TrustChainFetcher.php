<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Exceptions\TrustChainException;

class TrustChainFetcher
{
    public function __construct(
        private readonly EntityStatementFetcher $entityStatementFetcher,
        private readonly ?LoggerInterface $logger = null,
    ) {
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
            "Trust chain fetch started for leaf entity ID $leafEntityId with valid Trust Anchor IDs being " .
            implode(', ', $validTrustAnchorIds),
        );

        // Fetch leaf entity configuration and find authority_hints
        $leafEntityConfiguration = $this->entityStatementFetcher->forWellKnown($leafEntityId);

        return [$leafEntityConfiguration];
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\TrustChainException
     */
    private function validateStart(string $leafEntityId, array $validTrustAnchorIds): void
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
}
