<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\TokenStatusList;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\StatusTypeEnum;
use SimpleSAML\OpenID\Exceptions\InvalidValueException;
use SimpleSAML\OpenID\Exceptions\StatusListException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\TokenStatusList\StatusList;

#[CoversClass(StatusList::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Base64Url::class)]
#[UsesClass(Helpers\Type::class)]
final class StatusListTest extends TestCase
{
    /**
     * The 1-bit example from the specification: 16 Referenced Tokens in two bytes.
     */
    protected const EXAMPLE_1BIT_BYTES = "\xB9\xA3";

    protected const EXAMPLE_1BIT_LST = 'eNrbuRgAAhcBXQ';

    /**
     * The 2-bit example from the specification: 12 Referenced Tokens in three bytes.
     */
    protected const EXAMPLE_2BIT_BYTES = "\xC9\x44\xF9";

    protected const EXAMPLE_2BIT_LST = 'eNo76fITAAPfAgc';


    protected function sut(
        ?int $bits = null,
        ?string $bytes = null,
        ?string $aggregationUri = null,
        ?Helpers $helpers = null,
    ): StatusList {
        $bits ??= 1;
        $bytes ??= self::EXAMPLE_1BIT_BYTES;
        $helpers ??= new Helpers();

        return new StatusList($bits, $bytes, $aggregationUri, $helpers);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(StatusList::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut();

        $this->assertSame(1, $sut->getBits());
        $this->assertSame(self::EXAMPLE_1BIT_BYTES, $sut->getBytes());
        $this->assertSame(16, $sut->getCapacity());
        $this->assertNull($sut->getAggregationUri());
    }


    /**
     * @return \Iterator<string, array{int, int}>
     */
    public static function capacityProvider(): \Iterator
    {
        yield '1 bit, 8 entries per byte' => [1, 8];
        yield '2 bits, 4 entries per byte' => [2, 4];
        yield '4 bits, 2 entries per byte' => [4, 2];
        yield '8 bits, 1 entry per byte' => [8, 1];
    }


    #[DataProvider('capacityProvider')]
    public function testCapacityFollowsFromBits(int $bits, int $entriesPerByte): void
    {
        $this->assertSame($entriesPerByte * 3, $this->sut($bits, "\x00\x00\x00")->getCapacity());
    }


    /**
     * The bit values of the 1-bit example in the specification, in index order.
     */
    public function testDecodesTheOneBitSpecificationExample(): void
    {
        $expected = [1, 0, 0, 1, 1, 1, 0, 1, 1, 1, 0, 0, 0, 1, 0, 1];

        $sut = $this->sut(1, self::EXAMPLE_1BIT_BYTES);

        foreach ($expected as $idx => $status) {
            $this->assertSame($status, $sut->get($idx), sprintf('Index %d.', $idx));
        }
    }


    /**
     * The 2-bit example, which is where a reversed packing order stops being ambiguous.
     */
    public function testDecodesTheTwoBitSpecificationExample(): void
    {
        $expected = [0b01, 0b10, 0b00, 0b11, 0b00, 0b01, 0b00, 0b01, 0b01, 0b10, 0b11, 0b11];

        $sut = $this->sut(2, self::EXAMPLE_2BIT_BYTES);

        foreach ($expected as $idx => $status) {
            $this->assertSame($status, $sut->get($idx), sprintf('Index %d.', $idx));
        }
    }


    public function testCanGetStatusType(): void
    {
        $sut = $this->sut(2, self::EXAMPLE_2BIT_BYTES);

        $this->assertSame(StatusTypeEnum::Invalid, $sut->getStatusType(0));
        $this->assertSame(StatusTypeEnum::Suspended, $sut->getStatusType(1));
        $this->assertSame(StatusTypeEnum::Valid, $sut->getStatusType(2));
    }


    /**
     * 0x03 is permanently reserved as application specific, so it has no Status Type of its own.
     */
    public function testGetStatusTypeIsNullForUnregisteredValue(): void
    {
        $sut = $this->sut(2, self::EXAMPLE_2BIT_BYTES);

        $this->assertNotInstanceOf(StatusTypeEnum::class, $sut->getStatusType(3));
        $this->assertSame(0b11, $sut->get(3));
    }


    public function testCanSetStatus(): void
    {
        $sut = $this->sut(2, "\x00");

        $updated = $sut->withStatus(2, StatusTypeEnum::Suspended);

        $this->assertSame(StatusTypeEnum::Suspended, $updated->getStatusType(2));
        // Immutable, so the original is untouched.
        $this->assertSame(StatusTypeEnum::Valid, $sut->getStatusType(2));
    }


    public function testSettingStatusLeavesNeighbouringIndicesAlone(): void
    {
        $sut = $this->sut(4, "\xFF\xFF")
            ->withStatus(1, 0b0000);

        $this->assertSame(0b1111, $sut->get(0));
        $this->assertSame(0b0000, $sut->get(1));
        $this->assertSame(0b1111, $sut->get(2));
        $this->assertSame(0b1111, $sut->get(3));
    }


    public function testCanOverwriteAnExistingStatus(): void
    {
        $sut = $this->sut(4, "\x00")
            ->withStatus(0, 0b1010)
            ->withStatus(0, 0b0101);

        $this->assertSame(0b0101, $sut->get(0));
    }


    /**
     * @return \Iterator<string, array{int}>
     */
    public static function invalidBitsProvider(): \Iterator
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
        yield 'three' => [3];
        yield 'five' => [5];
        yield 'sixteen' => [16];
    }


