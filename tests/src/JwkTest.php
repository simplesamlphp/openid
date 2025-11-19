<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID;

use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Jwk;
use SimpleSAML\OpenID\Jwk\Factories\JwkDecoratorFactory;

#[\PHPUnit\Framework\Attributes\CoversClass(Jwk::class)]
final class JwkTest extends TestCase
{
    protected function sut(): Jwk
    {
        return new Jwk();
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Jwk::class, $this->sut());
    }


    public function testCanBuildTools(): void
    {
        $this->assertInstanceOf(
            JwkDecoratorFactory::class,
            $this->sut()->jwkDecoratorFactory(),
        );
    }
}
