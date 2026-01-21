<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\ValueAbstracts;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\ValueAbstracts\UniqueStringBag;

#[CoversClass(UniqueStringBag::class)]
final class UniqueStringBagTest extends TestCase
{
    protected array $sampleValues = ['foo', 'bar'];


    protected function sut(
        ?array $values = null,
    ): UniqueStringBag {
        $values ??= $this->sampleValues;

        return new UniqueStringBag(...$values);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(UniqueStringBag::class, $this->sut());
    }
}
