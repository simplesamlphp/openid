<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\EntityDiscoveryException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;
use Throwable;

/**
 * @see \SimpleSAML\Test\OpenID\Federation\SubordinateListingFetcherTest
 */
class SubordinateListingFetcher
{
    /**
     * A listing is a flat JSON array of entity IDs, so it is larger than the entity statements and JWKS
     * documents the general fetch size limit is sized for: a big federation can list many thousands of them.
     */
    public const DEFAULT_MAX_FETCH_SIZE_BYTES = 1048576;

    public const DEFAULT_MAX_SUBORDINATES = 1000;


    protected int $maxSubordinates;

    protected int $maxFetchSizeBytes;


    /**
     * @param int $maxSubordinates Maximum number of entity IDs to take from a single listing. A listing
     * endpoint is free to return as many as it likes, and every one of them costs a well-known fetch during
     * discovery, so what is returned beyond this is dropped rather than walked.
     * @param int $maxFetchSizeBytes Maximum response body size to read for a listing.
     */
    public function __construct(
        protected readonly ArtifactFetcher $artifactFetcher,
        protected readonly Helpers $helpers,
        protected readonly DateIntervalDecorator $maxCacheDurationDecorator,
        protected readonly ?LoggerInterface $logger = null,
        int $maxSubordinates = self::DEFAULT_MAX_SUBORDINATES,
        int $maxFetchSizeBytes = self::DEFAULT_MAX_FETCH_SIZE_BYTES,
    ) {
        $this->maxSubordinates = max(1, $maxSubordinates);
        $this->maxFetchSizeBytes = max(1, $maxFetchSizeBytes);
    }


    public function getMaxSubordinates(): int
    {
        return $this->maxSubordinates;
    }


    public function getMaxFetchSizeBytes(): int
    {
        return $this->maxFetchSizeBytes;
    }


    /**
     * Fetch immediate subordinate entity IDs from a federation list endpoint.
     *
     * @param non-empty-string $listEndpointUri
     * @param array<string, string|string[]> $filters  Optional query params: entity_type, intermediate, etc.
     * @param bool $forceRefresh  If true, ignore cached listing and fetch from network.
     * @return non-empty-string[]
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityDiscoveryException
     */
    public function fetch(string $listEndpointUri, array $filters = [], bool $forceRefresh = false): array
    {
        $uri = $this->helpers->url()->withMultiValueParams($listEndpointUri, $filters);

        if (!$forceRefresh) {
            $this->logger?->debug('Checking for cached subordinate listing.', ['uri' => $uri]);
            $cached = $this->artifactFetcher->fromCacheAsString($uri);
            if (is_string($cached)) {
                $this->logger?->debug('Returning cached subordinate listing.', ['uri' => $uri]);
                return $this->decodeAndEnsureType($cached);
            }

            $this->logger?->debug('No cached subordinate listing found.', ['uri' => $uri]);
        }

        $this->logger?->debug('Fetching subordinate listing from network.', ['uri' => $uri, 'filters' => $filters]);

        try {
            $responseBody = $this->artifactFetcher->fromNetworkAsString($uri, $this->maxFetchSizeBytes);
            $this->logger?->debug('Fetched subordinate listing from network.', ['uri' => $uri]);

            $result = $this->decodeAndEnsureType($responseBody);

            $this->artifactFetcher->cacheIt(
                $responseBody,
                $this->maxCacheDurationDecorator->getInSeconds(),
                $uri,
            );

            return $result;
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


    /**
     * @return non-empty-string[]
     * @throws \SimpleSAML\OpenID\Exceptions\EntityDiscoveryException
     */
    protected function decodeAndEnsureType(string $responseBody): array
    {
        $decoded = $this->helpers->json()->decode($responseBody);

        if (!is_array($decoded)) {
            throw new EntityDiscoveryException('Subordinate listing response is not a JSON array.');
        }

        $subordinateIds = $this->helpers->type()->ensureArrayWithValuesAsNonEmptyStrings(
            $decoded,
            'Subordinate Listing',
        );

        return $this->capSubordinates($subordinateIds);
    }


    /**
     * Take at most the allowed number of entity IDs from a listing.
     *
     * Dropping the rest rather than failing keeps an oversized listing from taking the whole discovery down
     * with it, at the cost of an incomplete view, which is why it is reported.
     *
     * @param non-empty-string[] $subordinateIds
     * @return non-empty-string[]
     */
    protected function capSubordinates(array $subordinateIds): array
    {
        if (($subordinateCount = count($subordinateIds)) <= $this->maxSubordinates) {
            return $subordinateIds;
        }

        $this->logger?->warning(
            sprintf(
                'Subordinate listing returned %s entity IDs, while max %s is allowed. The listing is ' .
                'truncated, so any discovery based on it is incomplete.',
                $subordinateCount,
                $this->maxSubordinates,
            ),
        );

        return array_slice($subordinateIds, 0, $this->maxSubordinates);
    }
}
