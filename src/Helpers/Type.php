<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Helpers;

use SimpleSAML\OpenID\Exceptions\InvalidValueException;
use Stringable;

class Type
{
    /**
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function ensureString(mixed $value, ?string $context = null): string
    {
        if (is_scalar($value) || $value instanceof Stringable) {
            return (string)$value;
        }

        $error = 'Unsafe string casting, aborting.';
        $error .= is_string($context) ? " Context: $context" : '';
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

        if ($value !== '' && $value !== '0') {
            return $value;
        }

        $error = 'Empty string value encountered, aborting.';
        $error .= is_string($context) ? " Context: $context" : '';
        $error .= ' Value was: ' . var_export($value, true);

        throw new InvalidValueException($error);
    }

    /**
     * @param array<mixed> $values // Bc of phpstan.
     * @return string[]
     */
    public function ensureStrings(array $values, ?string $context = null): array
    {
        return array_map(
            $this->ensureString(...),
            $values,
            array_fill(0, count($values), $context),
        );
    }

    /**
     * @param array<mixed> $values // Bc of phpstan.
     * @return non-empty-string[]
     */
    public function ensureNonEmptyStrings(array $values, ?string $context = null): array
    {
        return array_map(
            $this->ensureNonEmptyString(...),
            $values,
            array_fill(0, count($values), $context),
        );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function ensureInt(mixed $value, ?string $context = null): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        $error = 'Unsafe integer casting, aborting.';
        $error .= is_string($context) ? "Context: $context" : '';
        $error .= ' Value was: ' . var_export($value, true);

        throw new InvalidValueException($error);
    }
}
