<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Codebooks;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\VerificationRelationshipEnum;

#[CoversClass(VerificationRelationshipEnum::class)]
final class VerificationRelationshipEnumTest extends TestCase
{
    public function testSignatureUseCoversEveryRelationshipButKeyAgreement(): void
    {
        $signatureRelationships = VerificationRelationshipEnum::forSignatureUse();

        $this->assertNotContains(VerificationRelationshipEnum::KeyAgreement, $signatureRelationships);
        $this->assertContains(VerificationRelationshipEnum::Authentication, $signatureRelationships);
        $this->assertContains(VerificationRelationshipEnum::AssertionMethod, $signatureRelationships);
        $this->assertContains(VerificationRelationshipEnum::CapabilityDelegation, $signatureRelationships);
        $this->assertContains(VerificationRelationshipEnum::CapabilityInvocation, $signatureRelationships);
    }


    public function testEncryptionUseIsKeyAgreementOnly(): void
    {
        $this->assertSame(
            [VerificationRelationshipEnum::KeyAgreement],
            VerificationRelationshipEnum::forEncryptionUse(),
        );
    }


    public function testTheTwoUsesPartitionEveryRelationship(): void
    {
        $this->assertEqualsCanonicalizing(
            VerificationRelationshipEnum::cases(),
            array_merge(
                VerificationRelationshipEnum::forSignatureUse(),
                VerificationRelationshipEnum::forEncryptionUse(),
            ),
        );
    }
}
