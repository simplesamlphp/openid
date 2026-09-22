<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Helpers;

use JsonSerializable;
use SimpleSAML\OpenID\Codebooks\UriPattern;
use SimpleSAML\OpenID\Exceptions\InvalidValueException;
use Stringable;
use Traversable;

/**
 * @see \SimpleSAML\Test\OpenID\Helpers\TypeTest
 */
class Type
{
    /**
     * Largest magnitude of an RFC 7519 NumericDate that a JSON number carries exactly and that survives the
     * conversion to a PHP integer, being 2 ** 53. Some 285 million years of seconds, so no legitimate timestamp
     * comes near it.
     */
    public const MAX_NUMERIC_DATE = 9007199254740992;


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
     * @return mixed[]
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
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
     * Ensures the value is a JSON number, meaning an integer or a float, and not a string which merely looks like
     * one. Use this instead of ensureInt() wherever a specification calls for a JSON number: RFC 7519 NumericDate
     * claims such as `iat` and `exp`, and the Status List Token `ttl` claim, all of which a numeric string does
     * not satisfy.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function ensureNumber(mixed $value, ?string $context = null): int|float
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value) && is_finite($value)) {
            return $value;
        }

        $error = $this->prepareErrorMessage(
            'Value is not a number, aborting.',
            $value,
            $context,
        );

        throw new InvalidValueException($error);
    }


    /**
     * Enforces that the value is a JSON string, as distinct from ensureString(), which casts a number, a boolean
     * or a Stringable into one. Use this wherever a value is acted on as an identifier a producer signed: a
     * subject of `true` is not the subject "1", and a client identifier of 42 is not the client "42".
     *
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function enforceString(mixed $value, ?string $context = null): string
    {
        if (is_string($value)) {
            return $value;
        }

        $error = $this->prepareErrorMessage(
            'Value is not a string, aborting.',
            $value,
            $context,
        );

        throw new InvalidValueException($error);
    }


    /**
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function enforceNonEmptyString(mixed $value, ?string $context = null): string
    {
        return $this->ensureNonEmptyString($this->enforceString($value, $context), $context);
    }


    /**
     * Enforces that the value is a JSON array, which decodes into a PHP list, as distinct from ensureArray(),
     * which also accepts what a JSON object decodes into. Note that the distinction only goes as far as the
     * decoding does: a JSON object whose keys happen to be the sequence 0, 1, 2 … decodes into a list as well.
     *
     * @return list<mixed>
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function enforceList(mixed $value, ?string $context = null): array
    {
        if (is_array($value) && array_is_list($value)) {
            return $value;
        }

        $error = $this->prepareErrorMessage(
            'Value is not a list, aborting.',
            $value,
            $context,
        );

        throw new InvalidValueException($error);
    }


    /**
     * @return list<non-empty-string>
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function enforceListOfNonEmptyStrings(mixed $value, ?string $context = null): array
    {
        $list = $this->enforceList($value, $context);

        return array_map(
            fn(mixed $entry): string => $this->enforceNonEmptyString($entry, $context),
            $list,
        );
    }


    /**
     * @return non-empty-list<non-empty-string>
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function enforceNonEmptyListOfNonEmptyStrings(mixed $value, ?string $context = null): array
    {
        $list = $this->enforceListOfNonEmptyStrings($value, $context);

        if ($list === []) {
            $error = $this->prepareErrorMessage(
                'Empty list encountered, aborting.',
                $value,
                $context,
            );

            throw new InvalidValueException($error);
        }

        return $list;
    }


    /**
     * Enforces that the value is an RFC 7519 NumericDate: a JSON number (ensureNumber(), so a numeric string does
     * not satisfy it) whose magnitude a PHP integer can hold exactly. The bound is what stops a nonsensical value
     * from becoming a plausible one: casting a float beyond the integer range yields 0 rather than saturating, so
     * an `exp` of 1e100 would otherwise read as the epoch. A fraction of a second is accepted, as RFC 7519 allows
     * one; whether it is kept or truncated is the caller's to decide.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function enforceNumericDate(mixed $value, ?string $context = null): int|float
    {
        $number = $this->ensureNumber($value, $context);

        if ($number < -self::MAX_NUMERIC_DATE || $number > self::MAX_NUMERIC_DATE) {
            $error = $this->prepareErrorMessage(
                'Value is not a usable NumericDate, it is outside the representable range, aborting.',
                $value,
                $context,
            );

            throw new InvalidValueException($error);
        }

        return $number;
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
        string $pattern = UriPattern::Uri->value,
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
