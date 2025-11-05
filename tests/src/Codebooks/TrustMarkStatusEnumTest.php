<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Codebooks;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\OpenID\Codebooks\TrustMarkStatusEnum;
use PHPUnit\Framework\TestCase;

#[CoversClass(TrustMarkStatusEnum::class)]
class TrustMarkStatusEnumTest extends TestCase
{
    public function testIsValid(): void
    {
        $this->assertTrue(TrustMarkStatusEnum::Active->isValid());
        $this->assertFalse(TrustMarkStatusEnum::Expired->isValid());;
        $this->assertFalse(TrustMarkStatusEnum::Revoked->isValid());;
        $this->assertFalse(TrustMarkStatusEnum::Invalid->isValid());
    }
}
