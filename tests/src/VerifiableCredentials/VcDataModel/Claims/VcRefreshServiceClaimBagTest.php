<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcRefreshServiceClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcRefreshServiceClaimValue;

#[\PHPUnit\Framework\Attributes\CoversClass(VcRefreshServiceClaimBag::class)]
final class VcRefreshServiceClaimBagTest extends TestCase
{
    protected MockObject $vcRefreshServiceClaimValueMock;


    protected function setUp(): void
    {
        $this->vcRefreshServiceClaimValueMock = $this->createMock(VcRefreshServiceClaimValue::class);
    }


    protected function sut(
        ?VcRefreshServiceClaimValue $vcRefreshServiceClaimValue = null,
        VcRefreshServiceClaimValue ...$vcRefreshServiceClaimValues,
    ): \SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcRefreshServiceClaimBag {
        $vcRefreshServiceClaimValue ??= $this->vcRefreshServiceClaimValueMock;

        return new VcRefreshServiceClaimBag($vcRefreshServiceClaimValue, ...$vcRefreshServiceClaimValues);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VcRefreshServiceClaimBag::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $this->vcRefreshServiceClaimValueMock->expects($this->once())
            ->method('jsonSerialize')
            ->willReturn(['refreshService']);

        $sut = $this->sut();
        $this->assertSame([['refreshService']], $sut->jsonSerialize());
        $this->assertSame([$this->vcRefreshServiceClaimValueMock], $sut->getValue());
        $this->assertSame('refreshService', $sut->getName());
        ;
    }
}
