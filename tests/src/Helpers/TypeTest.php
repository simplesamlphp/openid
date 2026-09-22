<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Helpers;

use ArrayObject;
use JsonSerializable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\UriPattern;
use SimpleSAML\OpenID\Exceptions\InvalidValueException;
use SimpleSAML\OpenID\Helpers\Type;

#[CoversClass(Type::class)]
#[UsesClass(UriPattern::class)]
final class TypeTest extends TestCase
{
    protected function sut(): Type
    {
        return new Type();
    }


    public function testCanEnsureString(): void
    {
        $this->assertSame('a', $this->sut()->ensureString('a'));
        $this->assertSame('1', $this->sut()->ensureString(1));
        $this->assertSame('0', $this->sut()->ensureString(0));
        $this->assertSame('1', $this->sut()->ensureString(true));
        $this->assertSame('', $this->sut()->ensureString(false));
    }


    public function testEnsureStringThrowsForNonScalar(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Unsafe');

        $this->sut()->ensureString(null);
    }


    public function testEnsureStringThrowsForNonStringableObject(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Unsafe');

        $this->sut()->ensureString(new \stdClass());
    }


    public function testCanEnsureNonEmptyString(): void
    {
        $this->assertSame('a', $this->sut()->ensureNonEmptyString('a'));
        $this->assertSame('1', $this->sut()->ensureNonEmptyString(true));
    }


    public function testEnsureNonEmptyStringThrowsForEmptyString(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Empty');

        $this->sut()->ensureNonEmptyString('');
    }


    public function testEnsureNonEmptyStringThrowsForNull(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Unsafe');

        $this->sut()->ensureNonEmptyString(null);
    }


    public function testEnsureNonEmptyStringThrowsForFalse(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Empty');

        $this->sut()->ensureNonEmptyString(false);
    }


    public function testCanEnsureArray(): void
    {
        $this->assertSame(
            [1, 2],
            $this->sut()->ensureArray([1, 2]),
        );

        $this->assertSame(
            ['1', '2'],
            $this->sut()->ensureArray(['1', '2']),
        );

        $arrayObject = new ArrayObject([1, 2]);
        $this->assertSame(
            [1, 2],
            $this->sut()->ensureArray($arrayObject),
        );

        $jsonSerializable = new class implements JsonSerializable {
            public function jsonSerialize(): mixed
            {
                return [1, 2];
            }
        };
        $this->assertSame(
            [1, 2],
            $this->sut()->ensureArray($jsonSerializable),
        );

        $object = new class {
            public int $a = 1;

            public int $b = 2;
        };
        $this->assertSame(
            ['a' => 1, 'b' => 2],
            $this->sut()->ensureArray($object),
        );
    }


    public function testEnsureArrayThrowsForUnsafeCasting(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Unsafe');

        $this->sut()->ensureArray(null);
    }


    public function testCanEnsureArrayWithKeysAsStrings(): void
    {
        $this->assertSame(
            ['1' => 'a', '2' => 'b'],
            $this->sut()->ensureArrayWithKeysAsStrings([1 => 'a', 2 => 'b']),
        );
        $this->assertSame(
            ['1' => 1, '2' => 2],
            $this->sut()->ensureArrayWithKeysAsStrings([1 => 1, '2' => 2]),
        );
        $this->assertSame(
            ['0' => 0, '1' => 1, '2' => 2],
            $this->sut()->ensureArrayWithKeysAsStrings([0, 1, 2]),
        );

        // Test call for a nested array
        $this->assertSame(
            [['0' => 0, '1' => 1], ['0' => 2, '1' => 3]],
            array_map(
                $this->sut()->ensureArrayWithKeysAsStrings(...),
                [[0, 1], [2, 3]],
            ),
        );
    }


