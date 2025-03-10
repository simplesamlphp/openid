<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Claims\JwksClaim;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Federation\Claims\TrustMarkOwnersClaimBag;
use SimpleSAML\OpenID\Federation\Claims\TrustMarkOwnersClaimValue;
use SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimBag;
use SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimValue;
use SimpleSAML\OpenID\Federation\Factories\FederationClaimFactory;
use SimpleSAML\OpenID\Helpers;

#[CoversClass(FederationClaimFactory::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Type::class)]
#[UsesClass(ClaimFactory::class)]
#[UsesClass(JwksClaim::class)]
#[UsesClass(TrustMarksClaimValue::class)]
#[UsesClass(TrustMarksClaimBag::class)]
#[UsesClass(TrustMarkOwnersClaimValue::class)]
#[UsesClass(TrustMarkOwnersClaimBag::class)]
final class FederationClaimFactoryTest extends TestCase
{
    protected Helpers $helpers;

    protected ClaimFactory $claimFactory;

    protected array $jwksArraySample = [
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
        $this->claimFactory = new ClaimFactory($this->helpers);
    }

    public function sut(
        ?Helpers $helpers = null,
        ?ClaimFactory $claimFactory = null,
    ): FederationClaimFactory {
        $helpers ??= $this->helpers;
        $claimFactory ??= $this->claimFactory;

        return new FederationClaimFactory(
            $helpers,
            $claimFactory,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(FederationClaimFactory::class, $this->sut());
    }

    public function testCanBuildTrustMarksClaimValue(): void
    {
        $this->assertInstanceOf(TrustMarksClaimValue::class, $this->sut()->buildTrustMarksClaimValue(
            'trustMarkId',
            'trustMark',
        ));
    }

    public function testCanBuildTrustMarksClaimValueFrom(): void
    {
        $trustMarksClaimData = [
            ClaimsEnum::TrustMarkId->value => 'trustMarkId',
            ClaimsEnum::TrustMark->value => 'trustMark',
            'something' => 'else',
        ];

        $this->assertInstanceOf(
            TrustMarksClaimValue::class,
            $this->sut()->buildTrustMarksClaimValueFrom($trustMarksClaimData),
        );
    }

    public function testCanBuildTrustMarksClaimBag(): void
    {
        $this->assertInstanceOf(
            TrustMarksClaimBag::class,
            $this->sut()->buildTrustMarksClaimBag(),
        );
    }

    public function testCanBuildTrustMarkOwnersClaimValue(): void
    {
        $this->assertInstanceOf(TrustMarkOwnersClaimValue::class, $this->sut()->buildTrustMarkOwnersClaimValue(
            'trustMarkId',
            'subject',
            $this->jwksArraySample,
        ));
    }

    public function testCanBuildTrustMarkOwnersClaimBagFrom(): void
    {
        $trustMarkOwnersClaimData = [
            'trustMarkId' => [
                ClaimsEnum::Sub->value => 'subject',
                ClaimsEnum::Jwks->value => $this->jwksArraySample,
            ],
        ];

        $this->assertInstanceOf(
            TrustMarkOwnersClaimBag::class,
            $this->sut()->buildTrustMarkOwnersClaimBagFrom($trustMarkOwnersClaimData),
        );
    }
}
