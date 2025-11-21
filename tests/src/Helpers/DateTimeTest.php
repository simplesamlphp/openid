<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Helpers;

use DateTimeImmutable as NativeDateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Helpers\DateTime as DateTimeHelper;

#[CoversClass(DateTimeHelper::class)]
final class DateTimeTest extends TestCase
{
    public function testFromXsDateTimeParsesRfc3339ExtendedWithMicrosecondsAndOffset(): void
    {
        $helper = new DateTimeHelper();
        $input = '2024-03-01T12:34:56.123456+02:00';

        $result = $helper->fromXsDateTime($input, new DateTimeZone('UTC'));
        $expected = new NativeDateTimeImmutable($input, new DateTimeZone('UTC'));

        $this->assertSame($expected->getTimestamp(), $result->getTimestamp());
        $this->assertSame($expected->format('u'), $result->format('u')); // microseconds
        $this->assertSame($expected->format('P'), $result->format('P')); // timezone offset
    }


    public function testFromXsDateTimeFallsBackToAtomWithoutMicroseconds(): void
    {
        $helper = new DateTimeHelper();
        $input = '2024-03-01T12:34:56+02:00';

        $result = $helper->fromXsDateTime($input, new DateTimeZone('UTC'));
        $expected = new NativeDateTimeImmutable($input, new DateTimeZone('UTC'));

        $this->assertSame($expected->getTimestamp(), $result->getTimestamp());
        $this->assertSame($expected->format('P'), $result->format('P'));
    }


    public function testFromXsDateTimeFallsBackToConstructorIso8601Zulu(): void
    {
        $helper = new DateTimeHelper();
        $input = '2024-03-01T12:34:56Z';

        $result = $helper->fromXsDateTime($input, new DateTimeZone('Europe/Prague'));
        $expected = new NativeDateTimeImmutable($input, new DateTimeZone('Europe/Prague'));

        $this->assertSame($expected->getTimestamp(), $result->getTimestamp());
        $this->assertSame('+00:00', $result->format('P'));
    }


    public function testFromXsDateTimeUsesProvidedTimezoneWhenInputHasNoOffset(): void
    {
        $helper = new DateTimeHelper();
        $input = '2024-03-01T12:34:56'; // no timezone info in the string
        $tz = new DateTimeZone('UTC');

        $result = $helper->fromXsDateTime($input, $tz);
        $expected = new NativeDateTimeImmutable($input, $tz);

        $this->assertSame($expected->getTimestamp(), $result->getTimestamp());
        $this->assertSame('+00:00', $result->format('P'));
    }


    public function testGetUtcReturnsDateInUtc(): void
    {
        $helper = new DateTimeHelper();
        $input = '2024-03-01 12:00:00';

        $result = $helper->getUtc($input);
        $expected = new NativeDateTimeImmutable($input, new DateTimeZone('UTC'));

        $this->assertSame($expected->getTimestamp(), $result->getTimestamp());
        $this->assertSame('+00:00', $result->format('P'));
    }


    public function testFromTimestampCreatesUtcDateTime(): void
    {
        $helper = new DateTimeHelper();
        $timestamp = 1_728_000; // 20 days after epoch

        $result = $helper->fromTimestamp($timestamp);

        $this->assertSame($timestamp, $result->getTimestamp());
        $this->assertSame('+00:00', $result->format('P'));
    }
}
