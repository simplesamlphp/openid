<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Helpers;

use JsonSerializable;
use SimpleSAML\OpenID\Exceptions\InvalidValueException;
use Stringable;
use Traversable;

class Type
{
    /**
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function ensureString(mixed $value, ?string $context = null): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value) || $value instanceof Stringable) {
            return (string)$value;
        }

        $error = 'Unsafe string casting, aborting.';
        $error .= is_string($context) ? ' Context: ' . $context : '';
        $error .= ' Value was: ' . var_export($value, true);

        throw new InvalidValueException($error);
    }

    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function ensureNonEmptyString(mixed $value, ?string $context = null): string
    {
        $value = $this->ensureString($value);

        if ($value !== '') {
            return $value;
        }

        $error = 'Empty string value encountered, aborting.';
        $error .= is_string($context) ? ' Context: ' . $context : '';
        $error .= ' Value was: ' . var_export($value, true);

        throw new InvalidValueException($error);
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     * @return mixed[]
     */
    public function ensureArray(mixed $value, ?string $context = null): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof Traversable) {
            return iterator_to_array($value);
        }

        if ($value instanceof JsonSerializable) {
            return (array)$value->jsonSerialize();
        }

        if (is_object($value)) {
            return (array)$value;
            // Converts object properties to an array
        }

        $error = 'Unsafe array casting, aborting.';
        $error .= is_string($context) ? 'Context: ' . $context : '';
        $error .= ' Value was: ' . var_export($value, true);

        throw new InvalidValueException($error);
    }

    /**
     * @return array<string,mixed>
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function ensureArrayWithKeysAsStrings(mixed $value, ?string $context = null): array
    {
        $value = $this->ensureArray($value, $context);

        return array_combine(
            array_map(
                $this->ensureString(...),
                array_keys($value),
                array_fill(0, count($value), $context),
            ),
            $value,
        );
    }

    /**
     * @return array<non-empty-string,mixed>
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function ensureArrayWithKeysAsNonEmptyStrings(mixed $value, ?string $context = null): array
    {
        $value = $this->ensureArray($value, $context);

        return array_combine(
            array_map(
                $this->ensureNonEmptyString(...),
                array_keys($value),
                array_fill(0, count($value), $context),
            ),
            $value,
        );
    }

    /**
     * @return string[]
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function ensureArrayWithValuesAsStrings(mixed $value, ?string $context = null): array
    {
        $value = $this->ensureArray($value, $context);

        return array_map(
            $this->ensureString(...),
            $value,
            array_fill(0, count($value), $context),
        );
    }

    /**
     * @return non-empty-string[]
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function ensureArrayWithValuesAsNonEmptyStrings(mixed $value, ?string $context = null): array
    {
        $value = $this->ensureArray($value, $context);

        return array_map(
            $this->ensureNonEmptyString(...),
            $value,
            array_fill(0, count($value), $context),
        );
    }

    /**
     * @return array<string,string>
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function ensureArrayWithKeysAndValuesAsStrings(mixed $value, ?string $context = null): array
    {
        $value = $this->ensureArray($value, $context);

        return array_combine(
            array_map(
                $this->ensureString(...),
                array_keys($value),
                array_fill(0, count($value), $context),
            ),
            array_map(
                $this->ensureString(...),
                $value,
                array_fill(0, count($value), $context),
            ),
        );
    }

    /**
     * @return array<non-empty-string,non-empty-string>
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function ensureArrayWithKeysAndValuesAsNonEmptyStrings(mixed $value, ?string $context = null): array
    {
        $value = $this->ensureArray($value, $context);

        return array_combine(
            array_map(
                $this->ensureNonEmptyString(...),
                array_keys($value),
                array_fill(0, count($value), $context),
            ),
            array_map(
                $this->ensureNonEmptyString(...),
                $value,
                array_fill(0, count($value), $context),
            ),
        );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function ensureInt(mixed $value, ?string $context = null): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int)$value;
        }

        $error = 'Unsafe integer casting, aborting.';
        $error .= is_string($context) ? 'Context: ' . $context : '';
        $error .= ' Value was: ' . var_export($value, true);

        throw new InvalidValueException($error);
    }
}
