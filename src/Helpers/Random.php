<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Helpers;

use SimpleSAML\OpenID\Exceptions\OpenIdException;
use Throwable;

class Random
{
    /**
     * @param string[] $blacklist
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function string(
        int $length = 40,
        ?array $blacklist = null,
        ?string $prefix = null,
        ?string $suffix = null,
        int $maxTries = 5,
    ): string {
        if ($length < 1) {
            throw new OpenIdException('Random string length can not be less than 1.');
        }

        $errors = [];

        while ($maxTries-- > 0) {
            try {
                $random = bin2hex(random_bytes($length));
                // @codeCoverageIgnoreStart
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
                continue;
            }

            // @codeCoverageIgnoreEnd

            if ($blacklist !== null && in_array($random, $blacklist, true)) {
                $errors[] = sprintf(
                    'Random string %s is in the blacklist [%s], skipping.',
                    $random,
                    implode(', ', $blacklist),
                );
                continue;
            }

            return ($prefix ?? '') . $random . ($suffix ?? '');
        }

        throw new OpenIdException(
            'Could not generate a random string, errors were: ' . implode(', ', $errors) . '.',
        );
    }
}
