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
}
