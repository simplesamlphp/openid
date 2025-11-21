<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\AbstractTypedClaimValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\TypeClaimValue;

#[\PHPUnit\Framework\Attributes\CoversClass(AbstractTypedClaimValue::class)]
final class AbstractTypedClaimValueTest extends TestCase
{
    protected MockObject $typeClaimValueMock;

    protected array $otherClaims = [];

    protected string $name = 'name';


    protected function setUp(): void
    {
        $this->typeClaimValueMock = $this->createMock(TypeClaimValue::class);
    }


    protected function sut(
        ?TypeClaimValue $typeClaimValue = null,
        ?array $otherClaims = null,
        ?string $name = null,
    ): AbstractTypedClaimValue {
        $typeClaimValue ??= $this->typeClaimValueMock;
        $otherClaims ??= $this->otherClaims;
        $name ??= $this->name;

        return new class ($typeClaimValue, $otherClaims, $name) extends AbstractTypedClaimValue {
            public function __construct(
                TypeClaimValue $typeClaimValue,
                array $otherClaims,
                protected readonly string $name,
            ) {
                parent::__construct($typeClaimValue, $otherClaims);
            }


            public function getName(): string
            {
                return $this->name;
            }
        };
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(AbstractTypedClaimValue::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $this->typeClaimValueMock->expects($this->once())
            ->method('jsonSerialize')
            ->willReturn('type');

        $sut = $this->sut();
        $this->assertSame($this->typeClaimValueMock, $sut->getType());
        $this->assertSame('type', $sut->getKey('type'));
        $this->assertSame($this->name, $sut->getName());
        $this->assertArrayHasKey('type', $sut->getValue());
        $this->assertArrayHasKey('type', $sut->jsonSerialize());
    }
}
