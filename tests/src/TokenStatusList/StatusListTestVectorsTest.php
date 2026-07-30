<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\TokenStatusList;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\TokenStatusList\Factories\StatusListFactory;
use SimpleSAML\OpenID\TokenStatusList\StatusList;
use SimpleSAML\Test\OpenID\Help;

/**
 * Conformance to the Status List encoding test vectors in Appendix C of the specification.
 *
 * The fixture is generated from the specification text itself by
 * tools/extract-status-list-test-vectors.php, since the 8-bit vector alone asserts 256 indices.
 */
#[CoversClass(StatusList::class)]
#[CoversClass(StatusListFactory::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Base64Url::class)]
#[UsesClass(Helpers\Type::class)]
final class StatusListTestVectorsTest extends TestCase
{
    /**
     * @var array<string,array{bits:int,capacity:int,byteLength:int,statuses:array<string,int>,lst:string}>
     */
    protected static array $testVectors;

    protected static StatusListFactory $statusListFactory;


    public static function setUpBeforeClass(): void
    {
        $json = file_get_contents(
            (new Help())->getTestDataDir('status-list-test-vectors-appendix-c.json'),
        );

        self::$testVectors = (array)json_decode((string)$json, true, 512, JSON_THROW_ON_ERROR);
        self::$statusListFactory = new StatusListFactory(new Helpers());
    }


    /**
     * @return \Iterator<string, array{string}>
     */
    public static function vectorNameProvider(): \Iterator
    {
        yield 'C.1 (1-bit)' => ['C.1'];
        yield 'C.2 (2-bit)' => ['C.2'];
        yield 'C.3 (4-bit)' => ['C.3'];
        yield 'C.4 (8-bit)' => ['C.4'];
    }


    public function testCanLoadTestVectors(): void
    {
        $this->assertCount(4, self::$testVectors);
    }


    /**
     * Every index the specification asserts a status for decodes to that status, and the list is the size the
     * vector says it is.
     */
    #[DataProvider('vectorNameProvider')]
    public function testDecodesEveryAssertedIndex(string $name): void
    {
        $vector = self::$testVectors[$name];

        $statusList = self::$statusListFactory->fromEncoded(
            $vector['lst'],
            $vector['bits'],
            $vector['byteLength'],
        );

        $this->assertSame($vector['byteLength'], strlen($statusList->getBytes()));
        $this->assertSame($vector['capacity'], $statusList->getCapacity());

        foreach ($vector['statuses'] as $idx => $expectedStatus) {
            $this->assertSame(
                $expectedStatus,
                $statusList->get((int)$idx),
                sprintf('%s: index %s decoded to the wrong status.', $name, $idx),
            );
        }
    }


    /**
     * Indices the vector says nothing about read as 0 (VALID). Appendix C states that all values not mentioned
     * can be assumed to be 0, so a decoder that shifted statuses would be caught here as well as above.
     */
    #[DataProvider('vectorNameProvider')]
    public function testUnlistedIndicesAreValid(string $name): void
    {
        $vector = self::$testVectors[$name];

        $statusList = self::$statusListFactory->fromEncoded(
            $vector['lst'],
            $vector['bits'],
            $vector['byteLength'],
        );

        $asserted = array_map(intval(...), array_keys($vector['statuses']));

        // Neighbours of asserted indices are where an off-by-one in the packing shows up first.
        $probes = [];
        foreach ($asserted as $idx) {
            $probes[] = $idx - 1;
            $probes[] = $idx + 1;
        }

        $probes = array_merge($probes, [1, 2, 3, 7, 8, 9, 100, 65535, $vector['capacity'] - 1]);

        foreach ($probes as $idx) {
            if ($idx < 0) {
                continue;
            }

            if ($idx >= $vector['capacity']) {
                continue;
            }

            if (in_array($idx, $asserted, true)) {
                continue;
            }

            $this->assertSame(
                0,
                $statusList->get($idx),
                sprintf('%s: unlisted index %d did not decode as VALID.', $name, $idx),
            );
        }
    }


    /**
     * Rebuilding the list from its asserted entries reproduces the exact `lst` string the specification publishes.
     *
     * This is a canary rather than a conformance requirement: the specification only RECOMMENDS the highest
     * compression level, and decoders must accept any valid DEFLATE stream, so a zlib whose output differs is not
     * a defect. It does mean this library's encoder still agrees with the reference implementation byte for byte.
     */
    #[DataProvider('vectorNameProvider')]
    public function testEncoderReproducesSpecificationBytes(string $name): void
    {
        $vector = self::$testVectors[$name];

        $entries = [];
        foreach ($vector['statuses'] as $idx => $status) {
            $entries[(int)$idx] = $status;
        }

        $statusList = self::$statusListFactory->fromEntries(
            $entries,
            $vector['bits'],
            $vector['capacity'],
        );

        $this->assertSame($vector['lst'], $statusList->toEncoded());
    }


    /**
     * fromEntries() and repeated withStatus() produce the same list.
     */
    #[DataProvider('vectorNameProvider')]
    public function testBulkMaterialisationMatchesRepeatedWithStatus(string $name): void
    {
        $vector = self::$testVectors[$name];

        $entries = [];
        foreach ($vector['statuses'] as $idx => $status) {
            $entries[(int)$idx] = $status;
        }

        $bulk = self::$statusListFactory->fromEntries($entries, $vector['bits'], $vector['capacity']);

        $applied = self::$statusListFactory->forCapacity($vector['capacity'], $vector['bits']);
        foreach ($entries as $idx => $status) {
            $applied = $applied->withStatus($idx, $status);
        }

        $this->assertSame($bulk->getBytes(), $applied->getBytes());
    }


    /**
     * A gap in the entries leaves that index VALID instead of shifting every status after it. Keying on iteration
     * position rather than on the supplied index would silently misreport the status of every later Referenced
     * Token, so this is asserted directly.
     */
    public function testEntriesAreKeyedOnIndexNotOnPosition(): void
    {
        $vector = self::$testVectors['C.1'];

        $entries = [];
        foreach ($vector['statuses'] as $idx => $status) {
            $entries[(int)$idx] = $status;
        }

        // Drop the first asserted index, keeping the rest at the indices they belong to. Note that array_shift()
        // would renumber the integer keys and so defeat the point of the test.
        $firstIdx = (int)array_key_first($entries);
        $withoutFirst = $entries;
        unset($withoutFirst[$firstIdx]);

        $statusList = self::$statusListFactory->fromEntries(
            $withoutFirst,
            $vector['bits'],
            $vector['capacity'],
        );

        $this->assertSame(0, $statusList->get($firstIdx));

        foreach ($withoutFirst as $idx => $expectedStatus) {
            $this->assertSame($expectedStatus, $statusList->get($idx));
        }
    }
}
