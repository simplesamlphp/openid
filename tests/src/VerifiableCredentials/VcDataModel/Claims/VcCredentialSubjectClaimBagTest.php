<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSubjectClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSubjectClaimValue;

#[\PHPUnit\Framework\Attributes\CoversClass(VcCredentialSubjectClaimBag::class)]
final class VcCredentialSubjectClaimBagTest extends TestCase
{
    protected MockObject $vcCredentialSubjectClaimValueMock;


    protected function setUp(): void
    {
        $this->vcCredentialSubjectClaimValueMock = $this->createMock(VcCredentialSubjectClaimValue::class);
    }


    protected function sut(
        ?VcCredentialSubjectClaimValue $vcCredentialSubjectClaimValue = null,
        VcCredentialSubjectClaimValue ...$vcCredentialSubjectClaimValues,
    ): VcCredentialSubjectClaimBag {
        $vcCredentialSubjectClaimValue ??= $this->vcCredentialSubjectClaimValueMock;

        return new VcCredentialSubjectClaimBag($vcCredentialSubjectClaimValue, ...$vcCredentialSubjectClaimValues);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VcCredentialSubjectClaimBag::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $this->vcCredentialSubjectClaimValueMock->expects($this->once())
            ->method('jsonSerialize')
            ->willReturn(['credentialSubject']);

        $sut = $this->sut();


        $this->assertSame('credentialSubject', $sut->getName());
        $this->assertSame([$this->vcCredentialSubjectClaimValueMock], $sut->getValue());
        $this->assertSame([['credentialSubject']], $sut->jsonSerialize());
    }
}
