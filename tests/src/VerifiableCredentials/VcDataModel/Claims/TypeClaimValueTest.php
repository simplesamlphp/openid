<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\TypeClaimValue;

#[\PHPUnit\Framework\Attributes\CoversClass(TypeClaimValue::class)]
final class TypeClaimValueTest extends TestCase
{
    protected array $types = ['type', 'type2'];


    protected function sut(
        ?array $types = null,
    ): TypeClaimValue {
        $types ??= $this->types;

        return new TypeClaimValue($types);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TypeClaimValue::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertSame('type', $sut->getName());
        $this->assertSame($this->types, $sut->getValue());
        $this->assertSame($this->types, $sut->jsonSerialize());
        $this->assertTrue($sut->has('type'));
    }


    public function testJsonSerializeCanReturnStringOrArray(): void
    {
        $sut = $this->sut();
        $this->assertSame($this->types, $sut->jsonSerialize());

        $sut = $this->sut(['type']);
        $this->assertSame('type', $sut->jsonSerialize());
    }
}
