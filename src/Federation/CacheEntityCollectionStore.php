<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use SimpleSAML\OpenID\Decorators\CacheDecorator;
use Throwable;

class CacheEntityCollectionStore implements EntityCollectionStoreInterface
{
    private const PREFIX = 'federation_entity_ids';


    public function __construct(
        private readonly CacheDecorator $cacheDecorator,
    ) {
    }


    public function storeEntityIds(string $trustAnchorId, array $entityIds, int $ttl): void
    {
        try {
            $this->cacheDecorator->set(
                json_encode($entityIds, JSON_THROW_ON_ERROR),
                $ttl,
                self::PREFIX,
                $trustAnchorId,
            );
        } catch (Throwable) {
            // Log if needed, or ignore for now as per ArtifactFetcher pattern
        }
    }


    public function getEntityIds(string $trustAnchorId): ?array
    {
        try {
            /** @var ?string $cached */
            $cached = $this->cacheDecorator->get(null, self::PREFIX, $trustAnchorId);

            if (is_null($cached)) {
                return null;
            }

            /** @var non-empty-string[] $decoded */
            $decoded = json_decode($cached, true, 512, JSON_THROW_ON_ERROR);

            return $decoded;
        } catch (Throwable) {
            return null;
        }
    }


    public function clearEntityIds(string $trustAnchorId): void
    {
        try {
            $this->cacheDecorator->delete(self::PREFIX, $trustAnchorId);
        } catch (Throwable) {
            // Ignore
        }
    }
}
