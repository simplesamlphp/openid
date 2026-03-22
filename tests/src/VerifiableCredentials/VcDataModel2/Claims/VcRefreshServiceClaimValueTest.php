<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel2\Claims;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\TypeClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims\VcRefreshServiceClaimValue;

#[CoversClass(VcRefreshServiceClaimValue::class)]
final class VcRefreshServiceClaimValueTest extends TestCase
{
    private Stub $typeClaimValue;

    private array $otherClaims = [];


    protected function setUp(): void
    {
        $this->typeClaimValue = $this->createStub(TypeClaimValue::class);
    }


    protected function sut(
        ?TypeClaimValue $typeClaimValue = null,
        ?array $otherClaims = null,
    ): VcRefreshServiceClaimValue {
        $typeClaimValue ??= $this->typeClaimValue;
        $otherClaims ??= $this->otherClaims;

        return new VcRefreshServiceClaimValue($typeClaimValue, $otherClaims);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VcRefreshServiceClaimValue::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertSame($this->typeClaimValue, $sut->getType());
        $this->assertSame('refreshService', $sut->getName());
    }


    public function testCanGetOtherClaimValues(): void
    {
        $otherClaims = ['foo' => 'bar'];
        $sut = $this->sut(otherClaims: $otherClaims);

        $this->assertSame('bar', $sut->getKey('foo'));
        $this->assertNull($sut->getKey('baz'));

        $otherClaims = [123 => 'integer key content'];
        $sut = $this->sut(otherClaims: $otherClaims);
        $this->assertSame('integer key content', $sut->getKey(123));
    }


    public function testOverwritingType(): void
    {
        $otherClaims = [
            'type' => 'overwritten type',
            'foo' => 'bar',
        ];
        $this->typeClaimValue->method('jsonSerialize')->willReturn(['RefreshService']);
        $sut = $this->sut(otherClaims: $otherClaims);

        $this->assertSame(['RefreshService'], $sut->getKey('type'));
        $this->assertSame('bar', $sut->getKey('foo'));
    }


    public function testCanGetValue(): void
    {
        $this->typeClaimValue->method('jsonSerialize')->willReturn(['RefreshService']);
        $otherClaims = ['foo' => 'bar'];
        $sut = $this->sut(otherClaims: $otherClaims);

        $expected = [
            'foo' => 'bar',
            'type' => ['RefreshService'],
        ];

        $this->assertSame($expected, $sut->getValue());
        $this->assertSame($expected, $sut->jsonSerialize());
    }
}