    public function testCanEnsureArrayWithKeysAsNonEmptyStrings(): void
    {
        $this->assertSame(
            ['1' => 'a', '2' => 'b'],
            $this->sut()->ensureArrayWithKeysAsNonEmptyStrings([1 => 'a', 2 => 'b']),
        );
        $this->assertSame(
            ['1' => 1, '2' => 2],
            $this->sut()->ensureArrayWithKeysAsNonEmptyStrings([1 => 1, '2' => 2]),
        );
        $this->assertSame(
            ['0' => 0, '1' => 1, '2' => 2],
            $this->sut()->ensureArrayWithKeysAsNonEmptyStrings([0, 1, 2]),
        );

        // Test call for a nested array
        $this->assertSame(
            [['0' => 0, '1' => 1], ['0' => 2, '1' => 3]],
            array_map(
                $this->sut()->ensureArrayWithKeysAsNonEmptyStrings(...),
                [[0, 1], [2, 3]],
            ),
        );
    }


    public function testCanEnsureArrayWithValuesAsStrings(): void
    {
        $this->assertSame(
            ['0', '1', '2'],
            $this->sut()->ensureArrayWithValuesAsStrings([0, 1, 2]),
        );
    }


    public function testCanEnsureArrayWithValuesAsNonEmptyStrings(): void
    {
        $this->assertSame(
            ['0', '1', '2'],
            $this->sut()->ensureArrayWithValuesAsNonEmptyStrings([0, 1, 2]),
        );
    }


    public function testCanEnsureArrayWithKeysAndValuesAsStrings(): void
    {
        $this->assertSame(
            ['0' => '0', '1' => '1', '2' => '2'],
            $this->sut()->ensureArrayWithKeysAndValuesAsStrings([0, 1, 2]),
        );
    }


    public function testCanEnsureArrayWithKeysAndValuesAsNonEmptyStrings(): void
    {
        $this->assertSame(
            ['0' => '0', '1' => '1', '2' => '2'],
            $this->sut()->ensureArrayWithKeysAndValuesAsNonEmptyStrings([0, 1, 2]),
        );

        // Test call for a nested array
        $this->assertSame(
            [['0' => '0', '1' => '1'], ['0' => '2', '1' => '3']],
            array_map(
                $this->sut()->ensureArrayWithKeysAndValuesAsNonEmptyStrings(...),
                [[0, 1], [2, 3]],
            ),
        );
    }


    public function testCanEnsureInt(): void
    {
        $this->assertSame(1, $this->sut()->ensureInt(1));
        $this->assertSame(1, $this->sut()->ensureInt('1'));
    }


    public function testEnsureIntThrowsForNonNull(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Unsafe');

        $this->sut()->ensureInt(null);
    }


    public function testCanEnsureNumber(): void
    {
        $this->assertSame(1, $this->sut()->ensureNumber(1));
        $this->assertSame(-1, $this->sut()->ensureNumber(-1));
        $this->assertEqualsWithDelta(1.5, $this->sut()->ensureNumber(1.5), PHP_FLOAT_EPSILON);
    }


    /**
     * Unlike ensureInt(), a numeric string is not a number, which is what makes this usable for the JSON number
     * a specification asks for.
     */
    #[DataProvider('nonNumberProvider')]
    public function testEnsureNumberThrowsForNonNumber(mixed $value): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('not a number');

