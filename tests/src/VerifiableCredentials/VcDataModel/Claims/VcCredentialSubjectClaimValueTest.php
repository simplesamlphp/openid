<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialSubjectClaimValue;

#[\PHPUnit\Framework\Attributes\CoversClass(VcCredentialSubjectClaimValue::class)]
final class VcCredentialSubjectClaimValueTest extends TestCase
{
    protected array $data = ['id' => 'subject'];


    protected function sut(
        ?array $data = null,
    ): VcCredentialSubjectClaimValue {
        $data ??= $this->data;

        return new VcCredentialSubjectClaimValue($data);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VcCredentialSubjectClaimValue::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertSame($this->data, $sut->jsonSerialize());
        $this->assertSame($this->data, $sut->getValue());
        $this->assertSame('subject', $sut->get('id'));
        $this->assertSame('credentialSubject', $sut->getName());
    }
}
