<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jws;

use Jose\Component\Signature\JWSVerifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;

#[CoversClass(JwsVerifierDecorator::class)]
final class JwsVerifierDecoratorTest extends TestCase
{
    protected MockObject $jwsVerifierMock;


    protected function setUp(): void
    {
        $this->jwsVerifierMock = $this->createMock(JWSVerifier::class);
    }


    protected function sut(
        ?JWSVerifier $jwsVerifier = null,
    ): JwsVerifierDecorator {
        $jwsVerifier ??= $this->jwsVerifierMock;

        return new JwsVerifierDecorator(
            $jwsVerifier,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(JwsVerifierDecorator::class, $this->sut());
    }


    public function testCanGetJwsVerifier(): void
    {
        $this->assertInstanceOf(JwsVerifier::class, $this->sut()->jwsVerifier());
    }


    public function testCanVerifyWithKeySet(): void
    {
        $this->jwsVerifierMock->expects($this->once())->method('verifyWithKeySet');

        $this->sut()->verifyWithKeySet(
            $this->createStub(\SimpleSAML\OpenID\Jws\JwsDecorator::class),
            $this->createStub(\SimpleSAML\OpenID\Jwks\JwksDecorator::class),
            0,
        );
    }
}
