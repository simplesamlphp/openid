<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Helpers;

use SimpleSAML\OpenID\Exceptions\OpenIdException;

class Arr
{
    /**
     * @phpstan-ignore missingType.iterableValue (We can handle mixed type)
     */
    public function ensureArrayDepth(array &$array, int|string ...$keys): void
    {
        if (count($keys) > 99) {
            throw new OpenIdException('Refusing to recurse to given depth.');
        }

        $key = array_shift($keys);

        /** @psalm-suppress RiskyTruthyFalsyComparison */
        if (!$key) {
            return;
        }

        if (!isset($array[$key]) || !is_array($array[$key])) {
            $array[$key] = [];
        }

        $this->ensureArrayDepth($array[$key], ...$keys);
    }

    /**
     * @return array<string,mixed>
     * @phpstan-ignore missingType.iterableValue (We can handle mixed type)
     */
    public function ensureStringKeys(array $array): array
    {
        return array_combine(
            array_map('strval', array_keys($array)),
            $array,
        );
    }
}
