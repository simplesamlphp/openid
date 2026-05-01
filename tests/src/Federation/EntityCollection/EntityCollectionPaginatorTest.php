<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\EntityCollection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionPaginator;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Helpers\Base64Url;

#[CoversClass(EntityCollectionPaginator::class)]
final class EntityCollectionPaginatorTest extends TestCase
{
    private Base64Url&MockObject $base64Url;

    private EntityCollectionPaginator $paginator;


    protected function setUp(): void
    {
        $helpers = $this->createMock(Helpers::class);
        $this->base64Url = $this->createMock(Base64Url::class);
        $helpers->method('base64Url')->willReturn($this->base64Url);
        $this->paginator = new EntityCollectionPaginator($helpers);
    }


    public function testPaginateFirstPage(): void
    {
        $entities = [
            'id1' => ['sub' => 'id1'],
            'id2' => ['sub' => 'id2'],
            'id3' => ['sub' => 'id3'],
        ];

        $this->base64Url->expects($this->once())
            ->method('encode')
            ->with('id2')
            ->willReturn('YmFzZTY0LWlkMg');

        $result = $this->paginator->paginate($entities, 2);

        $expected = [
            ClaimsEnum::Entities->value => [
                'id1' => ['sub' => 'id1'],
                'id2' => ['sub' => 'id2'],
            ],
            ClaimsEnum::Next->value => 'YmFzZTY0LWlkMg',
        ];

        $this->assertSame($expected, $result);
    }


    public function testPaginateSecondPage(): void
    {
        $entities = [
            'id1' => ['sub' => 'id1'],
            'id2' => ['sub' => 'id2'],
            'id3' => ['sub' => 'id3'],
        ];

        $this->base64Url->expects($this->once())
            ->method('decode')
            ->with('YmFzZTY0LWlkMQ')
            ->willReturn('id1');

        // No more pages after this one (limit 2, offset 1 means id2, id3 are returned)
        $result = $this->paginator->paginate($entities, 2, 'YmFzZTY0LWlkMQ');

        $expected = [
            ClaimsEnum::Entities->value => [
                'id2' => ['sub' => 'id2'],
                'id3' => ['sub' => 'id3'],
            ],
            ClaimsEnum::Next->value => null,
        ];

        $this->assertSame($expected, $result);
    }


    public function testPaginateInvalidCursor(): void
    {
        $entities = [
            'id1' => ['sub' => 'id1'],
            'id2' => ['sub' => 'id2'],
        ];

        $this->base64Url->expects($this->once())
            ->method('decode')
            ->with('invalid')
            ->willReturn('non-existent');

        // If cursor is not found, it starts from the beginning (offset 0)
        $result = $this->paginator->paginate($entities, 1, 'invalid');

        $this->assertArrayHasKey('id1', $result[ClaimsEnum::Entities->value]);
        $this->assertCount(1, $result[ClaimsEnum::Entities->value]);
    }
}
