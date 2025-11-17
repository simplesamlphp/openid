<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\SdJwt;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Exceptions\SdJwtException;
use SimpleSAML\OpenID\SdJwt\Disclosure;
use SimpleSAML\OpenID\SdJwt\DisclosureBag;

#[\PHPUnit\Framework\Attributes\CoversClass(DisclosureBag::class)]
final class DisclosureBagTest extends TestCase
{
    protected MockObject $disclosureMock;


    protected function setUp(): void
    {
        $this->disclosureMock = $this->createMock(Disclosure::class);
        $this->disclosureMock->method('getSalt')->willReturn('salt');
    }


    protected function sut(
        Disclosure ...$disclosures,
    ): DisclosureBag {
        return new DisclosureBag(...$disclosures);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(DisclosureBag::class, $this->sut());
    }


    public function testCanAddAndGet(): void
    {
        $sut = $this->sut($this->disclosureMock);

        $this->assertCount(1, $sut->all());

        $disclosureMock2 = $this->createMock(Disclosure::class);
        $disclosureMock2->method('getSalt')->willReturn('salt2');
        $sut->add($disclosureMock2);

        $this->assertCount(2, $sut->all());
    }


    public function testCanGetSalts(): void
    {
        $sut = $this->sut($this->disclosureMock);
        $this->assertSame(
            ['salt'],
            $sut->salts(),
        );
    }


    public function testAddThrowsForDuplicateSalt(): void
    {
        $sut = $this->sut($this->disclosureMock);
        $this->expectException(SdJwtException::class);
        $this->expectExceptionMessage('salt');
        $sut->add($this->disclosureMock);
    }
}
