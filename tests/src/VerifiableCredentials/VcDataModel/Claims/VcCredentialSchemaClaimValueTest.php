<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\TypeClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSchemaClaimValue;

#[\PHPUnit\Framework\Attributes\CoversClass(VcCredentialSchemaClaimValue::class)]
final class VcCredentialSchemaClaimValueTest extends TestCase
{
    protected string $id = 'id';

    protected MockObject $typeClaimValueMock;


    protected function setUp(): void
    {
        $this->typeClaimValueMock = $this->createMock(TypeClaimValue::class);
    }


    protected function sut(
        ?string $id = null,
        ?TypeClaimValue $typeClaimValue = null,
    ): VcCredentialSchemaClaimValue {
        $id ??= $this->id;
        $typeClaimValue ??= $this->typeClaimValueMock;

        return new VcCredentialSchemaClaimValue(
            $id,
            $typeClaimValue,
        );
    }


    public function testCanCrateInstance(): void
    {
        $this->assertInstanceOf(VcCredentialSchemaClaimValue::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertSame($this->id, $sut->getId());
        $this->assertSame($this->typeClaimValueMock, $sut->getType());
        $this->assertSame('credentialSchema', $sut->getName());
    }
}
