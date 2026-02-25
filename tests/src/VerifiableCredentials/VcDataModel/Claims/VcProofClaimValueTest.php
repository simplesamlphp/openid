<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\TypeClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcProofClaimValue;

#[\PHPUnit\Framework\Attributes\CoversClass(VcProofClaimValue::class)]
final class VcProofClaimValueTest extends TestCase
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
    ): VcProofClaimValue {
        $typeClaimValue ??= $this->typeClaimValueMock;
        $otherClaims ??= $this->otherClaims;

        return new VcProofClaimValue($typeClaimValue, $otherClaims);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VcProofClaimValue::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertSame($this->typeClaimValueMock, $sut->getType());
        $this->assertSame('proof', $sut->getName());
    }
}
