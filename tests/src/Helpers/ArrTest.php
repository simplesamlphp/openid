<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Exceptions\OpenIdException;
use SimpleSAML\OpenID\Helpers\Arr;

#[CoversClass(Arr::class)]
final class ArrTest extends TestCase
{
    protected function sut(): Arr
    {
        return new Arr();
    }


    public function testCanEnsureArrayDepth(): void
    {
        $arr = [];
        $this->sut()->ensureArrayDepth($arr, 'a', 'b');
        $this->assertIsArray($arr['a']['b']);

        $arr = [];
        $this->sut()->ensureArrayDepth($arr, 1, 2);
        $this->assertIsArray($arr[1][2]);
    }


    public function testThrowsIfTooDeepArrayDepth(): void
    {
        $this->expectException(OpenIDException::class);
        $this->expectExceptionMessage('depth');

        $arr = [];
        $this->sut()->ensureArrayDepth($arr, ...range(0, 100));
    }


    public function testCanGetNestedValue(): void
    {
        $arr = ['a' => ['b' => ['c' => 'd']]];
        $this->assertSame(
            ['b' => ['c' => 'd']],
            $this->sut()->getNestedValue($arr, 'a'),
        );
        $this->assertSame(
            ['c' => 'd'],
            $this->sut()->getNestedValue($arr, 'a', 'b'),
        );
        $this->assertSame(
            'd',
            $this->sut()->getNestedValue($arr, 'a', 'b', 'c'),
        );
        $this->assertNull(
            $this->sut()->getNestedValue($arr, 'a', 'b', 'c', 'd'),
        );
        $this->assertNull(
            $this->sut()->getNestedValue($arr, 'b', 'c', 'd'),
        );
        $this->assertNull(
            $this->sut()->getNestedValue($arr, 0),
        );
        $this->assertNull(
            $this->sut()->getNestedValue($arr),
        );
        $this->assertNull(
            $this->sut()->getNestedValue([], 'a', 'b', 'c', 'd'),
        );
        $this->assertNull(
            $this->sut()->getNestedValue([]),
        );
    }


    public function testGetNestedValueThrowsIfTooDeep(): void
    {
        $this->expectException(OpenIDException::class);
        $this->expectExceptionMessage('depth');

        $arr = [];
        $this->sut()->getNestedValue($arr, ...range(0, 100));
    }


    public function testCanGetNestedValueReference(): void
    {
        $arr = [];
        $this->sut()->getNestedValueReference($arr, 'a', 'b', 'c');
        $this->assertIsArray($arr['a']['b']);
        $this->assertIsArray($arr['a']['b']['c']);

        $arr = ['a' => ['b' => 'c']];
        $reference = $this->sut()->getNestedValueReference($arr, 'a', 'b');
        $this->assertSame('c', $reference);
    }


    public function testGetNestedValueThrowsForNonArrayPathElements(): void
    {
        $this->expectException(OpenIDException::class);
        $this->expectExceptionMessage('non-array');

        $arr = ['a' => ['b' => 'c']];
        $this->sut()->getNestedValueReference($arr, 'a', 'b', 'c');
    }


    public function testCanSetNestedValue(): void
    {
        $arr = [];
        $this->sut()->setNestedValue($arr, 'b');
        $this->assertSame([], $arr);

        $arr = [];
        $this->sut()->setNestedValue($arr, 'b', 'a');
        $this->assertSame(['a' => 'b'], $arr);

        $arr = [];
        $this->sut()->setNestedValue($arr, 'c', 'a', 'b');
        $this->assertSame(['a' => ['b' => 'c']], $arr);
    }


    public function testCanAddNestedValue(): void
    {
        $arr = [];
        $this->sut()->addNestedValue($arr, 'b');
        $this->assertSame(['b'], $arr);

        $arr = [];
        $this->sut()->addNestedValue($arr, 'b', 'a');
        $this->assertSame(['a' => ['b']], $arr);

        $arr = ['a' => []];
        $this->sut()->addNestedValue($arr, 'b', 'a');
        $this->assertSame(['a' => ['b']], $arr);

        $arr = ['a' => ['b']];
        $this->sut()->addNestedValue($arr, 'c', 'a');
        $this->assertSame(['a' => ['b', 'c']], $arr);

        $arr = ['a' => ['b']];
        $this->sut()->addNestedValue($arr, 'c', 'a', 'b');
        $this->assertSame(['a' => ['b', 'b' => ['c']]], $arr);

        $arr = ['a' => ['b']];
        $this->sut()->addNestedValue($arr, ['c'], 'a', 'b');
        $this->assertSame(['a' => ['b', 'b' => [['c']]]], $arr);
    }


    public function testAddNestedValueThrowsForNonArrayPathElements(): void
    {
        $this->expectException(OpenIDException::class);
        $this->expectExceptionMessage('non-array');

        $arr = ['a' => 'b'];
        $this->sut()->addNestedValue($arr, 'c', 'a');
    }


    public function testIsAssociative(): void
    {
        $this->assertFalse($this->sut()->isAssociative([]));
        $this->assertFalse($this->sut()->isAssociative(['a', 'b', 'c']));
        $this->assertTrue($this->sut()->isAssociative(['a' => 'b']));
        $this->assertTrue($this->sut()->isAssociative([1 => 'b'])); // Not sequential from 0
        $this->assertTrue($this->sut()->isAssociative([0 => 'a', 2 => 'b']));
    }


    public function testIsOfArrays(): void
    {
        $this->assertTrue($this->sut()->isOfArrays([]));
        $this->assertTrue($this->sut()->isOfArrays([[], [1]]));
        $this->assertFalse($this->sut()->isOfArrays([[], 'a']));
    }


    public function testContainsKey(): void
    {
        $arr = ['a' => ['b' => ['c' => 'd']], 'e' => 'f'];
        $this->assertTrue($this->sut()->containsKey($arr, 'a'));
        $this->assertTrue($this->sut()->containsKey($arr, 'b'));
        $this->assertTrue($this->sut()->containsKey($arr, 'c'));
        $this->assertTrue($this->sut()->containsKey($arr, 'e'));
        $this->assertFalse($this->sut()->containsKey($arr, 'd'));
        $this->assertFalse($this->sut()->containsKey($arr, 'f'));
        $this->assertFalse($this->sut()->containsKey($arr, 'g'));
    }


    public function testHybridSort(): void
    {
        // Numeric keys
        $arr = [3, 1, 2];
        $this->sut()->hybridSort($arr);
        $this->assertSame([1, 2, 3], $arr);

        // String keys
        $arr = ['b' => 2, 'a' => 1, 'c' => 3];
        $this->sut()->hybridSort($arr);
        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $arr);

        // Nested
        $arr = [
            'b' => [3, 1, 2],
            'a' => ['y' => 2, 'x' => 1],
        ];
        $this->sut()->hybridSort($arr);
        $this->assertSame([
            'a' => ['x' => 1, 'y' => 2],
            'b' => [1, 2, 3],
        ], $arr);
    }


    public function testValidateMaxDepth(): void
    {
        $this->sut()->validateMaxDepth(Arr::MAX_DEPTH);
        $this->assertTrue(true);

        $this->expectException(OpenIdException::class);
        $this->expectExceptionMessage('Refusing to recurse');
        $this->sut()->validateMaxDepth(Arr::MAX_DEPTH + 1);
    }
}
