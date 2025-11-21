<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Helpers;

use SimpleSAML\OpenID\Exceptions\OpenIdException;
use Throwable;

class Random
{
    /**
     * @param string[] $blacklist
     * @return non-empty-string The generated random string in hexadecimal format.
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function string(
        int $byteLength = 40,
        ?array $blacklist = null,
        ?string $prefix = null,
        ?string $suffix = null,
        int $maxTries = 5,
    ): string {
        if ($byteLength < 1) {
            throw new OpenIdException('Random byte length can not be less than 1.');
        }

        $errors = [];

        while ($maxTries-- > 0) {
            try {
                $random = bin2hex(random_bytes($byteLength));
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
