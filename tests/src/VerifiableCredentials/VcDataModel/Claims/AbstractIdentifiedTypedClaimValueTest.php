<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\AbstractIdentifiedTypedClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\TypeClaimValue;

#[CoversClass(AbstractIdentifiedTypedClaimValue::class)]
final class AbstractIdentifiedTypedClaimValueTest extends TestCase
{
    protected string $id = "id";

    protected MockObject $typeClaimValueMock;

    protected array $otherClaims = [];

    protected string $name = 'name';


    protected function setUp(): void
    {
        $this->typeClaimValueMock = $this->createMock(TypeClaimValue::class);
    }


    protected function sut(
        ?string $id = null,
        ?TypeClaimValue $typeClaimValue = null,
        ?array $otherClaims = null,
        ?string $name = null,
    ): AbstractIdentifiedTypedClaimValue {
        $id ??= $this->id;
        $typeClaimValue ??= $this->typeClaimValueMock;
        $otherClaims ??= $this->otherClaims;
        $name ??= $this->name;

        return new class (
            $id,
            $typeClaimValue,
            $otherClaims,
            $name,
        ) extends AbstractIdentifiedTypedClaimValue {
            public function __construct(
                string $id,
                TypeClaimValue $typeClaimValue,
                array $otherClaims,
                protected readonly string $name,
            ) {
                parent::__construct($id, $typeClaimValue, $otherClaims);
            }


            public function getName(): string
            {
                return $this->name;
            }
        };
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(AbstractIdentifiedTypedClaimValue::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertSame($this->id, $sut->getId());
        $this->assertSame($this->typeClaimValueMock, $sut->getType());
        $this->assertSame($this->id, $sut->getKey('id'));
        $this->assertSame($this->name, $sut->getName());
        $this->assertArrayHasKey('id', $sut->getValue());
        $this->assertArrayHasKey('id', $sut->jsonSerialize());
    }
}
