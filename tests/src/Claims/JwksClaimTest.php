<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Claims;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Claims\JwksClaim;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;

#[CoversClass(JwksClaim::class)]
final class JwksClaimTest extends TestCase
{
    protected array $valueSample;

    protected string $nameSample;


    protected function setUp(): void
    {
        $this->valueSample = [
            'keys' => [
                [
                    'alg' => 'RS256',
                    'use' => 'sig',
                    'kty' => 'RSA',
                    // phpcs:ignore
                    'n' => 'pJgG9F_lwc2cFEC1l6q0fjJYxKPbtVGqJpDggDpDR8MgfbH0jUZP_RvhJGpl_09Bp-PfibLiwxchHZlrCx-fHQyGMaBRivUfq_p12ECEXMaFUcasCP6cyNrDfa5Uchumau4WeC21nYI1NMawiMiWFcHpLCQ7Ul8NMaCM_dkeruhm_xG0ZCqfwu30jOyCsnZdE0izJwPTfBRLpLyivu8eHpwjoIzmwqo8H-ZsbqR0vdRu20-MNS78ppTxwK3QmJhU6VO2r730F6WH9xJd_XUDuVeM4_6Z6WVDXw3kQF-jlpfcssPP303nbqVmfFZSUgS8buToErpMqevMIKREShsjMQ',
                    'e' => 'AQAB',
                    'kid' => 'F4VFObNusj3PHmrHxpqh4GNiuFHlfh-2s6xMJ95fLYA',
                ],
            ],
        ];
        $this->nameSample = ClaimsEnum::Jwks->value;
    }


    protected function sut(
        ?array $value = null,
        ?string $name = null,
    ): JwksClaim {
        $value ??= $this->valueSample;
        $name ??= $this->nameSample;

        return new JwksClaim(
            $value,
            $name,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(JwksClaim::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $this->assertSame($this->valueSample, $this->sut()->getValue());
        $this->assertSame($this->nameSample, $this->sut()->getName());
        $this->assertSame(
            [$this->nameSample => $this->valueSample],
            $this->sut()->jsonSerialize(),
        );
    }


    public function testCanCheckIfKeyIdExists(): void
    {
        $sut = $this->sut();

        $this->assertTrue($sut->hasKeyId('F4VFObNusj3PHmrHxpqh4GNiuFHlfh-2s6xMJ95fLYA'));
        $this->assertFalse($sut->hasKeyId('invalid-key-id'));
    }
}
