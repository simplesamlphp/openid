<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSchemaClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSchemaClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialStatusClaimValue;

#[CoversClass(VcCredentialSchemaClaimBag::class)]
final class VcCredentialSchemaClaimBagTest extends TestCase
{
    protected MockObject $vcCredentialSchemaClaimValueMock;


    protected function setUp(): void
    {
        $this->vcCredentialSchemaClaimValueMock = $this->createMock(VcCredentialSchemaClaimValue::class);
    }


    protected function sut(
        ?VcCredentialSchemaClaimValue $vcCredentialStatusClaimValue = null,
        VcCredentialStatusClaimValue ...$vcCredentialStatusClaimValues,
    ): VcCredentialSchemaClaimBag {
        $vcCredentialStatusClaimValue ??= $this->vcCredentialSchemaClaimValueMock;

        return new VcCredentialSchemaClaimBag($vcCredentialStatusClaimValue, ...$vcCredentialStatusClaimValues);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VcCredentialSchemaClaimBag::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $this->vcCredentialSchemaClaimValueMock->expects($this->once())
            ->method('jsonSerialize')
            ->willReturn(['schema']);

        $sut = $this->sut();
        $this->assertSame('credentialSchema', $sut->getName());
        $this->assertSame([$this->vcCredentialSchemaClaimValueMock], $sut->getValue());
        $this->assertSame([['schema']], $sut->jsonSerialize());
    }
}
