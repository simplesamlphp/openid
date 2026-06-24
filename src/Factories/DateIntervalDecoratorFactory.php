<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Factories;

use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;

/**
 * @see \SimpleSAML\Test\OpenID\Factories\DateIntervalDecoratorFactoryTest
 */
class DateIntervalDecoratorFactory
{
    public function build(\DateInterval $dateInterval): DateIntervalDecorator
    {
        return new DateIntervalDecorator($dateInterval);
    }
}
