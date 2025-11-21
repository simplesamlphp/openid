<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Helpers;

use ArrayObject;
use JsonSerializable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Exceptions\InvalidValueException;
use SimpleSAML\OpenID\Helpers\Type;

#[CoversClass(Type::class)]
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
