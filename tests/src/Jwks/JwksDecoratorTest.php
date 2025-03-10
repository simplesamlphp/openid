<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jwks;

use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Jwks\JwksDecorator;

#[CoversClass(JwksDecorator::class)]
final class JwksDecoratorTest extends TestCase
{
    protected MockObject $jwkSetMock;


    protected array $jwkArraySample = [
        'alg' => 'RS256',
        'use' => 'sig',
        'kty' => 'RSA',
        // phpcs:ignore
        'n' => 'pJgG9F_lwc2cFEC1l6q0fjJYxKPbtVGqJpDggDpDR8MgfbH0jUZP_RvhJGpl_09Bp-PfibLiwxchHZlrCx-fHQyGMaBRivUfq_p12ECEXMaFUcasCP6cyNrDfa5Uchumau4WeC21nYI1NMawiMiWFcHpLCQ7Ul8NMaCM_dkeruhm_xG0ZCqfwu30jOyCsnZdE0izJwPTfBRLpLyivu8eHpwjoIzmwqo8H-ZsbqR0vdRu20-MNS78ppTxwK3QmJhU6VO2r730F6WH9xJd_XUDuVeM4_6Z6WVDXw3kQF-jlpfcssPP303nbqVmfFZSUgS8buToErpMqevMIKREShsjMQ',
        'e' => 'AQAB',
        'kid' => 'F4VFObNusj3PHmrHxpqh4GNiuFHlfh-2s6xMJ95fLYA',
    ];

    protected function setUp(): void
    {
        $jwkMock = $this->createMock(JWK::class);
        $jwkMock->method('jsonSerialize')->willReturn($this->jwkArraySample);
        $this->jwkSetMock = $this->createMock(JWKSet::class);
        $this->jwkSetMock->method('all')->willReturn([$jwkMock]);
    }

    protected function sut(
        ?JWKSet $jwkSet = null,
    ): JwksDecorator {
        $jwkSet ??= $this->jwkSetMock;

        return new JwksDecorator(
            $jwkSet,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(JwksDecorator::class, $this->sut());
    }

    public function testCanGetJwkSet(): void
    {
        $this->assertSame($this->jwkSetMock, $this->sut()->jwks());
    }

    public function testCanJsonSerialize(): void
    {
        $serialized = $this->sut()->jsonSerialize();
        $this->assertSame($serialized['keys'][0], $this->jwkArraySample);
    }
}
