<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\TypeClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialStatusClaimValue;

#[\PHPUnit\Framework\Attributes\CoversClass(VcCredentialStatusClaimValue::class)]
final class VcCredentialStatusClaimValueTest extends TestCase
{
    protected string $id = 'id';

    protected \PHPUnit\Framework\MockObject\Stub $typeClaimValueMock;


    protected function setUp(): void
    {
        $this->typeClaimValueMock = $this->createStub(TypeClaimValue::class);
    }


    protected function sut(
        ?string $id = null,
        ?TypeClaimValue $typeClaimValue = null,
    ): VcCredentialStatusClaimValue {
        $id ??= $this->id;
        $typeClaimValue ??= $this->typeClaimValueMock;

        return new VcCredentialStatusClaimValue($id, $typeClaimValue);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VcCredentialStatusClaimValue::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertSame($this->id, $sut->getId());
        $this->assertSame($this->typeClaimValueMock, $sut->getType());
        $this->assertSame('credentialStatus', $sut->getName());
    }
}
