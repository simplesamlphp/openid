<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\TypeClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcRefreshServiceClaimValue;

#[\PHPUnit\Framework\Attributes\CoversClass(VcRefreshServiceClaimValue::class)]
final class VcRefreshServiceClaimValueTest extends TestCase
{
    protected string $id = 'id';

    protected \PHPUnit\Framework\MockObject\Stub $typeClaimValue;

    protected array $otherClaims = [];


    protected function setUp(): void
    {
        $this->typeClaimValue = $this->createStub(TypeClaimValue::class);
    }


    protected function sut(
        ?string $id = null,
        ?TypeClaimValue $typeClaimValue = null,
        ?array $otherClaims = null,
    ): VcRefreshServiceClaimValue {
        $id ??= $this->id;
        $typeClaimValue ??= $this->typeClaimValue;
        $otherClaims ??= $this->otherClaims;

        return new VcRefreshServiceClaimValue($id, $typeClaimValue, $otherClaims);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VcRefreshServiceClaimValue::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertSame($this->id, $sut->getId());
        $this->assertSame($this->typeClaimValue, $sut->getType());
        $this->assertSame('refreshService', $sut->getName());
    }


    public function testCanGetOtherClaimValues(): void
    {
        $otherClaims = ['foo' => 'bar'];
        $sut = $this->sut(otherClaims: $otherClaims);

        $this->assertSame('bar', $sut->getKey('foo'));
        $this->assertSame($this->id, $sut->getKey('id'));
        $this->assertNull($sut->getKey('baz'));

        $otherClaims = [123 => 'integer key content'];
        $sut = $this->sut(otherClaims: $otherClaims);
        $this->assertSame('integer key content', $sut->getKey(123));
    }


    public function testOverwritingIdAndType(): void
    {
        $otherClaims = [
            'id' => 'overwritten id',
            'type' => 'overwritten type',
            'foo' => 'bar',
        ];
        $sut = $this->sut(otherClaims: $otherClaims);

        $this->assertSame($this->id, $sut->getId());
        $this->assertSame($this->id, $sut->getKey('id'));
        /** @phpstan-ignore method.notFound */
        $this->assertSame($this->typeClaimValue->jsonSerialize(), $sut->getKey('type'));
        $this->assertSame('bar', $sut->getKey('foo'));
    }


    public function testCanGetValue(): void
    {
        $this->typeClaimValue->method('jsonSerialize')->willReturn(['RefreshService']);
        $otherClaims = ['foo' => 'bar'];
        $sut = $this->sut(otherClaims: $otherClaims);

        $expected = [
            'foo' => 'bar',
            'id' => $this->id,
            'type' => ['RefreshService'],
        ];

        $this->assertSame($expected, $sut->getValue());
        $this->assertSame($expected, $sut->jsonSerialize());
    }
}
