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
}
