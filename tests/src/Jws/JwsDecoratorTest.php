<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jws;

use Jose\Component\Signature\JWS;
use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use PHPUnit\Framework\TestCase;

#[CoversClass(JwsDecorator::class)]
class JwsDecoratorTest extends TestCase
{
    protected JWS $jwsMock;

    protected function setUp(): void
    {
        $this->jwsMock = $this->createMock(JWS::class);
    }

    protected function sut(
        ?JWS $jws = null,
    ): JwsDecorator {
        $jws ??= $this->jwsMock;

        return new JwsDecorator($jws);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(JwsDecorator::class, $this->sut());
    }

    public function testCanGetJws(): void
    {
        $this->assertInstanceOf(JWS::class, $this->sut()->jws());
    }
}