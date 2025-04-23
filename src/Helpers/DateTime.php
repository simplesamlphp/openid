<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Helpers;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class DateTime
{
    /**
     * @throws \Exception
     */
    public function parseXsDateTime(string $input, ?DateTimeZone $tz = null): DateTimeImmutable
    {
        // Try extended RFC3339 (with microseconds)
        $dt = DateTimeImmutable::createFromFormat(
            DateTimeInterface::RFC3339_EXTENDED,
            $input,
            $tz,
        );
        if ($dt !== false) {
            return $dt;
        }

        // Fall back to standard atom format (no microseconds)
        $dt = DateTimeImmutable::createFromFormat(
            DateTimeInterface::ATOM,
            $input,
            $tz,
        );
        if ($dt !== false) {
            return $dt;
        }

        // Finally, let the constructor attempt flexible ISO8601 parsing
        return new DateTimeImmutable($input, $tz);
    }
}
