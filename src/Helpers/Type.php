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

        $error = $this->prepareErrorMessage(
            'Unsafe string casting, aborting.',
            $value,
            $context,
        );

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

        $error = $this->prepareErrorMessage(
            'Empty string value encountered, aborting.',
            $value,
            $context,
        );

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

        $error = $this->prepareErrorMessage(
            'Unsafe array casting, aborting.',
            $value,
            $context,
        );

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

        $error = $this->prepareErrorMessage(
            'Unsafe integer casting, aborting.',
            $value,
            $context,
        );

        throw new InvalidValueException($error);
    }

    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function enforceRegex(
        mixed $value,
        string $pattern,
        ?string $context = null,
    ): string {
        $value = $this->ensureNonEmptyString($value, $context);

        $error = $this->prepareErrorMessage(
            'Regex match failed, aborting.',
            $value,
            $context,
        );

        preg_match($pattern, $value) || throw new InvalidValueException($error);

        return $value;
    }

    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function enforceUri(
        mixed $value,
        ?string $context = null,
        string $pattern = '/^[^:]+:\/\/?([^\s\/$.?#].[^\s]*)?$/',
    ): string {
        try {
            $value = $this->enforceRegex($value, $pattern, $context);
        } catch (InvalidValueException) {
            $error = $this->prepareErrorMessage(
                'URI regex match failed, aborting.',
                $value,
                $context,
            );

            throw new InvalidValueException($error);
        }

        return $value;
    }

    /**
     * @param mixed[] $array
     * @return array<mixed[]>
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function enforceArrayOfArrays(array $array, ?string $context = null): array
    {
        foreach ($array as $value) {
            if (!is_array($value)) {
                $error = $this->prepareErrorMessage(
                    'Non-array value encountered, aborting.',
                    $array,
                    $context,
                );

                throw new InvalidValueException($error);
            }
        }

        /** @var array<mixed[]> $array */
        return $array;
    }

    /**
     * @param mixed[] $array
     * @return non-empty-array<mixed>
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function enforceNonEmptyArray(array $array, ?string $context = null): array
    {
        if ($array === []) {
            $error = $this->prepareErrorMessage(
                'Empty array encountered, aborting.',
                $array,
                $context,
            );
            throw new InvalidValueException($error);
        }

        return $array;
    }

    /**
     * @param mixed[] $array
     * @return non-empty-array<non-empty-string>
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function enforceNonEmptyArrayWithValuesAsNonEmptyStrings(array $array, ?string $context = null): array
    {
        $array = $this->ensureArrayWithValuesAsNonEmptyStrings($array, $context);
        $array = $this->enforceNonEmptyArray($array, $context);

        /** @var non-empty-array<non-empty-string> $array */
        return $array;
    }

    /**
     * @param mixed[] $array
     * @return non-empty-array<non-empty-array>
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function enforceNonEmptyArrayOfNonEmptyArrays(array $array, ?string $context = null): array
    {
        $array = $this->enforceNonEmptyArray($array, $context);

        foreach ($array as $value) {
            if (!is_array($value)) {
                $error = $this->prepareErrorMessage(
                    'Non-array value encountered, aborting.',
                    $array,
                    $context,
                );

                throw new InvalidValueException($error);
            }

            $this->enforceNonEmptyArray($value, $context);
        }

        /** @var non-empty-array<non-empty-array> $array */
        return $array;
    }

    protected function prepareErrorMessage(string $message, mixed $value, ?string $context = null): string
    {
        return $message .
        (is_string($context) ? ' Context: ' . $context : '') .
        ' Value was: ' . var_export($value, true);
    }
}
