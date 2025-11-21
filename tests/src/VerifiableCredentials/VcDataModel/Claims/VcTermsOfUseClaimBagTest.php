<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcTermsOfUseClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcTermsOfUseClaimValue;

#[\PHPUnit\Framework\Attributes\CoversClass(VcTermsOfUseClaimBag::class)]
final class VcTermsOfUseClaimBagTest extends TestCase
{
    protected MockObject $vcTermsOfUseClaimValueMock;


    protected function setUp(): void
    {
        $this->vcTermsOfUseClaimValueMock = $this->createMock(VcTermsOfUseClaimValue::class);
    }


    protected function sut(
        ?VcTermsOfUseClaimValue $vcTermsOfUseClaimValue = null,
        VcTermsOfUseClaimValue ...$vcTermsOfUseClaimValues,
    ): VcTermsOfUseClaimBag {
        $vcTermsOfUseClaimValue ??= $this->vcTermsOfUseClaimValueMock;

        return new VcTermsOfUseClaimBag($vcTermsOfUseClaimValue, ...$vcTermsOfUseClaimValues);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VcTermsOfUseClaimBag::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $this->vcTermsOfUseClaimValueMock->expects($this->once())
            ->method('jsonSerialize')
            ->willReturn(['termsOfUse']);

        $sut = $this->sut();
        $this->assertSame('termsOfUse', $sut->getName());
        $this->assertSame([$this->vcTermsOfUseClaimValueMock], $sut->getValue());
        $this->assertSame([['termsOfUse']], $sut->jsonSerialize());
    }
}
