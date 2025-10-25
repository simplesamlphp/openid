<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Helpers\Hash;

#[CoversClass(Hash::class)]
final class HashTest extends TestCase
{
    public function testFor(): void
    {
        $hash = new Hash();
        $algorithm = 'sha256';
        $data = 'test data';
        $binary = false;
        $options = [];

        $expected = hash($algorithm, $data, $binary, $options);
        $actual = $hash->for($algorithm, $data, $binary, $options);

        $this->assertSame($expected, $actual);
    }
}
