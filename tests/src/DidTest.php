<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID;

use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Did;
use SimpleSAML\OpenID\Did\DidKeyJwkResolver;
use SimpleSAML\OpenID\Helpers;

#[\PHPUnit\Framework\Attributes\CoversClass(Did::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Did\DidKeyJwkResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Helpers::class)]
final class DidTest extends TestCase
{
    protected function sut(): Did
    {
        return new Did();
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Did::class, $this->sut());
    }


    public function testCanBuildTools(): void
    {
        $this->assertInstanceOf(
            DidKeyJwkResolver::class,
            $this->sut()->didKeyResolver(),
        );

        $this->assertInstanceOf(
            Helpers::class,
            $this->sut()->helpers(),
        );
    }
}
