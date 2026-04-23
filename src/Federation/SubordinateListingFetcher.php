<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\EntityDiscoveryException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;
use Throwable;

class SubordinateListingFetcher
{
    public function __construct(
        protected readonly ArtifactFetcher $artifactFetcher,
        protected readonly Helpers $helpers,
        protected readonly ?LoggerInterface $logger = null,
    ) {
    }


    /**
     * Fetch immediate subordinate entity IDs from a federation list endpoint.
     *
     * @param non-empty-string $listEndpointUri
     * @param array<string, string|string[]> $filters  Optional query params: entity_type, intermediate, etc.
     * @return non-empty-string[]
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityDiscoveryException
     */
    public function fetch(string $listEndpointUri, array $filters = []): array
    {
        $uri = $this->helpers->url()->withMultiValueParams($listEndpointUri, $filters);

        $this->logger?->debug('Fetching subordinate listing.', ['uri' => $uri, 'filters' => $filters]);

        try {
            $responseBody = $this->artifactFetcher->fromNetworkAsString($uri);
            $this->logger?->debug('Fetched subordinate listing from network.', ['uri' => $uri]);

            $decoded = $this->helpers->json()->decode($responseBody);

            if (!is_array($decoded)) {
                throw new EntityDiscoveryException('Subordinate listing response is not a JSON array.');
            }

            return $this->helpers->type()->ensureArrayWithValuesAsNonEmptyStrings($decoded, ClaimsEnum::Sub->value);
        } catch (Throwable $throwable) {
            $message = sprintf(
                'Unable to fetch subordinate listing from %s. Error: %s',
                $uri,
                $throwable->getMessage(),
            );
            $this->logger?->error($message);
            throw new EntityDiscoveryException($message, (int)$throwable->getCode(), $throwable);
        }
    }
}
