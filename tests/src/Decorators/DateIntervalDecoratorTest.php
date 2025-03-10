<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Decorators;

use DateInterval;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;

#[CoversClass(DateIntervalDecorator::class)]
final class DateIntervalDecoratorTest extends TestCase
{
    protected DateInterval $dateInterval;

    protected function setUp(): void
    {
        $this->dateInterval = new DateInterval('P1D');
    }

    protected function sut(
        ?DateInterval $dateInterval = null,
    ): DateIntervalDecorator {
        $dateInterval ??= $this->dateInterval;

        return new DateIntervalDecorator($dateInterval);
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(DateIntervalDecorator::class, $this->sut());
    }

    public function testCanGetInSeconds(): void
    {
        $this->assertSame(86400, $this->sut()->getInSeconds());
    }

    public function testCanGetLowestInSecondsComparedToExpirationTime(): void
    {
        $expirationTime = time() + 100;
        $this->assertSame(
            100,
            $this->sut()->lowestInSecondsComparedToExpirationTime($expirationTime),
        );

        $expirationTime = time() + 100 + 86400;
        $this->assertSame(
            86400,
            $this->sut()->lowestInSecondsComparedToExpirationTime($expirationTime),
        );
    }
}
