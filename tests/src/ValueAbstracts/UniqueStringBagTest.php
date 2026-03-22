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


    public function testGetAll(): void
    {
        $bag = $this->sut();
        $this->assertSame($this->sampleValues, $bag->getAll());
    }


    public function testHas(): void
    {
        $bag = $this->sut();
        $this->assertTrue($bag->has('foo'));
        $this->assertTrue($bag->has('bar'));
        $this->assertFalse($bag->has('unknown'));
    }


    public function testAdd(): void
    {
        $bag = new UniqueStringBag();
        $this->assertSame([], $bag->getAll());

        $bag->add('foo');
        $this->assertSame(['foo'], $bag->getAll());

        $bag->add('bar', 'baz');
        $this->assertSame(['foo', 'bar', 'baz'], $bag->getAll());
    }


    public function testUniqueness(): void
    {
        // Constructor uniqueness
        $bag = new UniqueStringBag('foo', 'foo', 'bar');
        $this->assertSame(['foo', 'bar'], $bag->getAll());

        // add() uniqueness
        $bag->add('foo');
        $this->assertSame(['foo', 'bar'], $bag->getAll());

        $bag->add('baz', 'bar', 'qux');
        $this->assertSame(['foo', 'bar', 'baz', 'qux'], $bag->getAll());
    }


    public function testJsonSerialize(): void
    {
        $bag = $this->sut();
        $this->assertSame($this->sampleValues, $bag->jsonSerialize());
        $this->assertSame(
            json_encode($this->sampleValues),
            json_encode($bag),
        );
    }
}
