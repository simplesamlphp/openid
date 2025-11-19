<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\TypeClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcRefreshServiceClaimValue;

#[\PHPUnit\Framework\Attributes\CoversClass(VcRefreshServiceClaimValue::class)]
final class VcRefreshServiceClaimValueTest extends TestCase
{
    protected string $id = 'id';

    protected MockObject $typeClaimValue;

    protected array $otherClaims = [];


    protected function setUp(): void
    {
        $this->typeClaimValue = $this->createMock(TypeClaimValue::class);
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
}
