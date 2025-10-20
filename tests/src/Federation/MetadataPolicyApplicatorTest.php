<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\MetadataPolicyOperatorsEnum;
use SimpleSAML\OpenID\Exceptions\MetadataPolicyException;
use SimpleSAML\OpenID\Federation\MetadataPolicyApplicator;
use SimpleSAML\OpenID\Helpers;

#[CoversClass(MetadataPolicyApplicator::class)]
#[UsesClass(MetadataPolicyOperatorsEnum::class)]
final class MetadataPolicyApplicatorTest extends TestCase
{
    protected MockObject $helpersMock;

    protected array $metadataPolicySample = [
        'grant_types' => [
            'default' => [
                0 => 'authorization_code',
            ],
            'superset_of' => [
                0 => 'authorization_code',
            ],
            'subset_of' => [
                0 => 'authorization_code',
            ],
        ],
        'token_endpoint_auth_method' => [
            'one_of' => [
                0 => 'self_signed_tls_client_auth',
            ],
            'essential' => true,
        ],
        'token_endpoint_auth_signing_alg' => [
            'one_of' => [
                0 => 'PS256',
                1 => 'ES256',
            ],
        ],
        'subject_type' => [
            'value' => 'pairwise',
        ],
        'contacts' => [
            'add' => [
                0 => 'helpdesk@federation.example.org',
                1 => 'helpdesk@org.example.org',
            ],
        ],
    ];

    protected array $metadataSample = [
        'redirect_uris' => [
            0 => 'https://rp.example.org/callback',
        ],
        'response_types' => [
            0 => 'code',
        ],
        'token_endpoint_auth_method' => 'self_signed_tls_client_auth',
        'sector_identifier_uri' => 'https://org.example.org/sector-ids.json',
        'policy_uri' => 'https://org.example.org/policy.html',
        'contacts' => [
            0 => 'rp_admins@rp.example.org',
        ],
    ];


    protected function sut(
        ?Helpers $helpers = null,
    ): MetadataPolicyApplicator {
        $helpers ??= $this->helpersMock;

        return new MetadataPolicyApplicator($helpers);
    }


    protected function setUp(): void
    {
        $this->helpersMock = $this->createMock(Helpers::class);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(MetadataPolicyApplicator::class, $this->sut());
    }


    public function testCanApplyBasicMetadataPolicy(): void
    {
        $expectedResolvedMetadata = [
            'redirect_uris' => [
                0 => 'https://rp.example.org/callback',
            ],
            'response_types' => [
                0 => 'code',
            ],
            'token_endpoint_auth_method' => 'self_signed_tls_client_auth',
            'sector_identifier_uri' => 'https://org.example.org/sector-ids.json',
            'policy_uri' => 'https://org.example.org/policy.html',
            'contacts' => [
                0 => 'rp_admins@rp.example.org',
                1 => 'helpdesk@federation.example.org',
                2 => 'helpdesk@org.example.org',
            ],
            'grant_types' => [
                0 => 'authorization_code',
            ],
            'subject_type' => 'pairwise',
        ];

        $resolvedMetadata = $this->sut()->for(
            $this->metadataPolicySample,
            $this->metadataSample,
        );

        $this->assertSame($expectedResolvedMetadata, $resolvedMetadata);
    }


    public function testCanHandleScopeClaims(): void
    {
        $metadataPolicy = [
            'scope' => [
                'superset_of' => [
                    0 => 'openid',
                ],
                'add' => [
                    0 => 'email',
                ],
            ],
        ];

        $metadata = [
            'scope' => 'openid profile',
        ];

        $this->assertSame(
            ['scope' => 'openid profile email'],
            $this->sut()->for($metadataPolicy, $metadata),
        );
    }


    public function testCanUnsetMetadataValue(): void
    {
        $metadataPolicy = [
            'scope' => [
                'value' => null,
            ],
        ];

        $metadata = [
            'scope' => 'openid profile',
        ];

        $this->assertSame(
            [],
            $this->sut()->for($metadataPolicy, $metadata),
        );
    }


    public function testCanAddNonExistingMetadataValue(): void
    {
        $metadataPolicy = [
            'scope' => [
                'add' => 'openid',
            ],
        ];

        $metadata = [];

        $this->assertSame(
            ['scope' => 'openid'],
            $this->sut()->for($metadataPolicy, $metadata),
        );
    }


    public function testHasEmptyParameterOnNonExistingParameterForSubsetOf(): void
    {
        $metadataPolicy = [
            'scope' => [
                'subset_of' => ['openid'],
            ],
        ];
        $metadata = [];

        $this->assertSame(
            [],
            $this->sut()->for($metadataPolicy, $metadata),
        );
    }