    #[DataProvider('invalidBitsProvider')]
    public function testThrowsForInvalidBits(int $bits): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('Invalid number of bits');

        $this->sut($bits);
    }


    public function testThrowsForEmptyByteArray(): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('at least one index');

        $this->sut(1, '');
    }


    public function testThrowsForNonUriAggregationUri(): void
    {
        $this->expectException(InvalidValueException::class);

        $this->sut(1, self::EXAMPLE_1BIT_BYTES, 'not a uri');
    }


    /**
     * @return \Iterator<string, array{int}>
     */
    public static function outOfRangeIndexProvider(): \Iterator
    {
        yield 'negative' => [-1];
        yield 'one past the end' => [16];
        yield 'far past the end' => [100000];
    }


    /**
     * An index outside the list is not VALID: no statement about the Referenced Token can be made.
     */
    #[DataProvider('outOfRangeIndexProvider')]
    public function testGetThrowsForOutOfRangeIndex(int $idx): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('out of bounds');

        $this->sut()->get($idx);
    }


    #[DataProvider('outOfRangeIndexProvider')]
    public function testWithStatusThrowsForOutOfRangeIndex(int $idx): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('out of bounds');

        $this->sut()->withStatus($idx, 1);
    }


    /**
     * @return \Iterator<string, array{int, int}>
     */
    public static function oversizedStatusProvider(): \Iterator
    {
        yield '1 bit can not hold 2' => [1, 2];
        yield '1 bit can not hold SUSPENDED' => [1, StatusTypeEnum::Suspended->value];
        yield '2 bits can not hold 4' => [2, 4];
        yield '4 bits can not hold 16' => [4, 16];
        yield '8 bits can not hold 256' => [8, 256];
    }


    #[DataProvider('oversizedStatusProvider')]
    public function testWithStatusThrowsForStatusThatDoesNotFit(int $bits, int $status): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('can not be represented');

        $this->sut($bits, "\x00\x00")->withStatus(0, $status);
    }


    public function testWithStatusThrowsForNegativeStatus(): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('can not be represented');

        $this->sut(8, "\x00")->withStatus(0, -1);
    }


    /**
     * @return \Iterator<string, array{int, string, string}>
     */
    public static function encodingProvider(): \Iterator
    {
        yield '1 bit' => [1, self::EXAMPLE_1BIT_BYTES, self::EXAMPLE_1BIT_LST];
        yield '2 bit' => [2, self::EXAMPLE_2BIT_BYTES, self::EXAMPLE_2BIT_LST];
    }


    /**
     * Reproduces the encoded lists the specification prints for its own examples.
     */
    #[DataProvider('encodingProvider')]
    public function testCanEncode(int $bits, string $bytes, string $lst): void
    {
        $this->assertSame($lst, $this->sut($bits, $bytes)->toEncoded());
    }


    #[DataProvider('encodingProvider')]
    public function testCanJsonSerialize(int $bits, string $bytes, string $lst): void
    {
        $this->assertSame(
            ['bits' => $bits, 'lst' => $lst],
            $this->sut($bits, $bytes)->jsonSerialize(),
        );
    }


    public function testJsonSerializeIncludesAggregationUriWhenSet(): void
    {
        $sut = $this->sut(1, self::EXAMPLE_1BIT_BYTES, 'https://example.org/statuslists');

        $this->assertSame(
            [
                'bits' => 1,
                'lst' => self::EXAMPLE_1BIT_LST,
                'aggregation_uri' => 'https://example.org/statuslists',
            ],
            $sut->jsonSerialize(),
        );
    }


    public function testAggregationUriSurvivesWithStatus(): void
    {
        $sut = $this->sut(1, self::EXAMPLE_1BIT_BYTES, 'https://example.org/statuslists')
            ->withStatus(0, 0);

        $this->assertSame('https://example.org/statuslists', $sut->getAggregationUri());
    }


    public function testIsAllowedBits(): void
    {
        foreach ([1, 2, 4, 8] as $bits) {
            $this->assertTrue(StatusList::isAllowedBits($bits));
        }

        foreach ([0, -1, 3, 5, 7, 9, 16] as $bits) {
            $this->assertFalse(StatusList::isAllowedBits($bits));
        }
    }
}
