<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Federation\EntityCollection;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionFilter;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionPaginator;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionSorter;

#[CoversClass(EntityCollection::class)]
final class EntityCollectionTest extends TestCase
{
    private EntityCollectionFilter&MockObject $filter;

    private EntityCollectionSorter&MockObject $sorter;

    private EntityCollectionPaginator&MockObject $paginator;


    protected function setUp(): void
    {
        $this->filter = $this->createMock(EntityCollectionFilter::class);
        $this->sorter = $this->createMock(EntityCollectionSorter::class);
        $this->paginator = $this->createMock(EntityCollectionPaginator::class);
    }


    public function testGetEntities(): void
    {
        $entities = ['https://idp.example.com' => ['sub' => 'https://idp.example.com']];
        $collection = new EntityCollection(
            $this->filter,
            $this->sorter,
            $this->paginator,
            $entities,
        );

        $this->assertSame($entities, $collection->getEntities());
    }


    public function testGetLastUpdated(): void
    {
        $lastUpdated = 123456789;
        $collection = new EntityCollection(
            $this->filter,
            $this->sorter,
            $this->paginator,
            [],
            null,
            $lastUpdated,
        );

        $this->assertSame($lastUpdated, $collection->getLastUpdated());
    }


    public function testGetNextPageToken(): void
    {
        $token = 'opaque-token';
        $collection = new EntityCollection(
            $this->filter,
            $this->sorter,
            $this->paginator,
            [],
            $token,
        );

        $this->assertSame($token, $collection->getNextPageToken());
    }


    public function testToCollectionEndpointResponseArray(): void
    {
        $entities = [
            'https://idp.example.com' => [
                ClaimsEnum::Sub->value => 'https://idp.example.com',
                ClaimsEnum::Metadata->value => [
                    'openid_provider' => [
                        'issuer' => 'https://idp.example.com',
                    ],
                ],
                ClaimsEnum::TrustMarks->value => [
                    ['id' => 'mark1'],
                ],
            ],
            'https://rp.example.com' => [
                ClaimsEnum::Sub->value => 'https://rp.example.com',
                // No metadata
            ],
            'https://broken.example.com' => [
                ClaimsEnum::Sub->value => 'https://broken.example.com',
                ClaimsEnum::Metadata->value => 'invalid-metadata', // Not an array
            ],
        ];

        $lastUpdated = 1620000000;
        $nextToken = 'next-page';

        $collection = new EntityCollection(
            $this->filter,
            $this->sorter,
            $this->paginator,
            $entities,
            $nextToken,
            $lastUpdated,
        );

        $expected = [
            ClaimsEnum::Entities->value => [
                [
                    ClaimsEnum::EntityId->value => 'https://idp.example.com',
                    ClaimsEnum::EntityTypes->value => ['openid_provider'],
                    ClaimsEnum::UiInfos->value => [
                        'openid_provider' => [
                            'issuer' => 'https://idp.example.com',
                        ],
                    ],
                    ClaimsEnum::TrustMarks->value => [
                        ['id' => 'mark1'],
                    ],
                ],
                [
                    ClaimsEnum::EntityId->value => 'https://rp.example.com',
                    ClaimsEnum::EntityTypes->value => [],
                ],
                [
                    ClaimsEnum::EntityId->value => 'https://broken.example.com',
                    ClaimsEnum::EntityTypes->value => [],
                ],
            ],
            ClaimsEnum::Next->value => $nextToken,
            ClaimsEnum::LastUpdated->value => $lastUpdated,
        ];

        $this->assertSame($expected, $collection->toCollectionEndpointResponseArray());
    }


    public function testToCollectionEndpointResponseArrayWithNulls(): void
    {
        $collection = new EntityCollection(
            $this->filter,
            $this->sorter,
            $this->paginator,
            [],
        );

        $expected = [
            ClaimsEnum::Entities->value => [],
        ];

        $this->assertSame($expected, $collection->toCollectionEndpointResponseArray());
    }


    public function testFilter(): void
    {
        $entities = ['a' => []];
        $criteria = ['entity_type' => ['openid_provider']];
        $filtered = ['b' => []];

        $this->filter->expects($this->once())
            ->method('filter')
            ->with($entities, $criteria)
            ->willReturn($filtered);

        $collection = new EntityCollection(
            $this->filter,
            $this->sorter,
            $this->paginator,
            $entities,
        );

        $result = $collection->filter($criteria);

        $this->assertSame($collection, $result);
        $this->assertSame($filtered, $collection->getEntities());
    }


    public function testSort(): void
    {
        $entities = ['a' => []];
        $claimPaths = [['metadata', 'openid_provider', 'organization_name']];
        $sortOrder = 'asc';
        $sorted = ['b' => []];

        $this->sorter->expects($this->once())
            ->method('sort')
            ->with($entities, $claimPaths, $sortOrder)
            ->willReturn($sorted);

        $collection = new EntityCollection(
            $this->filter,
            $this->sorter,
            $this->paginator,
            $entities,
        );

        $result = $collection->sort($claimPaths, $sortOrder);

        $this->assertSame($collection, $result);
        $this->assertSame($sorted, $collection->getEntities());
    }


    public function testPaginate(): void
    {
        $entities = ['a' => [], 'b' => []];
        $limit = 1;
        $from = 'cursor';
        $paginatedEntities = ['b' => []];
        $nextToken = 'next-cursor';

        $this->paginator->expects($this->once())
            ->method('paginate')
            ->with($entities, $limit, $from)
            ->willReturn([
                'entities' => $paginatedEntities,
                'next' => $nextToken,
            ]);

        $collection = new EntityCollection(
            $this->filter,
            $this->sorter,
            $this->paginator,
            $entities,
        );

        $result = $collection->paginate($limit, $from);

        $this->assertSame($collection, $result);
        $this->assertSame($paginatedEntities, $collection->getEntities());
        $this->assertSame($nextToken, $collection->getNextPageToken());
    }
}
