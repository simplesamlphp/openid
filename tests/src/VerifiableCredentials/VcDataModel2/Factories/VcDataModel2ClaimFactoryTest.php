<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel2\Factories;

use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\AbstractIdentifiedTypedClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\AbstractTypedClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\TypeClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcAtContextClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialStatusClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Factories\VcDataModelClaimFactory;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims\VcCredentialStatusClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims\VcRefreshServiceClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims\VcRefreshServiceClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Factories\VcDataModel2ClaimFactory;

#[\PHPUnit\Framework\Attributes\CoversClass(VcDataModel2ClaimFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcAtContextClaimValue::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcCredentialStatusClaimBag::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcRefreshServiceClaimBag::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcRefreshServiceClaimValue::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TypeClaimValue::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Helpers::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Helpers\Arr::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Helpers\Type::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcCredentialStatusClaimValue::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractIdentifiedTypedClaimValue::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractTypedClaimValue::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(VcDataModelClaimFactory::class)]
final class VcDataModel2ClaimFactoryTest extends TestCase
{
    protected Helpers $helpers;

    protected \PHPUnit\Framework\MockObject\Stub $claimFactoryMock;


    protected function setUp(): void
    {
        $this->helpers = new Helpers();
        $this->claimFactoryMock = $this->createStub(ClaimFactory::class);
    }


    public function sut(): VcDataModel2ClaimFactory
    {
        $claimFactory = $this->claimFactoryMock;

        return new VcDataModel2ClaimFactory($this->helpers, $claimFactory);
    }


    public function testCanBuildVcAtContextClaimValue(): void
    {
        $this->assertInstanceOf(
            VcAtContextClaimValue::class,
            $this->sut()->buildVcAtContextClaimValue('https://www.w3.org/ns/credentials/v2', []),
        );
    }


    public function testCanBuildVcCredentialStatusClaimBag(): void
    {
        $data = ['id' => 'urn:example:status', 'type' => 'BitstringStatusList2023'];
        $this->assertInstanceOf(
            VcCredentialStatusClaimBag::class,
            $this->sut()->buildVcCredentialStatusClaimBag($data),
        );
    }


    public function testCanBuildVcCredentialStatusClaimBagWithMultipleStatus(): void
    {
        $data = [
            ['id' => 'urn:example:status1', 'type' => 'BitstringStatusList2023'],
            ['id' => 'urn:example:status2', 'type' => 'BitstringStatusList2023'],
        ];
        $this->assertInstanceOf(
            VcCredentialStatusClaimBag::class,
            $this->sut()->buildVcCredentialStatusClaimBag($data),
        );
    }


    public function testCanBuildVcRefreshServiceClaimValue2(): void
    {
        $data = ['type' => 'RefreshService2021', 'url' => 'https://example.com/refresh'];
        $this->assertInstanceOf(
            VcRefreshServiceClaimValue::class,
            $this->sut()->buildVcRefreshServiceClaimValue2($data),
        );
    }


    public function testBuildVcRefreshServiceClaimValue2ThrowsIfNoType(): void
    {
        $this->expectException(\SimpleSAML\OpenID\Exceptions\VcDataModelException::class);
        $this->expectExceptionMessage('No Type claim value available.');

        $this->sut()->buildVcRefreshServiceClaimValue2(['url' => 'https://example.com/refresh']);
    }


    public function testCanBuildVcRefreshServiceClaimBag2(): void
    {
        $data = ['type' => 'RefreshService2021', 'url' => 'https://example.com/refresh'];
        $this->assertInstanceOf(
            VcRefreshServiceClaimBag::class,
            $this->sut()->buildVcRefreshServiceClaimBag2($data),
        );
    }


    public function testCanBuildVcRefreshServiceClaimBag2WithMultipleServices(): void
    {
        $data = [
            ['type' => 'RefreshService2021', 'url' => 'https://example.com/refresh1'],
            ['type' => 'RefreshService2021', 'url' => 'https://example.com/refresh2'],
        ];
        $this->assertInstanceOf(
            VcRefreshServiceClaimBag::class,
            $this->sut()->buildVcRefreshServiceClaimBag2($data),
        );
    }
}
