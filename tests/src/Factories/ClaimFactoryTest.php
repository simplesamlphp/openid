<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Claims\GenericClaim;
use SimpleSAML\OpenID\Claims\JwksClaim;
use SimpleSAML\OpenID\Exceptions\JwksException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Federation\Factories\FederationClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Factories\VcDataModelClaimFactory;

#[CoversClass(ClaimFactory::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Type::class)]
#[UsesClass(FederationClaimFactory::class)]
#[UsesClass(GenericClaim::class)]
#[UsesClass(JwksClaim::class)]
#[UsesClass(VcDataModelClaimFactory::class)]
final class ClaimFactoryTest extends TestCase
{
    protected Helpers $helpers;

    protected array $jwksValueSample = [
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


    protected function setUp(): void
    {
        $this->helpers = new Helpers();
    }


    protected function sut(
        ?Helpers $helpers = null,
    ): ClaimFactory {
        $helpers ??= $this->helpers;

        return new ClaimFactory(
            $helpers,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(ClaimFactory::class, $this->sut());
    }


    public function testCanGetForFederation(): void
    {
        $this->assertInstanceOf(FederationClaimFactory::class, $this->sut()->forFederation());
    }


    public function testCanGetForVcDataModel(): void
    {
        $this->assertInstanceOf(VcDataModelClaimFactory::class, $this->sut()->forVcDataModel());
    }


    public function testCanBuildGeneric(): void
    {
        $this->assertInstanceOf(
            GenericClaim::class,
            $this->sut()->buildGeneric('value', 'name'),
        );
    }


    public function testCanBuildJwks(): void
    {
        $this->assertInstanceOf(
            JwksClaim::class,
            $this->sut()->buildJwks($this->jwksValueSample),
        );
    }


    public function testBuildJwksThrowsIfNoKeysKey(): void
    {
        $this->expectException(JwksException::class);
        $this->expectExceptionMessage('Invalid');

        $this->sut()->buildJwks([]);
    }


    public function testBuildJwksThrowsIfNoJwkKey(): void
    {
        $this->expectException(JwksException::class);
        $this->expectExceptionMessage('Invalid');

        $this->sut()->buildJwks(['keys' => ['invalid']]);
    }
}
