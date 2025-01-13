<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleSAML\OpenID\Codebooks\EntityTypesEnum;
use SimpleSAML\OpenID\Codebooks\MetadataPolicyOperatorsEnum;
use SimpleSAML\OpenID\Exceptions\MetadataPolicyException;
use SimpleSAML\OpenID\Federation\MetadataPolicyResolver;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Helpers;

#[CoversClass(MetadataPolicyResolver::class)]
#[UsesClass(MetadataPolicyOperatorsEnum::class)]
class MetadataPolicyResolverTest extends TestCase
{
    protected MockObject $helpersMock;

    protected array $trustAnchorMetadataPolicySample = [
        'openid_relying_party' => [
            'grant_types' => [
                'default' => ['authorization_code',],
                'subset_of' => ['authorization_code', 'refresh_token',],
                'superset_of' => ['authorization_code'],
            ],
            'token_endpoint_auth_method' => [
                'one_of' => ['private_key_jwt', 'self_signed_tls_client_auth',],
                'essential' => true,
            ],
            'token_endpoint_auth_signing_alg' => [
                'one_of' => ['PS256', 'ES256',],
            ],
            'subject_type' => [
                'value' => 'pairwise',
            ],
            'contacts' => [
                'add' => ['helpdesk@federation.example.org'],
            ],
        ],
    ];

    protected array $intermediateMetadataPolicySample = [
        'openid_relying_party' => [
            'grant_types' => [
                'subset_of' => ['authorization_code',],
            ],
            'token_endpoint_auth_method' => [
                'one_of' => ['self_signed_tls_client_auth'],
            ],
            'contacts' => [
                'add' => ['helpdesk@org.example.org'],
            ],
            'subject_type' => [
                'value' => 'pairwise',
            ],
        ],
    ];

    protected function setUp(): void
    {
        $this->helpersMock = $this->createMock(Helpers::class);
    }

    protected function sut(
        ?Helpers $helpers = null,
    ): MetadataPolicyResolver {
        $helpers ??= $this->helpersMock;

        return new MetadataPolicyResolver($helpers);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(MetadataPolicyResolver::class, $this->sut());
    }

    public function testForHappyFlow(): void
    {
        $metadataPolicy = $this->sut()->for(
            EntityTypesEnum::OpenIdRelyingParty,
            [
                $this->trustAnchorMetadataPolicySample,
                $this->intermediateMetadataPolicySample
            ],
        );

        $this->assertIsArray($metadataPolicy);
    }

    public function testReturnsEmptyArrayIfEntityTypeNotPresent(): void
    {
        $this->assertEmpty(
            $this->sut()->for(
                EntityTypesEnum::FederationEntity,
                [
                    $this->trustAnchorMetadataPolicySample,
                    $this->intermediateMetadataPolicySample
                ],
            )
        );
    }

    public function testThrowsInCaseOfDifferentValueOperatorValue(): void
    {
        $this->expectException(MetadataPolicyException::class);
        $this->expectExceptionMessage('Different');

        $intermediateMetadataPolicy = $this->intermediateMetadataPolicySample;
        $intermediateMetadataPolicy['openid_relying_party']['subject_type']['value'] = 'different';

        $this->sut()->for(
            EntityTypesEnum::OpenIdRelyingParty,
            [
                $this->trustAnchorMetadataPolicySample,
                $intermediateMetadataPolicy,
            ],
        );
    }

    public function testThrowsForInvalidEssentialOperatorValueChange(): void
    {
        $this->expectException(MetadataPolicyException::class);
        $this->expectExceptionMessage('Invalid');

        $intermediateMetadataPolicy = $this->intermediateMetadataPolicySample;
        $intermediateMetadataPolicy['openid_relying_party']['token_endpoint_auth_method']['essential'] = false;

        $this->sut()->for(
            EntityTypesEnum::OpenIdRelyingParty,
            [
                $this->trustAnchorMetadataPolicySample,
                $intermediateMetadataPolicy,
            ],
        );
    }

    public function testSetsEssentialOperatorValueInCaseOfCurrentFalseValue(): void
    {
        $trustAnchorMetadataPolicy = $this->trustAnchorMetadataPolicySample;
        $trustAnchorMetadataPolicy['openid_relying_party']['token_endpoint_auth_method']['essential'] = false;

        $intermediateMetadataPolicy = $this->intermediateMetadataPolicySample;
        $intermediateMetadataPolicy['openid_relying_party']['token_endpoint_auth_method']['essential'] = true;

        $metadataPolicy = $this->sut()->for(
            EntityTypesEnum::OpenIdRelyingParty,
            [
                $trustAnchorMetadataPolicy,
                $intermediateMetadataPolicy,
            ],
        );

        $this->assertTrue($metadataPolicy['token_endpoint_auth_method']['essential']);
    }
}
