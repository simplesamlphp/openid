<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel2\Claims;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims\VcRefreshServiceClaimBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Claims\VcRefreshServiceClaimValue;

#[\PHPUnit\Framework\Attributes\CoversClass(VcRefreshServiceClaimBag::class)]
final class VcRefreshServiceClaimBagTest extends TestCase
{
    protected MockObject $vcRefreshServiceClaimValueMock;


    protected function setUp(): void
    {
        $this->vcRefreshServiceClaimValueMock = $this->createMock(VcRefreshServiceClaimValue::class);
    }


    protected function sut(
        ?VcRefreshServiceClaimValue $vcRefreshServiceClaimValue = null,
        VcRefreshServiceClaimValue ...$vcRefreshServiceClaimValueValues,
    ): VcRefreshServiceClaimBag {
        $vcRefreshServiceClaimValue ??= $this->vcRefreshServiceClaimValueMock;

        return new VcRefreshServiceClaimBag(
            $vcRefreshServiceClaimValue,
            ...$vcRefreshServiceClaimValueValues,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VcRefreshServiceClaimBag::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertSame('refreshService', $sut->getName());
        $this->assertCount(1, $sut->getValue());
        $this->assertSame($this->vcRefreshServiceClaimValueMock, $sut->getValue()[0]);
    }


    public function testJsonSerialize(): void
    {
        $this->vcRefreshServiceClaimValueMock->method('jsonSerialize')->willReturn(['foo' => 'bar']);
        $sut = $this->sut();
        $this->assertSame([['foo' => 'bar']], $sut->jsonSerialize());
    }


    public function testCanCreateWithMultipleValues(): void
    {
        $mock1 = $this->createMock(VcRefreshServiceClaimValue::class);
        $mock1->method('jsonSerialize')->willReturn(['m1' => 'v1']);
        $mock2 = $this->createMock(VcRefreshServiceClaimValue::class);
        $mock2->method('jsonSerialize')->willReturn(['m2' => 'v2']);

        $sut = $this->sut($mock1, $mock2);

        $this->assertCount(2, $sut->getValue());
        $this->assertSame([['m1' => 'v1'], ['m2' => 'v2']], $sut->jsonSerialize());
    }
}
