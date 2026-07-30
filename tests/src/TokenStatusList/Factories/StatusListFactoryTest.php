<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\TokenStatusList\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\StatusTypeEnum;
use SimpleSAML\OpenID\Exceptions\InvalidValueException;
use SimpleSAML\OpenID\Exceptions\StatusListException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\TokenStatusList\Factories\StatusListFactory;
use SimpleSAML\OpenID\TokenStatusList\StatusList;

#[CoversClass(StatusListFactory::class)]
#[UsesClass(StatusList::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Base64Url::class)]
#[UsesClass(Helpers\Type::class)]
final class StatusListFactoryTest extends TestCase
{
    protected const EXAMPLE_1BIT_LST = 'eNrbuRgAAhcBXQ';


    protected function sut(?Helpers $helpers = null): StatusListFactory
    {
        $helpers ??= new Helpers();

        return new StatusListFactory($helpers);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(StatusListFactory::class, $this->sut());
    }


    public function testCanBuild(): void
    {
        $statusList = $this->sut()->build(1, "\xB9\xA3");

        $this->assertSame(1, $statusList->getBits());
        $this->assertSame(16, $statusList->getCapacity());
    }


    public function testCanBuildForCapacity(): void
    {
        $statusList = $this->sut()->forCapacity(1024, 2);

        $this->assertSame(1024, $statusList->getCapacity());
        $this->assertSame(256, strlen($statusList->getBytes()));
        $this->assertSame(0, $statusList->get(0));
        $this->assertSame(0, $statusList->get(1023));
    }


    /**
     * A capacity that is not a whole number of bytes is rounded up, so the list conveys a status for a few more
     * indices than requested rather than for fewer.
     */
    public function testForCapacityRoundsUpToWholeBytes(): void
    {
        $this->assertSame(16, $this->sut()->forCapacity(9, 1)->getCapacity());
        $this->assertSame(4, $this->sut()->forCapacity(3, 2)->getCapacity());
        $this->assertSame(1, $this->sut()->forCapacity(1, 8)->getCapacity());
    }


    public function testForCapacityThrowsForNonPositiveCapacity(): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('capacity must be at least 1');

