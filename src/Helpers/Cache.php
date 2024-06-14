<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Helpers;

class Cache
{
    /**
     * @param non-empty-string|non-empty-array<string> $elements
     * @return string
     */
    public function keyFor(string ...$elements): string
    {
        return hash('sha256', implode('-', $elements));
    }

    public function maxDuration(\DateInterval $maxCacheDuration, int $expiration): int
    {
        $timestamp = (new \DateTimeImmutable())->getTimestamp();
        $expiration = min($timestamp, $expiration);
        return min($maxCacheDuration->s, $expiration - $timestamp);
    }
}
