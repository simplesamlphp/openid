<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\ValueAbstracts;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\ValueAbstracts\KeyedString;

#[CoversClass(KeyedString::class)]
final class KeyedStringTest extends TestCase
{
    private string $key;

    private string $value;


    protected function setUp(): void
    {
        $this->key = 'sample-key';
        $this->value = 'sample-value';
    }


    protected function sut(
        ?string $key = null,
        ?string $value = null,
    ): KeyedString {
        $key ??= $this->key;
        $value ??= $this->value;

        return new KeyedString($key, $value);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(KeyedString::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut();

        $this->assertSame($this->key, $sut->getKey());
        $this->assertSame($this->value, $sut->getValue());
        $this->assertSame([$this->key => $this->value], $sut->jsonSerialize());
    }
}
