<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Codebooks;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\TrustMarkStatusEnum;

#[CoversClass(TrustMarkStatusEnum::class)]
final class TrustMarkStatusEnumTest extends TestCase
{
    public function testIsValid(): void
    {
        $this->assertTrue(TrustMarkStatusEnum::Active->isValid());
        $this->assertFalse(TrustMarkStatusEnum::Expired->isValid());
        ;
        $this->assertFalse(TrustMarkStatusEnum::Revoked->isValid());
        ;
        $this->assertFalse(TrustMarkStatusEnum::Invalid->isValid());
    }
}
