<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\EntityCollection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Federation\EntityCollection\EntityCollectionFilter;
use SimpleSAML\OpenID\Helpers;

#[CoversClass(EntityCollectionFilter::class)]
final class EntityCollectionFilterTest extends TestCase
{
    private EntityCollectionFilter $filter;


    protected function setUp(): void
    {
        $this->filter = new EntityCollectionFilter($this->createStub(Helpers::class));
    }


    public function testFilterByEntityType(): void
    {
        $entities = [
            'idp' => [
                ClaimsEnum::Metadata->value => [
                    'openid_provider' => [],
                ],
            ],
            'rp' => [
                ClaimsEnum::Metadata->value => [
                    'openid_relying_party' => [],
                ],
            ],
            'both' => [
                ClaimsEnum::Metadata->value => [
                    'openid_provider' => [],
                    'openid_relying_party' => [],
                ],
            ],
            'none' => [
                ClaimsEnum::Metadata->value => [],
            ],
            'invalid' => [
                ClaimsEnum::Metadata->value => 'string',
            ],
        ];

        // Filter by openid_provider
        $result = $this->filter->filter($entities, ['entity_type' => ['openid_provider']]);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('idp', $result);
        $this->assertArrayHasKey('both', $result);

        // Filter by openid_relying_party
        $result = $this->filter->filter($entities, ['entity_type' => ['openid_relying_party']]);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('rp', $result);
        $this->assertArrayHasKey('both', $result);

        // Filter by both
        $result = $this->filter->filter($entities, ['entity_type' => ['openid_provider', 'openid_relying_party']]);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('idp', $result);
        $this->assertArrayHasKey('rp', $result);
        $this->assertArrayHasKey('both', $result);
    }


    public function testFilterByTrustMarkType(): void
    {
        $entities = [
            'm1' => [
                ClaimsEnum::TrustMarks->value => [
                    [ClaimsEnum::TrustMarkType->value => 'type1'],
                ],
            ],
            'm12' => [
                ClaimsEnum::TrustMarks->value => [
                    [ClaimsEnum::TrustMarkType->value => 'type1'],
                    [ClaimsEnum::TrustMarkType->value => 'type2'],
                ],
            ],
            'm2' => [
                ClaimsEnum::TrustMarks->value => [
                    [ClaimsEnum::TrustMarkType->value => 'type2'],
                ],
            ],
            'none' => [],
            'invalid' => [
                ClaimsEnum::TrustMarks->value => 'string',
            ],
        ];

        // Filter by type1
        $result = $this->filter->filter($entities, ['trust_mark_type' => ['type1']]);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('m1', $result);
        $this->assertArrayHasKey('m12', $result);

        // Filter by type1 AND type2
        $result = $this->filter->filter($entities, ['trust_mark_type' => ['type1', 'type2']]);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('m12', $result);
    }


    public function testFilterByQuery(): void
    {
        $entities = [
            'idp' => [
                ClaimsEnum::Sub->value => 'https://idp.example.com',
                ClaimsEnum::Metadata->value => [
                    'openid_provider' => [
                        ClaimsEnum::DisplayName->value => 'Example IdP',
                        ClaimsEnum::OrganizationName->value => 'Example Org',
                    ],
                ],
            ],
            'rp' => [
                ClaimsEnum::Sub->value => 'https://rp.example.com',
                ClaimsEnum::Metadata->value => [
                    'openid_relying_party' => [
                        ClaimsEnum::DisplayName->value => 'Example RP',
                    ],
                ],
            ],
            'other' => [
                ClaimsEnum::Sub->value => 'https://other.example.com',
                ClaimsEnum::Metadata->value => [
                    'federation_entity' => [
                        ClaimsEnum::OrganizationName->value => 'Other Org',
                    ],
                ],
            ],
        ];

        // Query by sub
        $result = $this->filter->filter($entities, ['query' => 'idp']);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('idp', $result);

        // Query by display_name
        $result = $this->filter->filter($entities, ['query' => 'IdP']);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('idp', $result);

        // Query by organization_name
        $result = $this->filter->filter($entities, ['query' => 'Other']);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('other', $result);

        // Query with no results
        $result = $this->filter->filter($entities, ['query' => 'nomatch']);
        $this->assertCount(0, $result);
    }


    public function testFilterWithInvalidMetadataStructures(): void
    {
        $entities = [
            'invalid_metadata' => [
                ClaimsEnum::Metadata->value => 'not-an-array',
            ],
            'invalid_trustmarks' => [
                ClaimsEnum::TrustMarks->value => 'not-an-array',
            ],
            'invalid_type_payload' => [
                ClaimsEnum::Metadata->value => [
                    'openid_provider' => 'not-an-array',
                ],
            ],
        ];

        // invalid_metadata and invalid_trustmarks are excluded (return false on line 50)
        // invalid_type_payload is INCLUDED because isset($metadata['openid_provider']) is true
        $this->assertCount(1, $this->filter->filter($entities, ['entity_type' => ['openid_provider']]));

        // all are excluded for trust_mark_type
        $this->assertCount(0, $this->filter->filter($entities, ['trust_mark_type' => ['type1']]));

        // all are excluded for query
        $this->assertCount(0, $this->filter->filter($entities, ['query' => 'something']));
    }
}
