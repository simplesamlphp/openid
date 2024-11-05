<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Decorators;

use DateInterval;
use DateTimeImmutable;

class DateIntervalDecorator
{
    public int $inSeconds;

    public function __construct(public readonly DateInterval $dateInterval)
    {
        $this->inSeconds = self::toSeconds($this->dateInterval);
    }

    public function lowestInSecondsComparedToExpirationTime(int $expirationTime): int
    {
        $timestamp = time();
        $expirationInSeconds = max($timestamp, $expirationTime) - $timestamp;

        return min($this->inSeconds, $expirationInSeconds);
    }

    public static function toSeconds(DateInterval $dateInterval): int
    {
        $reference = new DateTimeImmutable();
        $endTime = $reference->add($dateInterval);

        return $endTime->getTimestamp() - $reference->getTimestamp();
    }

    public function getInSeconds(): int
    {
        return $this->inSeconds;
    }
}
