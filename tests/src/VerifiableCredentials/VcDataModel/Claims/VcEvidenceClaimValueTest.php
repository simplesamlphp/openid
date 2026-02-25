<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\TypeClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcEvidenceClaimValue;

#[\PHPUnit\Framework\Attributes\CoversClass(VcEvidenceClaimValue::class)]
final class VcEvidenceClaimValueTest extends TestCase
{
    protected \PHPUnit\Framework\MockObject\Stub $typeClaimValue;


    protected function setUp(): void
    {
        $this->typeClaimValue = $this->createStub(TypeClaimValue::class);
    }


    protected function sut(
        ?TypeClaimValue $typeClaimValue = null,
    ): VcEvidenceClaimValue {
        $typeClaimValue ??= $this->typeClaimValue;

        return new VcEvidenceClaimValue($typeClaimValue);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VcEvidenceClaimValue::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertSame($this->typeClaimValue, $sut->getType());
        $this->assertSame('evidence', $sut->getName());
    }
}
