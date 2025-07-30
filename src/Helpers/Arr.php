<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Helpers;

use SimpleSAML\OpenID\Exceptions\OpenIdException;

class Arr
{
    public const MAX_DEPTH = 99;

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function validateMaxDepth(int $depth): void
    {
        if ($depth > self::MAX_DEPTH) {
            throw new OpenIdException(
                sprintf(
                    'Refusing to recurse to given depth %s. Max depth is %s.',
                    $depth,
                    self::MAX_DEPTH,
                ),
            );
        }
    }

    /**
     * Ensure the existence of nested arrays for given keys. Note that this will create / overwrite any non-array
     * nested values and make them an array.
     *
     * @param mixed[] $array
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function ensureArrayDepth(array &$array, int|string ...$keys): void
    {
        $this->validateMaxDepth(count($keys));

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
     * Get nested value reference at a given path. Creates nested arrays dynamically if the key is not present.
     *
     * @param mixed[] $array
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException If a non-array value exists on the path.
     */
    public function &getNestedValueReference(array &$array, int|string ...$keys): mixed
    {
        $this->validateMaxDepth(count($keys));

        $nested = &$array;

        foreach ($keys as $key) {
            if (!is_array($nested)) {
                throw new OpenIdException(
                    sprintf(
                        'Refusing to operate on non-array value for key: %s, path: %s, array: %s.',
                        $key,
                        implode('.', $keys),
                        var_export($array, true),
                    ),
                );
            }

            if (!isset($nested[$key])) {
                $nested[$key] = [];
            }

            $nested = &$nested[$key];
        }

        return $nested;
    }

    /**
     * Set a value at a path.
     *
     * @param mixed[] $array
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function setNestedValue(array &$array, mixed $value, int|string ...$keys): void
    {
        if (count($keys) < 1) {
            return;
        }

        $reference =& $this->getNestedValueReference($array, ...$keys);

        $reference = $value;
    }

    /**
     * @param mixed[] $array
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function addNestedValue(array &$array, mixed $value, int|string ...$keys): void
    {
        $reference =& $this->getNestedValueReference($array, ...$keys);

        if (!is_array($reference)) {
            throw new OpenIdException(
                sprintf(
                    'Refusing to add value to non-array value. Array: %s, path: %s, value: %s.',
                    var_export($array, true),
                    implode('.', $keys),
                    var_export($value, true),
                ),
            );
        }

        $reference[] = $value;
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
