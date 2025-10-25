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


    protected function setUp(): void
    {
        $this->jwkMock = $this->createMock(JWK::class);
    }


    protected function sut(
        ?JWK $jwk = null,
    ): JwkDecorator {
        $jwk ??= $this->jwkMock;

        return new JwkDecorator($jwk);
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
}
