<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Decorators;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

class CacheDecorator
{
    public function __construct(public readonly CacheInterface $cache)
    {
    }


    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get(mixed $default, string $keyElement, string ...$keyElements): mixed
    {
        return $this->cache->get(self::keyFor($keyElement, ...$keyElements), $default);
    }


    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function set(mixed $value, int|DateInterval $ttl, string $keyElement, string ...$keyElements): void
    {
        $this->cache->set(self::keyFor($keyElement, ...$keyElements), $value, $ttl);
    }


    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function has(string $keyElement, string ...$keyElements): bool
    {
        return $this->cache->has(self::keyFor($keyElement, ...$keyElements));
    }


    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function delete(string $keyElement, string ...$keyElements): bool
    {
        return $this->cache->delete(self::keyFor($keyElement, ...$keyElements));
    }


    public static function keyFor(string $element, string ...$elements): string
    {
        return hash('sha256', implode('-', [$element, ...$elements]));
    }
}
