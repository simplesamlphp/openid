<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Codebooks;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\HashAlgorithmsEnum;

#[CoversClass(HashAlgorithmsEnum::class)]
final class HashAlgorithmsEnumTest extends TestCase
{
    public function testIanaNameIsValue(): void
    {
        $this->assertSame(HashAlgorithmsEnum::SHA3_256->ianaName(), HashAlgorithmsEnum::SHA3_256->value);
        $this->assertSame(HashAlgorithmsEnum::SHA3_384->ianaName(), HashAlgorithmsEnum::SHA3_384->value);
        $this->assertSame(HashAlgorithmsEnum::SHA3_512->ianaName(), HashAlgorithmsEnum::SHA3_512->value);
    }


    public function testPhpName(): void
    {
        $this->assertSame('sha3-256', HashAlgorithmsEnum::SHA3_256->phpName());
        $this->assertSame('sha3-384', HashAlgorithmsEnum::SHA3_384->phpName());
        $this->assertSame('sha3-512', HashAlgorithmsEnum::SHA3_512->phpName());
    }
}
