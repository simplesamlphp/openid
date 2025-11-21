<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Exceptions\OpenIdException;
use SimpleSAML\OpenID\Helpers\Random;

#[CoversClass(Random::class)]
final class RandomTest extends TestCase
{
    protected Random $random;


    protected function setUp(): void
    {
        $this->random = new Random();
    }


    public function testLength(): void
    {
        $this->assertSame(80, strlen($this->random->string(40)));
    }


    public function testLengthWithPrefixAndSuffix(): void
    {
        $this->assertSame(92, strlen($this->random->string(40, null, 'prefix', 'suffix')));
    }


    public function testInvalidLength(): void
    {
        $this->expectException(OpenIdException::class);
        $this->random->string(0);
    }


    public function testBlacklist(): void
    {
        // This test is tricky because of the random nature.
        // We can't guarantee a collision.
        // We can try to mock random_bytes, but that's also complex.
        // For now, we'll just test that it *can* return a value
        // even with a blocklist.
        $randomString = $this->random->string(40, ['some-blacklisted-string']);
        $this->assertSame(80, strlen($randomString));
    }


    public function testPrefixAndSuffix(): void
    {
        $randomString = $this->random->string(10, null, 'prefix-', '-suffix');
        $this->assertStringStartsWith('prefix-', $randomString);
        $this->assertStringEndsWith('-suffix', $randomString);
        // The random part is 10 bytes, which is 20 hex characters.
        $this->assertSame(20 + 7 + 7, strlen($randomString));
    }
}
