<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Codebooks;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\StatusTypeEnum;

#[CoversClass(StatusTypeEnum::class)]
final class StatusTypeEnumTest extends TestCase
{
    public function testHasTheValuesTheSpecificationDefines(): void
    {
        $this->assertSame(0x00, StatusTypeEnum::Valid->value);
        $this->assertSame(0x01, StatusTypeEnum::Invalid->value);
        $this->assertSame(0x02, StatusTypeEnum::Suspended->value);
    }


    /**
     * 0x03 and 0x0C to 0x0F are permanently reserved as application specific, and everything else is reserved for
     * future registration, so none of them are Status Types of this library.
     */
    public function testHasNoOtherValues(): void
    {
        $this->assertCount(3, StatusTypeEnum::cases());

        foreach ([0x03, 0x04, 0x0C, 0x0F, 0xFF] as $value) {
            $this->assertNull(StatusTypeEnum::tryFrom($value));
        }
    }


    public function testCanGetRequiredBits(): void
    {
        $this->assertSame(1, StatusTypeEnum::Valid->requiredBits());
        $this->assertSame(1, StatusTypeEnum::Invalid->requiredBits());
        // A one bit list can not carry a suspension, which is the trap this exists to make visible.
        $this->assertSame(2, StatusTypeEnum::Suspended->requiredBits());
    }
}
