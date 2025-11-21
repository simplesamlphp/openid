<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Helpers\Base64Url;

#[CoversClass(Base64Url::class)]
final class Base64UrlTest extends TestCase
{
    private function sut(): Base64Url
    {
        return new Base64Url();
    }


    public function testEncodeRemovesPaddingAndUsesUrlSafeAlphabet(): void
    {
        $this->assertSame('', $this->sut()->encode(''));
        $this->assertSame('Zg', $this->sut()->encode('f'));        // Zg==
        $this->assertSame('Zm8', $this->sut()->encode('fo'));      // Zm8=
        $this->assertSame('Zm9v', $this->sut()->encode('foo'));    // Zm9v
        $this->assertSame('Zm9vYg', $this->sut()->encode('foob')); // Zm9vYg== -> trimmed
        $this->assertSame('Zm9vYmE', $this->sut()->encode('fooba'));
        $this->assertSame('Zm9vYmFy', $this->sut()->encode('foobar'));

        // Ensure + becomes - and / becomes _
        $this->assertSame('-w', $this->sut()->encode("\xFB"));   // +w== -> -w
        $this->assertSame('_w', $this->sut()->encode("\xFF"));   // /w== -> _w
        $this->assertSame('-_8', $this->sut()->encode("\xFB\xFF")); // +/8= -> -_8
    }


    public function testDecodeReversesEncodeAndHandlesPadding(): void
    {
        // Simple known mappings
        $this->assertSame('', $this->sut()->decode(''));
        $this->assertSame('f', $this->sut()->decode('Zg'));
        $this->assertSame('fo', $this->sut()->decode('Zm8'));
        $this->assertSame('foo', $this->sut()->decode('Zm9v'));
        $this->assertSame('foob', $this->sut()->decode('Zm9vYg'));
        $this->assertSame('fooba', $this->sut()->decode('Zm9vYmE'));
        $this->assertSame('foobar', $this->sut()->decode('Zm9vYmFy'));

        // Characters that require translation
        $this->assertSame("\xFB", $this->sut()->decode('-w'));
        $this->assertSame("\xFF", $this->sut()->decode('_w'));
        $this->assertSame("\xFB\xFF", $this->sut()->decode('-_8'));
    }


    public function testRoundTripVariousInputs(): void
    {
        $inputs = [
            '',
            'a', 'ab', 'abc', 'abcd', 'abcde',
            "\x00\x01\x02\x03", // binary
            'The quick brown fox jumps over the lazy dog',
            '✓ àéîöū',             // Latin-1/UTF-8 mix
            'Привет мир',          // Cyrillic
            '你好，世界',             // Chinese
            'emoji: 🚀🔥',
        ];

        foreach ($inputs as $input) {
            $encoded = $this->sut()->encode($input);
            $decoded = $this->sut()->decode($encoded);
            $this->assertSame($input, $decoded, 'Round-trip failed for input: ' . $input);
        }
    }


    public function testDecodeThrowsOnInvalidInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Base64URL encoded data');
        $this->sut()->decode('abc*'); // '*' is not a valid Base64URL character
    }
}
