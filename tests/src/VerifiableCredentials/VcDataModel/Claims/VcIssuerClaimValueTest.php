<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcIssuerClaimValue;

#[\PHPUnit\Framework\Attributes\CoversClass(VcIssuerClaimValue::class)]
final class VcIssuerClaimValueTest extends TestCase
{
    protected string $id = 'id';

    protected array $otherClaims = [];


    protected function sut(
        ?string $id = null,
        ?array $otherClaims = null,
    ): VcIssuerClaimValue {
        $id ??= $this->id;
        $otherClaims ??= $this->otherClaims;

        return new VcIssuerClaimValue($id, $otherClaims);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VcIssuerClaimValue::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut();

        $this->assertSame($this->id, $sut->getId());
        $this->assertSame('id', $sut->getKey('id'));
        $this->assertSame('issuer', $sut->getName());
        $this->assertSame(['id' => 'id'], $sut->getValue());
        $this->assertSame(['id' => 'id'], $sut->jsonSerialize());
    }
}