        $this->sut()->ensureNumber($value);
    }


    /**
     * @return \Iterator<string, array{mixed}>
     */
    public static function nonNumberProvider(): \Iterator
    {
        yield 'numeric string' => ['1'];
        yield 'string' => ['a'];
        yield 'null' => [null];
        yield 'boolean' => [true];
        yield 'array' => [[1]];
        yield 'not a number' => [NAN];
        yield 'infinite' => [INF];
    }


    public function testCanEnforceString(): void
    {
        $this->assertSame('a', $this->sut()->enforceString('a'));
        $this->assertSame('', $this->sut()->enforceString(''));
        $this->assertSame('0', $this->sut()->enforceString('0'));
    }


    /**
     * A value ensureString() would cast is not a string to enforceString().
     *
     * @return \Iterator<string, array{mixed}>
     */
    public static function nonStringProvider(): \Iterator
    {
        yield 'int' => [1];
        yield 'float' => [1.5];
        yield 'true' => [true];
        yield 'false' => [false];
        yield 'null' => [null];
        yield 'array' => [['a']];
        yield 'stringable' => [new class () implements \Stringable {
            public function __toString(): string
            {
                return 'a';
            }
        }];
    }


    #[DataProvider('nonStringProvider')]
    public function testEnforceStringThrowsForNonString(mixed $value): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('not a string');

        $this->sut()->enforceString($value, 'context');
    }


    public function testCanEnforceNonEmptyString(): void
    {
        $this->assertSame('a', $this->sut()->enforceNonEmptyString('a'));
        $this->assertSame('0', $this->sut()->enforceNonEmptyString('0'));
    }


    public function testEnforceNonEmptyStringThrowsForEmptyString(): void
    {
        $this->expectException(InvalidValueException::class);

        $this->sut()->enforceNonEmptyString('');
    }


    public function testEnforceNonEmptyStringThrowsForNonString(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('not a string');

        $this->sut()->enforceNonEmptyString(1);
    }


    public function testCanEnforceList(): void
    {
        $this->assertSame([], $this->sut()->enforceList([]));
        $this->assertSame(['a', 1, null], $this->sut()->enforceList(['a', 1, null]));
        // The distinction goes as far as the JSON decoding does: sequential numeric keys decode into a list.
        $this->assertSame(['a', 'b'], $this->sut()->enforceList([0 => 'a', 1 => 'b']));
    }


    /**
     * @return \Iterator<string, array{mixed}>
     */
    public static function nonListProvider(): \Iterator
    {
        yield 'object' => [['a' => 'b']];
        yield 'non-sequential keys' => [[1 => 'a']];
        yield 'string' => ['a'];
        yield 'null' => [null];
        yield 'int' => [1];
    }


    #[DataProvider('nonListProvider')]
    public function testEnforceListThrowsForNonList(mixed $value): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('not a list');

        $this->sut()->enforceList($value, 'context');
    }


    public function testCanEnforceListOfNonEmptyStrings(): void
    {
        $this->assertSame([], $this->sut()->enforceListOfNonEmptyStrings([]));
        $this->assertSame(['a', '0'], $this->sut()->enforceListOfNonEmptyStrings(['a', '0']));
    }


    /**
     * @return \Iterator<string, array{mixed}>
     */
    public static function nonListOfNonEmptyStringsProvider(): \Iterator
    {
        yield 'object' => [['a' => 'b']];
        yield 'a number in the list' => [['a', 1]];
        yield 'an empty string in the list' => [['a', '']];
        yield 'null in the list' => [['a', null]];
        yield 'string' => ['a'];
    }


    #[DataProvider('nonListOfNonEmptyStringsProvider')]
    public function testEnforceListOfNonEmptyStringsThrows(mixed $value): void
    {
        $this->expectException(InvalidValueException::class);

        $this->sut()->enforceListOfNonEmptyStrings($value, 'context');
    }


    public function testCanEnforceNonEmptyListOfNonEmptyStrings(): void
    {
        $this->assertSame(['a'], $this->sut()->enforceNonEmptyListOfNonEmptyStrings(['a']));
    }


    public function testEnforceNonEmptyListOfNonEmptyStringsThrowsForEmptyList(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Empty list');

        $this->sut()->enforceNonEmptyListOfNonEmptyStrings([], 'context');
    }


    public function testCanEnforceNumericDate(): void
    {
        $this->assertSame(1731175727, $this->sut()->enforceNumericDate(1731175727));
        $this->assertEqualsWithDelta(1731175727.5, $this->sut()->enforceNumericDate(1731175727.5), PHP_FLOAT_EPSILON);
        $this->assertSame(0, $this->sut()->enforceNumericDate(0));
        $this->assertSame(-1, $this->sut()->enforceNumericDate(-1));
        $this->assertSame(Type::MAX_NUMERIC_DATE, $this->sut()->enforceNumericDate(Type::MAX_NUMERIC_DATE));
        $this->assertSame(Type::MAX_NUMERIC_DATE, 2 ** 53);
    }


    /**
     * @return \Iterator<string, array{mixed}>
     */
    public static function nonNumericDateProvider(): \Iterator
    {
        yield 'numeric string' => ['1731175727'];
        yield 'beyond the representable range' => [1e100];
        yield 'beyond the representable range, negative' => [-1e100];
        yield 'one past the bound' => [Type::MAX_NUMERIC_DATE + 1];
        yield 'infinite' => [INF];
        yield 'not a number' => [NAN];
        yield 'null' => [null];
        yield 'bool' => [true];
    }


    #[DataProvider('nonNumericDateProvider')]
    public function testEnforceNumericDateThrows(mixed $value): void
    {
        $this->expectException(InvalidValueException::class);

        $this->sut()->enforceNumericDate($value, 'context');
    }


    public function testCanEnforceRegex(): void
    {
        $this->assertSame('a', $this->sut()->enforceRegex('a', '/^a$/'));
    }


    public function testEnforceRegexThrowsForInvalidValue(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Regex');

        $this->sut()->enforceRegex('a', '/^b$/');
    }


    public function testCanEnforceUri(): void
    {
        $this->assertSame('https://example.com', $this->sut()->enforceUri('https://example.com'));
    }


    /**
     * PCRE lets $ match immediately before a trailing newline unless the pattern says otherwise, so a URI
     * ending in one used to pass and keep the newline. These values reach a fetch, a log line and the `id`
     * of an issued credential, depending on which caller was validating.
     */
    public function testEnforceUriThrowsForATrailingNewline(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('URI');

        $this->sut()->enforceUri("did:web:example.org\n");
    }


    public function testEnforceUriThrowsForATrailingNewlineOnAnHttpUri(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('URI');

        $this->sut()->enforceUri(
            "https://example.com/issuer\n",
            null,
            UriPattern::HttpNoQueryNoFragment->value,
        );
    }


    public function testEnforceUriThrowsForInvalidValue(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('URI');

        $this->sut()->enforceUri('a');
    }


    public function testCanEnforceArrayOfArrays(): void
    {
        $a = ['a' => ['b' => 'c']];
        $this->assertSame($a, $this->sut()->enforceArrayOfArrays($a));
    }


    public function testEnforceArrayOfArraysThrowsForInvalidValue(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Non-array');
        $this->sut()->enforceArrayOfArrays(['a' => 'b']);
        ;
    }


    public function testCanEnforceNonEmptyArray(): void
    {
        $this->assertSame(['a'], $this->sut()->enforceNonEmptyArray(['a']));
    }


    public function testEnforceNonEmptyArrayThrowsForInvalidValue(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Empty');
        $this->sut()->enforceNonEmptyArray([]);
    }


    public function testCanEnforceNonEmptyArrayWithValuesAsNonEmptyStrings(): void
    {
        $this->assertSame(['a'], $this->sut()->enforceNonEmptyArrayWithValuesAsNonEmptyStrings(['a']));
    }


    public function testEnforceNonEmptyArrayWithValuesAsNonEmptyStringsThrowsForInvalidValue(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Empty');
        $this->sut()->enforceNonEmptyArrayWithValuesAsNonEmptyStrings([]);
    }


    public function testCanEnforceNonEmptyArrayOfNonEmptyArrays(): void
    {
        $a = [['a' => 'b']];
        $this->assertSame($a, $this->sut()->enforceNonEmptyArrayOfNonEmptyArrays($a));
    }


    public function testEnforceNonEmptyArrayOfNonEmptyArraysThrowsForInvalidValue(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Non-array');
        $this->sut()->enforceNonEmptyArrayOfNonEmptyArrays(['a' => 'b']);
    }
}
