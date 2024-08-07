<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Decorators;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

readonly class CacheDecorator
{
    public function __construct(public CacheInterface $cache)
    {
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get(mixed $default = null, string ...$keyElements): mixed
    {
        return $this->cache->get(self::keyFor(...$keyElements), $default);
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function set(mixed $value, int|DateInterval $ttl, string ...$keyElements): void
    {
        $this->cache->set(self::keyFor(...$keyElements), $value, $ttl);
    }

    public static function keyFor(string ...$elements): string
    {
        return hash('sha256', implode('-', $elements));
    }
}
