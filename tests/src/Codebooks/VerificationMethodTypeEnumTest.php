<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Codebooks;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\VerificationMethodTypeEnum;

#[CoversClass(VerificationMethodTypeEnum::class)]
final class VerificationMethodTypeEnumTest extends TestCase
{
    public function testJwkTypesCarryPublicKeyJwk(): void
    {
        $types = VerificationMethodTypeEnum::withPublicKeyJwk();

        $this->assertContains(VerificationMethodTypeEnum::JsonWebKey, $types);
        $this->assertContains(VerificationMethodTypeEnum::JsonWebKey2020, $types);
    }


    public function testMultibaseTypesCarryPublicKeyMultibase(): void
    {
        $types = VerificationMethodTypeEnum::withPublicKeyMultibase();

        $this->assertContains(VerificationMethodTypeEnum::Multikey, $types);
        $this->assertContains(VerificationMethodTypeEnum::Ed25519VerificationKey2020, $types);
    }


    public function testOnlyTheSuiteSpecificTypePinsACurve(): void
    {
        $this->assertSame('Ed25519', VerificationMethodTypeEnum::Ed25519VerificationKey2020->expectedCurve());
        $this->assertNull(VerificationMethodTypeEnum::Multikey->expectedCurve());
        $this->assertNull(VerificationMethodTypeEnum::JsonWebKey->expectedCurve());
        $this->assertNull(VerificationMethodTypeEnum::JsonWebKey2020->expectedCurve());
    }


    public function testEveryTypeCarriesExactlyOneKindOfKeyMaterial(): void
    {
        $jwkTypes = VerificationMethodTypeEnum::withPublicKeyJwk();
        $multibaseTypes = VerificationMethodTypeEnum::withPublicKeyMultibase();

        $this->assertSame(
            [],
            array_intersect(array_column($jwkTypes, 'value'), array_column($multibaseTypes, 'value')),
        );

        $this->assertEqualsCanonicalizing(
            VerificationMethodTypeEnum::cases(),
            array_merge($jwkTypes, $multibaseTypes),
        );
    }
}
