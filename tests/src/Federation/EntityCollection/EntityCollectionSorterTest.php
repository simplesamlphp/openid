<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\EntityCollection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Exceptions\OpenIdException;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionSorter;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Helpers\Arr;

#[CoversClass(EntityCollectionSorter::class)]
final class EntityCollectionSorterTest extends TestCase
{
    private Arr&MockObject $arr;

    private EntityCollectionSorter $sorter;


    protected function setUp(): void
    {
        $helpers = $this->createMock(Helpers::class);
        $this->arr = $this->createMock(Arr::class);
        $helpers->method('arr')->willReturn($this->arr);
        $this->sorter = new EntityCollectionSorter($helpers);
    }


    public function testSortAscending(): void
    {
        $entities = [
            'z' => ['val' => 'Zebra'],
            'a' => ['val' => 'Apple'],
            'm' => ['val' => 'Monkey'],
        ];

        $claimPaths = [['val']];

        $this->arr->method('getNestedValue')
            ->willReturnCallback(fn(array $arr, string $path): mixed => $arr[$path]);

        $result = $this->sorter->sort($entities, $claimPaths, 'asc');

        $this->assertSame(['a', 'm', 'z'], array_keys($result));
    }


    public function testSortDescending(): void
    {
        $entities = [
            'z' => ['val' => 'Zebra'],
            'a' => ['val' => 'Apple'],
            'm' => ['val' => 'Monkey'],
        ];

        $claimPaths = [['val']];

        $this->arr->method('getNestedValue')
            ->willReturnCallback(fn(array $arr, string $path): mixed => $arr[$path]);

        $result = $this->sorter->sort($entities, $claimPaths, 'desc');

        $this->assertSame(['z', 'm', 'a'], array_keys($result));
    }


    public function testSortMissingClaim(): void
    {
        $entities = [
            'a' => ['val' => 'Apple'],
            'b' => [], // Missing 'val'
        ];

        $claimPaths = [['val']];

        $this->arr->method('getNestedValue')
            ->willReturnCallback(function (array $arr, string $path) {
                if (!isset($arr[$path])) {
                    throw new OpenIdException('Missing');
                }

                return $arr[$path];
            });

        $result = $this->sorter->sort($entities, $claimPaths, 'asc');

        // 'b' (null/empty string) should come before 'a' ('Apple')
        $this->assertSame(['b', 'a'], array_keys($result));
    }


    public function testSortMultiplePaths(): void
    {
        $entities = [
            'id1' => ['v1' => 'A', 'v2' => 'B'],
            'id2' => ['v1' => 'A', 'v2' => 'A'],
        ];

        $claimPaths = [['v1'], ['v2']];

        $this->arr->method('getNestedValue')
            ->willReturnCallback(fn(array $arr, string $path): mixed => $arr[$path]);

        $result = $this->sorter->sort($entities, $claimPaths, 'asc');

        $this->assertSame(['id2', 'id1'], array_keys($result));
    }


    public function testSortEmptyEntities(): void
    {
        $this->assertSame([], $this->sorter->sort([], [['any']]));
    }


    public function testSortEqualValues(): void
    {
        $entities = [
            'id1' => ['val' => 'A'],
            'id2' => ['val' => 'A'],
        ];

        $claimPaths = [['val']];

        $this->arr->method('getNestedValue')->willReturn('A');

        $result = $this->sorter->sort($entities, $claimPaths, 'asc');

        // Order should be preserved if equal
        $this->assertSame(['id1', 'id2'], array_keys($result));
    }


    public function testSortDescendingDifferentValues(): void
    {
        $entities = [
            'id1' => ['val' => 'A'],
            'id2' => ['val' => 'B'],
        ];

        $claimPaths = [['val']];

        $this->arr->method('getNestedValue')
            ->willReturnCallback(fn(array $arr): mixed => $arr['val']);

        $result = $this->sorter->sort($entities, $claimPaths, 'desc');

        $this->assertSame(['id2', 'id1'], array_keys($result));
    }
}
