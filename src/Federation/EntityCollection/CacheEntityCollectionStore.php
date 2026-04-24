<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation\EntityCollection;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Helpers;
use Throwable;

class CacheEntityCollectionStore implements EntityCollectionStoreInterface
{
    protected const PREFIX = 'federation_entities';


    public function __construct(
        protected readonly CacheDecorator $cacheDecorator,
        protected readonly Helpers $helpers,
        protected readonly ?LoggerInterface $logger,
    ) {
    }


    public function store(string $trustAnchorId, array $entities, int $ttl): void
    {
        try {
            $this->cacheDecorator->set(
                $this->helpers->json()->encode($entities),
                $ttl,
                self::PREFIX,
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


    public function get(string $trustAnchorId): ?array
    {
        try {
            /** @var ?string $cached */
            $cached = $this->cacheDecorator->get(null, self::PREFIX, $trustAnchorId);

            if (is_null($cached)) {
                return null;
            }

            $decoded = $this->helpers->json()->decode($cached);

            if (!is_array($decoded)) {
                return null;
            }

            /** @var array<string, array<string, mixed>> $decoded */
            return $decoded;
        } catch (Throwable $throwable) {
            $this->logger?->error('Unable to retrieve entities from cache.', [
                'trustAnchorId' => $trustAnchorId,
                'exception_message' => $throwable->getMessage(),
            ]);
            return null;
        }
    }


    public function clear(string $trustAnchorId): void
    {
        try {
            $this->cacheDecorator->delete(self::PREFIX, $trustAnchorId);
        } catch (Throwable $throwable) {
            $this->logger?->error('Unable to clear entities from cache.', [
                'trustAnchorId' => $trustAnchorId,
                'exception_message' => $throwable->getMessage(),
            ]);
        }
    }
}
