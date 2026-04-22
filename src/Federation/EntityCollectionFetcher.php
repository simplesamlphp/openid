<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\EntityDiscoveryException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;
use Throwable;

class EntityCollectionFetcher
{
    public function __construct(
        private readonly ArtifactFetcher $artifactFetcher,
        private readonly Helpers $helpers,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }


    /**
     * Fetch an entity collection from a remote endpoint.
     *
     * @param non-empty-string $endpointUri
     * @param array{
     *   entity_type?: string[],
     *   trust_mark_type?: string,
     *   query?: string,
     *   trust_anchor?: string,
     *   entity_claims?: string[],
     *   ui_claims?: string[],
     *   limit?: positive-int,
     *   from?: string,
     * } $filters
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     * @throws \SimpleSAML\OpenID\Exceptions\EntityDiscoveryException
     */
    public function fetch(string $endpointUri, array $filters = []): EntityCollectionResponse
    {
        $uri = $this->helpers->url()->withMultiValueParams($endpointUri, $filters);

        $this->logger?->debug('Fetching entity collection.', ['uri' => $uri, 'filters' => $filters]);

        try {
            $responseBody = $this->artifactFetcher->fromNetworkAsString($uri);

            /** @var mixed $decoded */
            $decoded = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($decoded) || !isset($decoded['entities']) || !is_array($decoded['entities'])) {
                throw new EntityDiscoveryException('Entity collection response is missing "entities" array.');
            }

            $entries = [];
            foreach ($decoded['entities'] as $entryData) {
                if (!is_array($entryData)) {
                    continue;
                }

                /** @var array<string, mixed>|null $uiInfo */
                $uiInfo = is_array($entryData['ui_info'] ?? null) ? $entryData['ui_info'] : null;
                /** @var array<array<mixed>>|null $trustMarks */
                $trustMarks = is_array($entryData[ClaimsEnum::TrustMarks->value] ?? null)
                ? $entryData[ClaimsEnum::TrustMarks->value]
                : null;

                $entries[] = new EntityCollectionEntry(
                    $this->helpers->type()->ensureNonEmptyString($entryData[ClaimsEnum::Id->value] ?? null),
                    $this->helpers->type()->ensureArrayWithValuesAsNonEmptyStrings(
                        $entryData[ClaimsEnum::EntityTypes->value] ?? [],
                        'entity_types',
                    ),
                    $uiInfo,
                    $trustMarks,
                );
            }

            $lastUpdated = $decoded['last_updated'] ?? null;

            return new EntityCollectionResponse(
                $entries,
                $this->helpers->type()->getNonEmptyStringOrNull($decoded['next'] ?? null),
                is_numeric($lastUpdated) ? (int)$lastUpdated : null,
            );
        } catch (Throwable $throwable) {
            $message = sprintf('Unable to fetch entity collection from %s. Error: %s', $uri, $throwable->getMessage());
            $this->logger?->error($message);
            throw new EntityDiscoveryException($message, (int)$throwable->getCode(), $throwable);
        }
    }
}
