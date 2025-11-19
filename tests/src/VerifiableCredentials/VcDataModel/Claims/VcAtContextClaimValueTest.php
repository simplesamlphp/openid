<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\AtContextsEnum;
use SimpleSAML\OpenID\Exceptions\VcDataModelException;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\VcAtContextClaimValue;

#[\PHPUnit\Framework\Attributes\CoversClass(VcAtContextClaimValue::class)]
final class VcAtContextClaimValueTest extends TestCase
{
    protected string $baseContext = AtContextsEnum::W3Org2018CredentialsV1->value;

    protected array $otherContexts = [];


    protected function sut(
        ?string $baseContext = null,
        ?array $otherContexts = null,
    ): VcAtContextClaimValue {
        $baseContext ??= $this->baseContext;
        $otherContexts ??= $this->otherContexts;

        return new VcAtContextClaimValue($baseContext, $otherContexts);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VcAtContextClaimValue::class, $this->sut());
    }


    public function testThrowsOnInvalidBaseContext(): void
    {
        $this->expectException(VcDataModelException::class);
        $this->expectExceptionMessage('context');

        $this->sut('invalid');
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertContains($this->baseContext, $sut->jsonSerialize());
        $this->assertSame($this->baseContext, $sut->getBaseContext());
        $this->assertSame($this->otherContexts, $sut->getOtherContexts());
        $this->assertSame('@context', $sut->getName());
        $this->assertContains($this->baseContext, $sut->getValue());
    }
}