        $this->sut()->forCapacity(0, 1);
    }


    public function testForCapacityThrowsForInvalidBits(): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('Invalid number of bits');

        $this->sut()->forCapacity(1024, 3);
    }


    public function testCanBuildFromEntries(): void
    {
        $statusList = $this->sut()->fromEntries(
            [
                0 => StatusTypeEnum::Invalid,
                5 => StatusTypeEnum::Suspended,
                1023 => StatusTypeEnum::Invalid,
            ],
            2,
            1024,
        );

        $this->assertSame(StatusTypeEnum::Invalid, $statusList->getStatusType(0));
        $this->assertSame(StatusTypeEnum::Valid, $statusList->getStatusType(1));
        $this->assertSame(StatusTypeEnum::Suspended, $statusList->getStatusType(5));
        $this->assertSame(StatusTypeEnum::Invalid, $statusList->getStatusType(1023));
    }


    public function testCanBuildFromEntriesGivenAGenerator(): void
    {
        $entries = (function (): \Generator {
            yield 3 => StatusTypeEnum::Invalid;
            yield 9 => StatusTypeEnum::Invalid;
        })();

        $statusList = $this->sut()->fromEntries($entries, 1, 16);

        $this->assertSame(1, $statusList->get(3));
        $this->assertSame(1, $statusList->get(9));
        $this->assertSame(0, $statusList->get(4));
    }


    public function testCanBuildFromEntriesGivenRawIntegerStatuses(): void
    {
        $statusList = $this->sut()->fromEntries([0 => 0b1111], 4, 16);

        $this->assertSame(0b1111, $statusList->get(0));
    }


    public function testFromEntriesThrowsForOutOfRangeIndex(): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('out of bounds');

        $this->sut()->fromEntries([64 => StatusTypeEnum::Invalid], 1, 16);
    }


    public function testFromEntriesThrowsForNegativeIndex(): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('out of bounds');

        $this->sut()->fromEntries([-1 => StatusTypeEnum::Invalid], 1, 16);
    }


    /**
     * A pool configured with 1 bit can never carry SUSPENDED, and reconfiguration can not retrofit an existing
     * list, so this has to fail loudly rather than truncate.
     */
    public function testFromEntriesThrowsForStatusThatDoesNotFit(): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('can not be represented');

        $this->sut()->fromEntries([0 => StatusTypeEnum::Suspended], 1, 16);
    }


    public function testFromEntriesThrowsForNonIntegerIndex(): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('index must be an integer');

        $this->sut()->fromEntries(['not-an-index' => StatusTypeEnum::Invalid], 1, 16);
    }


    public function testCanBuildFromEncoded(): void
    {
        $statusList = $this->sut()->fromEncoded(self::EXAMPLE_1BIT_LST, 1, 2);

        $this->assertSame("\xB9\xA3", $statusList->getBytes());
        $this->assertSame(16, $statusList->getCapacity());
    }


    public function testCanRoundTrip(): void
    {
        $original = $this->sut()->fromEntries([0 => 1, 5 => 2, 300 => 3], 2, 1024);

        $decoded = $this->sut()->fromEncoded($original->toEncoded(), 2, 256);

        $this->assertSame($original->getBytes(), $decoded->getBytes());
    }


    /**
     * @return \Iterator<string, array{string}>
     */
    public static function invalidEncodingProvider(): \Iterator
    {
        yield 'padded' => ['eNrbuRgAAhcBXQ=='];
        yield 'standard base64 plus' => ['eNrbuRgAAhc+XQ'];
        yield 'standard base64 slash' => ['eNrbuRgAAhc/XQ'];
        yield 'leading whitespace' => [' eNrbuRgAAhcBXQ'];
        yield 'embedded newline' => ["eNrbuRgA\nAhcBXQ"];
        // A regular expression anchored with $ rather than \z would let this one through, $ also matching
        // immediately before a trailing newline.
        yield 'trailing newline' => ["eNrbuRgAAhcBXQ\n"];
        yield 'empty' => [''];
    }


    /**
     * Only the one encoding the specification defines is accepted; nothing is normalised into shape first.
     */
    #[DataProvider('invalidEncodingProvider')]
    public function testFromEncodedThrowsForNonStrictBase64Url(string $lst): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('unpadded base64url');

        $this->sut()->fromEncoded($lst, 1, 1024);
    }


    /**
     * The unused bits of the final base64url quantum have to be zero. Left unchecked, base64_decode() ignores
     * them and two different strings decode to the very same list.
     */
    public function testFromEncodedThrowsForNonCanonicalPadBits(): void
    {
        // Differs from the canonical encoding only in the unused bits of its last character.
        $this->assertSame(
            (new Helpers())->base64Url()->decode('eNrbuRgAAhcBXR'),
            (new Helpers())->base64Url()->decode(self::EXAMPLE_1BIT_LST),
        );

        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('not canonically encoded');

        $this->sut()->fromEncoded('eNrbuRgAAhcBXR', 1, 1024);
    }


    /**
     * gzuncompress() stops at the end of the first stream, so without an explicit check a list with bytes
     * appended would decode to exactly the same statuses as the clean one.
     */
    public function testFromEncodedThrowsForTrailingDataAfterTheStream(): void
    {
        $helpers = new Helpers();
        $compressed = gzcompress("\xB9\xA3", 9);
        $this->assertIsString($compressed);

        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('trailing byte');

        $this->sut()->fromEncoded($helpers->base64Url()->encode($compressed . 'TRAILING'), 1, 1024);
    }


    public function testFromEncodedThrowsForASecondAppendedStream(): void
    {
        $helpers = new Helpers();
        $compressed = gzcompress("\xB9\xA3", 9);
        $this->assertIsString($compressed);

        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('trailing byte');

        $this->sut()->fromEncoded($helpers->base64Url()->encode($compressed . $compressed), 1, 1024);
    }


    /**
     * The default compressed bound must never be tighter than what this library's own encoder emits, or a list
     * would fail to survive its own round trip.
     */
    public function testDefaultCompressedBoundAcceptsThisLibrarysOwnWorstCaseOutput(): void
    {
        foreach ([1024, 16384, 73600, 131072] as $byteLength) {
            $bytes = random_bytes($byteLength);
            $encoded = $this->sut()->build(8, $bytes)->toEncoded();

            $this->assertSame(
                $bytes,
                $this->sut()->fromEncoded($encoded, 8, $byteLength)->getBytes(),
                sprintf('A %d byte list did not survive the round trip.', $byteLength),
            );
        }
    }


    public function testFromEncodedThrowsForUndecompressableInput(): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('Unable to decompress');

        $this->sut()->fromEncoded('bm90LXpsaWI', 1, 1024);
    }


    /**
     * Raw DEFLATE without the ZLIB wrapper is not what the specification asks for.
     */
    public function testFromEncodedThrowsForRawDeflateStream(): void
    {
        $rawDeflate = gzdeflate("\xB9\xA3", 9);
        $this->assertIsString($rawDeflate);

        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('Unable to decompress');

        $this->sut()->fromEncoded(
            (new Helpers())->base64Url()->encode($rawDeflate),
            1,
            1024,
        );
    }


    /**
     * A stream that stops partway through is not a list. The inflater reports no error of its own for one, since
     * as far as it knows the rest of the input was still to come.
     */
    public function testFromEncodedThrowsForATruncatedStream(): void
    {
        $compressed = gzcompress(str_repeat("\xB9\xA3", 64), 9);
        $this->assertIsString($compressed);

        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('does not hold a complete ZLIB stream');

        $this->sut()->fromEncoded(
            (new Helpers())->base64Url()->encode(substr($compressed, 0, strlen($compressed) - 4)),
            1,
            1024,
        );
    }


    /**
     * A small compressed input decompressing to far more than the caller is willing to hold is refused before the
     * memory is allocated.
     */
    public function testFromEncodedThrowsForDecompressionBomb(): void
    {
        $bomb = gzcompress(str_repeat("\x00", 10 * 1024 * 1024), 9);
        $this->assertIsString($bomb);

        $encoded = (new Helpers())->base64Url()->encode($bomb);

        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('decompresses to more than 1024 bytes');

        $this->sut()->fromEncoded($encoded, 1, 1024, strlen($bomb));
    }


    /**
     * The encoded length is checked before anything is decoded.
     */
    public function testFromEncodedThrowsForOversizedEncodedInput(): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('exceeds the maximum of');

        $this->sut()->fromEncoded(str_repeat('A', 1000), 1, 8, 10);
    }


    /**
     * ... and the decoded length is checked before decompression is attempted.
     */
    public function testFromEncodedThrowsForOversizedCompressedInput(): void
    {
        // 16 base64url characters decode to 12 bytes, which passes the encoded-length bound of 16 for a
        // maximum of 10 compressed bytes but not the compressed-length bound itself.
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('Compressed Status List is 12 bytes long');

        $this->sut()->fromEncoded(str_repeat('A', 16), 1, 8, 10);
    }


    public function testFromEncodedThrowsForNonPositiveMaxDecompressedBytes(): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('decompressed size must be at least 1 byte');

        $this->sut()->fromEncoded(self::EXAMPLE_1BIT_LST, 1, 0);
    }


    public function testFromEncodedThrowsForNonPositiveMaxCompressedBytes(): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('compressed size must be at least 1 byte');

        $this->sut()->fromEncoded(self::EXAMPLE_1BIT_LST, 1, 1024, 0);
    }


    /**
     * The default compressed bound accommodates a list that does not compress at all, which is what a list full of
     * unique 8-bit statuses approaches.
     */
    public function testDefaultCompressedBoundAcceptsIncompressibleInput(): void
    {
        $bytes = random_bytes(65536);
        $encoded = $this->sut()->build(8, $bytes)->toEncoded();

        $this->assertSame($bytes, $this->sut()->fromEncoded($encoded, 8, 65536)->getBytes());
    }


    public function testCanBuildFromClaimData(): void
    {
        $statusList = $this->sut()->fromClaimData(
            ['bits' => 1, 'lst' => self::EXAMPLE_1BIT_LST],
            1024,
        );

        $this->assertSame(1, $statusList->getBits());
        $this->assertSame("\xB9\xA3", $statusList->getBytes());
        $this->assertNull($statusList->getAggregationUri());
    }


    public function testCanBuildFromClaimDataWithAggregationUri(): void
    {
        $statusList = $this->sut()->fromClaimData(
            [
                'bits' => 1,
                'lst' => self::EXAMPLE_1BIT_LST,
                'aggregation_uri' => 'https://example.org/statuslists',
            ],
            1024,
        );

        $this->assertSame('https://example.org/statuslists', $statusList->getAggregationUri());
    }


    /**
     * @return \Iterator<string, array{mixed}>
     */
    public static function invalidBitsClaimProvider(): \Iterator
    {
        yield 'missing' => [null];
        yield 'numeric string' => ['1'];
        yield 'float' => [1.0];
        yield 'boolean' => [true];
        yield 'array' => [[1]];
    }


    /**
     * `bits` is a JSON Integer, so a numeric string does not satisfy it.
     */
    #[DataProvider('invalidBitsClaimProvider')]
    public function testFromClaimDataThrowsForNonIntegerBits(mixed $bits): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('bits claim must be an integer');

        $this->sut()->fromClaimData(['bits' => $bits, 'lst' => self::EXAMPLE_1BIT_LST], 1024);
    }


    public function testFromClaimDataThrowsForMissingList(): void
    {
        $this->expectException(InvalidValueException::class);

        $this->sut()->fromClaimData(['bits' => 1], 1024);
    }


    public function testFromClaimDataThrowsForUnsupportedBits(): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('Invalid number of bits');

        $this->sut()->fromClaimData(['bits' => 3, 'lst' => self::EXAMPLE_1BIT_LST], 1024);
    }
}
