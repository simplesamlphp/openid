<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityCollection;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Helpers;
use Throwable;

class CacheEntityCollectionStore implements EntityCollectionStoreInterface
{
    protected const KEY_FEDERATED_ENTITIES = 'federation_entities';

    protected const KEY_LAST_UPDATED = 'last_updated';


    public function __construct(
        protected readonly CacheDecorator $cacheDecorator,
        protected readonly Helpers $helpers,
        protected readonly ?LoggerInterface $logger,
    ) {
    }


    /**
     * @inheritDoc
     */
    public function store(string $trustAnchorId, array $entities, int $ttl): void
    {
        try {
            $this->cacheDecorator->set(
                $entities,
                $ttl,
                self::KEY_FEDERATED_ENTITIES,
                $trustAnchorId,
            );
        } catch (Throwable $throwable) {
            $this->logger?->error('Unable to store entities in cache.', [
                'trustAnchorId' => $trustAnchorId,
                'entities' => $entities,
                'exception_message' => $throwable->getMessage(),
            ]);
        }
    }


    /**
     * @inheritDoc
     */
    public function get(string $trustAnchorId): ?array
    {
        try {
            $cached = $this->cacheDecorator->get(null, self::KEY_FEDERATED_ENTITIES, $trustAnchorId);

            if (!is_array($cached)) {
                return null;
            }

            /** @var array<string, array<string, mixed>> $cached */
            return $cached;
        } catch (Throwable $throwable) {
            $this->logger?->error('Unable to retrieve entities from cache.', [
                'trustAnchorId' => $trustAnchorId,
                'exception_message' => $throwable->getMessage(),
            ]);
            return null;
        }
    }


    /**
     * @inheritDoc
     */
    public function clear(string $trustAnchorId): void
    {
        try {
            $this->cacheDecorator->delete(self::KEY_FEDERATED_ENTITIES, $trustAnchorId);
        } catch (Throwable $throwable) {
            $this->logger?->error('Unable to clear entities from cache.', [
                'trustAnchorId' => $trustAnchorId,
                'exception_message' => $throwable->getMessage(),
            ]);
        }
    }


    /**
     * @inheritDoc
     */
    public function storeLastUpdated(string $trustAnchorId, int $timestamp, int $ttl): void
    {
        try {
            $this->cacheDecorator->set(
                (string)$timestamp,
                $ttl,
                self::KEY_LAST_UPDATED,
                $trustAnchorId,
            );
        } catch (Throwable $throwable) {
            $this->logger?->error('Unable to store last updated timestamp in cache.', [
                'trustAnchorId' => $trustAnchorId,
                'timestamp' => $timestamp,
                'exception_message' => $throwable->getMessage(),
            ]);
        }
    }


    /**
     * @inheritDoc
     */
    public function getLastUpdated(string $trustAnchorId): ?int
    {
        try {
            $lastUpdated = $this->cacheDecorator->get(null, self::KEY_LAST_UPDATED, $trustAnchorId);
        } catch (Throwable $throwable) {
            $this->logger?->error('Unable to retrieve last updated timestamp from cache.', [
                'trustAnchorId' => $trustAnchorId,
                'exception_message' => $throwable->getMessage(),
            ]);
            return null;
        }

        if (is_int($lastUpdated)) {
            return $lastUpdated;
        }

        return null;
    }


    /**
     * @inheritDoc
     */
    public function clearLastUpdated(string $trustAnchorId): void
    {
        try {
            $this->cacheDecorator->delete(self::KEY_LAST_UPDATED, $trustAnchorId);
        } catch (Throwable $throwable) {
            $this->logger?->error('Unable to clear last updated timestamp from cache.', [
                'trustAnchorId' => $trustAnchorId,
                'exception_message' => $throwable->getMessage(),
            ]);
        }
    }
}
