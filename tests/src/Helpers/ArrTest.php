<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Exceptions\OpenIdException;
use SimpleSAML\OpenID\Helpers\Arr;

#[CoversClass(Arr::class)]
class ArrTest extends TestCase
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

    public function testThrowsIfTooDeep(): void
    {
        $this->expectException(OpenIDException::class);
        $this->expectExceptionMessage('depth');

        $arr = [];
        $this->sut()->ensureArrayDepth($arr, ...range(0, 100));
    }

    public function testCanEnsureStringKeys(): void
    {
        $this->assertSame(
            ['1' => 'a', '2' => 'b'],
            $this->sut()->ensureStringKeys([1 => 'a', 2 => 'b']),
        );
        $this->assertSame(
            ['1' => 1, '2' => 2],
            $this->sut()->ensureStringKeys([1 => 1, '2' => 2]),
        );
        $this->assertSame(
            ['0' => 0, '1' => 1, '2' => 2],
            $this->sut()->ensureStringKeys([0, 1, 2]),
        );

        // Test call for nested array
        $this->assertSame(
            [['0' => 0, '1' => 1], ['0' => 2, '1' => 3]],
            array_map(
                $this->sut()->ensureStringKeys(...),
                [[0, 1], [2, 3]],
            ),
        );
    }
}
