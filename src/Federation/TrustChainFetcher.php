<?php

declare(strict_types=1);

namespace SimpleSAML\OIDT\Federation;

use GuzzleHttp\Client;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use SimpleSAML\OIDT\Exceptions\TrustChainException;

class TrustChainFetcher
{
    public function __construct(
        private ClientInterface $httpClient = new Client(),
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @param string $leafEntityId
     * @param array<string> $validTrustAnchorIds
     * @return array
     * @throws \SimpleSAML\OIDT\Exceptions\TrustChainException
     */
    public function for(string $leafEntityId, array $validTrustAnchorIds): array
    {
        $this->validateBeginning($leafEntityId, $validTrustAnchorIds);

        $this->logger?->info(
            "Trust chain fetch started for leaf entity ID $leafEntityId with valid Trust Anchor IDs being " .
            implode(', ', $validTrustAnchorIds),
        );


        return [];
    }

    /**
     * @throws \SimpleSAML\OIDT\Exceptions\TrustChainException
     */
    private function validateBeginning(string $leafEntityId, array $validTrustAnchorIds)
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
