<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\ValueAbstracts;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\ValueAbstracts\KeyedString;
use SimpleSAML\OpenID\ValueAbstracts\KeyedStringBag;

#[CoversClass(KeyedStringBag::class)]
#[UsesClass(KeyedString::class)]
final class KeyedStringBagTest extends TestCase
{
    private KeyedString $keyedString1;

    private KeyedString $keyedString2;


    protected function setUp(): void
    {
        $this->keyedString1 = new KeyedString('key1', 'value1');
        $this->keyedString2 = new KeyedString('key2', 'value2');
    }


    protected function sut(
        KeyedString ...$keyedStrings,
    ): KeyedStringBag {
        return new KeyedStringBag(...$keyedStrings);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(KeyedStringBag::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut($this->keyedString1, $this->keyedString2);

        $this->assertTrue($sut->has('key1'));
        $this->assertTrue($sut->has('key2'));
        $this->assertFalse($sut->has('key3'));

        $this->assertSame($this->keyedString1, $sut->get('key1'));
        $this->assertSame($this->keyedString2, $sut->get('key2'));
        $this->assertNotInstanceOf(\SimpleSAML\OpenID\ValueAbstracts\KeyedString::class, $sut->get('key3'));

        $this->assertSame(
            ['key1' => $this->keyedString1, 'key2' => $this->keyedString2],
            $sut->getAll(),
        );

        $this->assertSame(
            ['key1' => 'value1', 'key2' => 'value2'],
            $sut->jsonSerialize(),
        );
    }


    public function testCanAdd(): void
    {
        $sut = $this->sut($this->keyedString1);
        $sut->add($this->keyedString2);

        $this->assertTrue($sut->has('key1'));
        $this->assertTrue($sut->has('key2'));
    }
}
