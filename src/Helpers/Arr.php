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

        if (!$key) {
            return;
        }

        if (!isset($array[$key]) || !is_array($array[$key])) {
            $array[$key] = [];
        }

        $this->ensureArrayDepth($array[$key], ...$keys);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     * @param mixed[] $array
     */
    public function getNestedValue(array $array, int|string ...$keys): mixed
    {
        if (count($keys) > 99) {
            throw new OpenIdException('Refusing to recurse to given depth.');
        }

        if (count($keys) < 1) {
            return null;
        }

        if (count($keys) === 1) {
            return $array[array_shift($keys)] ?? null;
        }

        $key = array_shift($keys);

        if (!is_array($nestedArray = $array[$key] ?? null)) {
            return null;
        }

        return $this->getNestedValue($nestedArray, ...$keys);
    }

    /**
     * @param mixed[] $array
     */
    public function isAssociative(array $array): bool
    {
        // Has at least one string key or non-sequential numeric keys
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Is array of arrays.
     * @param mixed[] $array
     */
    public function isOfArrays(array $array): bool
    {
        foreach ($array as $value) {
            if (!is_array($value)) {
                return false;
            }
        }

        return true;
    }
}
