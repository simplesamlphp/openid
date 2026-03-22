<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel2\Claims;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcCredentialStatusClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims\VcCredentialStatusClaimBag;

#[\PHPUnit\Framework\Attributes\CoversClass(VcCredentialStatusClaimBag::class)]
final class VcCredentialStatusClaimBagTest extends TestCase
{
    protected MockObject $vcCredentialStatusClaimValueMock;


    protected function setUp(): void
    {
        $this->vcCredentialStatusClaimValueMock = $this->createMock(VcCredentialStatusClaimValue::class);
    }


    protected function sut(
        ?VcCredentialStatusClaimValue $vcCredentialStatusClaimValue = null,
        VcCredentialStatusClaimValue ...$vcCredentialStatusClaimValueValues,
    ): VcCredentialStatusClaimBag {
        $vcCredentialStatusClaimValue ??= $this->vcCredentialStatusClaimValueMock;

        return new VcCredentialStatusClaimBag(
            $vcCredentialStatusClaimValue,
            ...$vcCredentialStatusClaimValueValues,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VcCredentialStatusClaimBag::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertSame('credentialStatus', $sut->getName());
        $this->assertCount(1, $sut->getValue());
        $this->assertSame($this->vcCredentialStatusClaimValueMock, $sut->getValue()[0]);
    }


    public function testJsonSerialize(): void
    {
        $this->vcCredentialStatusClaimValueMock->method('jsonSerialize')->willReturn(['foo' => 'bar']);
        $sut = $this->sut();
        $this->assertSame([['foo' => 'bar']], $sut->jsonSerialize());
    }


    public function testCanCreateWithMultipleValues(): void
    {
        $mock1 = $this->createMock(VcCredentialStatusClaimValue::class);
        $mock1->method('jsonSerialize')->willReturn(['m1' => 'v1']);
        $mock2 = $this->createMock(VcCredentialStatusClaimValue::class);
        $mock2->method('jsonSerialize')->willReturn(['m2' => 'v2']);

        $sut = $this->sut($mock1, $mock2);

        $this->assertCount(2, $sut->getValue());
        $this->assertSame([['m1' => 'v1'], ['m2' => 'v2']], $sut->jsonSerialize());
    }
}
