<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityCollection;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Helpers;
use Throwable;

class CacheEntityCollectionStore implements EntityCollectionStoreInterface
{
    protected const PREFIX = 'federation_entity_ids';


    public function __construct(
        protected readonly CacheDecorator $cacheDecorator,
        protected readonly Helpers $helpers,
        protected readonly ?LoggerInterface $logger,
    ) {
    }


    public function storeEntityIds(string $trustAnchorId, array $entityIds, int $ttl): void
    {
        try {
            $this->cacheDecorator->set(
                $this->helpers->json()->encode($entityIds),
                $ttl,
                self::PREFIX,
                $trustAnchorId,
            );
        } catch (Throwable $throwable) {
            $this->logger?->error('Unable to store entity IDs in cache.', [
                'trustAnchorId' => $trustAnchorId,
                'entityIds' => $entityIds,
                'exception_message' => $throwable->getMessage(),
            ]);
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

            $decoded = $this->helpers->json()->decode($cached);
            return $this->helpers->type()->ensureArrayWithValuesAsNonEmptyStrings($decoded);
        } catch (Throwable $throwable) {
            $this->logger?->error('Unable to retrieve entity IDs from cache.', [
                'trustAnchorId' => $trustAnchorId,
                'exception_message' => $throwable->getMessage(),
            ]);
            return null;
        }
    }


    public function clearEntityIds(string $trustAnchorId): void
    {
        try {
            $this->cacheDecorator->delete(self::PREFIX, $trustAnchorId);
        } catch (Throwable $throwable) {
            $this->logger?->error('Unable to clear entity IDs from cache.', [
                'trustAnchorId' => $trustAnchorId,
                'exception_message' => $throwable->getMessage(),
            ]);
        }
    }
}
