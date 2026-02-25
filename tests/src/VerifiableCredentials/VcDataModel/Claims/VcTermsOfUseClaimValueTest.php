<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\TypeClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcTermsOfUseClaimValue;

#[\PHPUnit\Framework\Attributes\CoversClass(VcTermsOfUseClaimValue::class)]
final class VcTermsOfUseClaimValueTest extends TestCase
{
    protected \PHPUnit\Framework\MockObject\Stub $typeClaimValueMock;

    protected array $otherClaims = [];


    protected function setUp(): void
    {
        $this->typeClaimValueMock = $this->createStub(TypeClaimValue::class);
    }


    protected function sut(
        ?TypeClaimValue $typeClaimValue = null,
        ?array $otherClaims = null,
    ): VcTermsOfUseClaimValue {
        $typeClaimValue ??= $this->typeClaimValueMock;
        $otherClaims ??= $this->otherClaims;

        return new VcTermsOfUseClaimValue($typeClaimValue, $otherClaims);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VcTermsOfUseClaimValue::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertSame($this->typeClaimValueMock, $sut->getType());
        $this->assertSame('termsOfUse', $sut->getName());
    }
}
