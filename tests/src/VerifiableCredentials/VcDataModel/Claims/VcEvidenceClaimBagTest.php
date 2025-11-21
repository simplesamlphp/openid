<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcEvidenceClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcEvidenceClaimValue;

#[\PHPUnit\Framework\Attributes\CoversClass(VcEvidenceClaimBag::class)]
final class VcEvidenceClaimBagTest extends TestCase
{
    protected MockObject $vcEvidenceClaimValueMock;


    protected function setUp(): void
    {
        $this->vcEvidenceClaimValueMock = $this->createMock(VcEvidenceClaimValue::class);
    }


    protected function sut(
        ?VcEvidenceClaimValue $vcEvidenceClaimValue = null,
        VcEvidenceClaimValue ...$vcEvidenceClaimValues,
    ): VcEvidenceClaimBag {
        $vcEvidenceClaimValue ??= $this->vcEvidenceClaimValueMock;

        return new VcEvidenceClaimBag($vcEvidenceClaimValue, ...$vcEvidenceClaimValues);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VcEvidenceClaimBag::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $this->vcEvidenceClaimValueMock->expects($this->once())
            ->method('jsonSerialize')
            ->willReturn(['evidence']);

        $sut = $this->sut();
        $this->assertSame('evidence', $sut->getName());
        $this->assertSame([$this->vcEvidenceClaimValueMock], $sut->getValue());
        $this->assertSame([['evidence']], $sut->jsonSerialize());
    }
}
