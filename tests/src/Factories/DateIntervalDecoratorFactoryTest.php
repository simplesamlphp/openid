<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Factories;

use DateInterval;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;

#[CoversClass(DateIntervalDecoratorFactory::class)]
#[UsesClass(DateIntervalDecorator::class)]
final class DateIntervalDecoratorFactoryTest extends TestCase
{
    protected DateInterval $dateInterval;


    protected function setUp(): void
    {
        $this->dateInterval = new DateInterval('P1D');
    }


    protected function sut(): DateIntervalDecoratorFactory
    {
        return new DateIntervalDecoratorFactory();
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(DateIntervalDecoratorFactory::class, $this->sut());
    }


    public function testCanBuild(): void
    {
        $this->assertInstanceOf(
            DateIntervalDecorator::class,
            $this->sut()->build($this->dateInterval),
        );
    }
}