    public function testHasEmptyParameterOnNonExistingParameterForSupersetOf(): void
    {
        $metadataPolicy = [
            'scope' => [
                'superset_of' => ['openid'],
            ],
        ];
        $metadata = [];

        $this->assertSame(
            [],
            $this->sut()->for($metadataPolicy, $metadata),
        );
    }


    public function testHasEmptyParameterOnNonEssentialParameter(): void
    {
        $metadataPolicy = [
            'scope' => [
                'essential' => false,
            ],
        ];
        $metadata = [];

        $this->assertSame(
            [],
            $this->sut()->for($metadataPolicy, $metadata),
        );
    }


    public function testCanHandleValueRule(): void
    {
        $metadataPolicy = [
            'something' => [
                'value' => '1',
            ],
        ];
        $metadata = [
            'something' => '2',
        ];

        $this->assertSame(
            ['something' => '1'],
            $this->sut()->for($metadataPolicy, $metadata),
        );
    }


    public function testCanHandleAddRule(): void
    {
        $metadataPolicy = [
            'something' => [
                'add' => ['2'],
            ],
        ];
        $metadata = [
            'something' => ['1'],
        ];

        $this->assertSame(
            ['something' => ['1', '2']],
            $this->sut()->for($metadataPolicy, $metadata),
        );
    }


    public function testCanHandleDefaultRule(): void
    {
        $metadataPolicy = [
            'something' => [
                'default' => '1',
            ],
        ];
        $metadata = [];

        $this->assertSame(
            ['something' => '1'],
            $this->sut()->for($metadataPolicy, $metadata),
        );
    }


    public function testCanHandleOneOfRule(): void
    {
        $metadataPolicy = [
            'something' => [
                'one_of' => ['1', '2'],
            ],
        ];
        $metadata = [
            'something' => '1',
        ];

        $this->assertSame(
            ['something' => '1'],
            $this->sut()->for($metadataPolicy, $metadata),
        );
    }


    public function testCanHandleOneOfBreakRule(): void
    {
        $metadataPolicy = [
            'something' => [
                'one_of' => ['1'],
            ],
        ];
        $metadata = [
            'something' => '2',
        ];

        $this->expectException(MetadataPolicyException::class);
        $this->expectExceptionMessage('one of');

        $this->sut()->for($metadataPolicy, $metadata);
    }


    public function testCanHandleSubsetOfRule(): void
    {
        $metadataPolicy = [
            'something' => [
                'subset_of' => ['1', '2'],
            ],
        ];
        $metadata = [
            'something' => ['1'],
        ];

        $this->assertSame(
            ['something' => ['1']],
            $this->sut()->for($metadataPolicy, $metadata),
        );
    }


    public function testCanHandleSubsetOfBreakRule(): void
    {
        $metadataPolicy = [
            'something' => [
                'subset_of' => ['1'],
            ],
        ];
        $metadata = [
            'something' => ['1', '2'],
        ];

        $this->assertSame(
            ['something' => ['1']],
            $this->sut()->for($metadataPolicy, $metadata),
        );
    }


    public function testCanHandleSupersetOfRule(): void
    {
        $metadataPolicy = [
            'something' => [
                'superset_of' => ['1'],
            ],
        ];
        $metadata = [
            'something' => ['1', '2'],
        ];

        $this->assertSame(
            ['something' => ['1', '2']],
            $this->sut()->for($metadataPolicy, $metadata),
        );
    }


    public function testCanHandleSupersetOfBreakRule(): void
    {
        $metadataPolicy = [
            'something' => [
                'superset_of' => ['1', '2'],
            ],
        ];
        $metadata = [
            'something' => ['1'],
        ];

        $this->expectException(MetadataPolicyException::class);
        $this->expectExceptionMessage('superset of');

        $this->sut()->for($metadataPolicy, $metadata);
    }


    public function testCanHandleEssentialRule(): void
    {
        $metadataPolicy = [
            'something' => [
                'essential' => true,
            ],
        ];
        $metadata = [
            'something' => ['1', '2'],
        ];

        $this->assertSame(
            ['something' => ['1', '2']],
            $this->sut()->for($metadataPolicy, $metadata),
        );
    }


    public function testCanHandleEssentialBreakRule(): void
    {
        $metadataPolicy = [
            'something' => [
                'essential' => true,
            ],
        ];
        $metadata = [];


        $this->expectException(MetadataPolicyException::class);
        $this->expectExceptionMessage('essential');

        $this->sut()->for($metadataPolicy, $metadata);
    }
}
