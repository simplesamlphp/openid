<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jwk;

use Jose\Component\Core\JWK;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Jwk\JwkDecorator;

#[CoversClass(JwkDecorator::class)]
final class JwkDecoratorTest extends TestCase
{
    protected MockObject $jwkMock;

    protected array $additionalData = [];


    protected function setUp(): void
    {
        $this->jwkMock = $this->createMock(JWK::class);
    }


    protected function sut(
        ?JWK $jwk = null,
        ?array $additionalData = null,
    ): JwkDecorator {
        $jwk ??= $this->jwkMock;
        $additionalData ??= $this->additionalData;

        return new JwkDecorator($jwk, $additionalData);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(
            JwkDecorator::class,
            $this->sut(),
        );
    }


    public function testCanGetJwk(): void
    {
        $this->assertSame(
            $this->jwkMock,
            $this->sut()->jwk(),
        );
    }


    public function testCanGetAndSetAdditionalData(): void
    {
        $this->additionalData = ['foo' => 'bar'];
        $sut = $this->sut();
        $this->assertSame($this->additionalData, $sut->getAdditionalData());

        $sut->addAdditionalData('baz', 'qux');
        $this->assertSame(['foo' => 'bar', 'baz' => 'qux'], $sut->getAdditionalData());
    }


    public function testCanJsonSerialize(): void
    {
        $this->jwkMock->expects($this->once())->method('jsonSerialize')
            ->willReturn(['a' => 'b']);
        $this->additionalData = ['foo' => 'bar'];

        $this->assertSame(['a' => 'b', 'foo' => 'bar'], $this->sut()->jsonSerialize());
    }
}
