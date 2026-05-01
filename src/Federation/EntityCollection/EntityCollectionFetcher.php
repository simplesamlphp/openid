<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityCollection;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\EntityDiscoveryException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;
use Throwable;

class EntityCollectionFetcher
{
    public function __construct(
        protected readonly ArtifactFetcher $artifactFetcher,
        protected readonly Helpers $helpers,
        protected readonly ?LoggerInterface $logger = null,
    ) {
    }


    /**
     * Fetch an entity collection from a remote endpoint.
     *
     * @param non-empty-string $endpointUri
     * @param array{
     *   entity_type?: string[],
     *   trust_mark_type?: string[],
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

            $decoded = $this->helpers->json()->decode($responseBody);

            if (
                !is_array($decoded) ||
                !isset($decoded[ClaimsEnum::Entities->value]) ||
                !is_array($decoded[ClaimsEnum::Entities->value])
            ) {
                throw new EntityDiscoveryException('Entity collection response is missing "entities" array.');
            }

            $entries = [];
            foreach ($decoded[ClaimsEnum::Entities->value] as $entryData) {
                if (!is_array($entryData)) {
                    continue;
                }

                /** @var array<string, mixed>|null $uiInfo */
                $uiInfo = is_array($entryData[ClaimsEnum::UiInfos->value] ?? null) ?
                $entryData[ClaimsEnum::UiInfos->value] :
                null;
                /** @var array<array<mixed>>|null $trustMarks */
                $trustMarks = is_array($entryData[ClaimsEnum::TrustMarks->value] ?? null)
                ? $entryData[ClaimsEnum::TrustMarks->value]
                : null;

                $entries[] = new EntityCollectionEntry(
                    $this->helpers->type()->ensureNonEmptyString($entryData[ClaimsEnum::Id->value] ?? null),
                    $this->helpers->type()->ensureArrayWithValuesAsNonEmptyStrings(
                        $entryData[ClaimsEnum::EntityTypes->value] ?? [],
                        ClaimsEnum::EntityTypes->value,
                    ),
                    $uiInfo,
                    $trustMarks,
                );
            }

            $next = is_string($next = $decoded[ClaimsEnum::Next->value] ?? null) ? $next : null;
            $lastUpdated = is_numeric($lastUpdated = $decoded[ClaimsEnum::LastUpdated->value] ?? null) ?
            $this->helpers->type()->ensureInt($lastUpdated) :
            null;

            return new EntityCollectionResponse(
                $entries,
                $next,
                $lastUpdated,
            );
        } catch (Throwable $throwable) {
            $message = sprintf('Unable to fetch entity collection from %s. Error: %s', $uri, $throwable->getMessage());
            $this->logger?->error($message);
            throw new EntityDiscoveryException($message, (int)$throwable->getCode(), $throwable);
        }
    }
}
