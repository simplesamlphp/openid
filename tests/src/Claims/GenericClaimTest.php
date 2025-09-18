<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Claims;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Claims\GenericClaim;

#[CoversClass(GenericClaim::class)]
final class GenericClaimTest extends TestCase
{
    protected string $valueSample;

    protected string $nameSample;


    protected function setUp(): void
    {
        $this->valueSample = 'sample-value';
        $this->nameSample = 'sample-name';
    }


    protected function sut(
        mixed $value = null,
        ?string $name = null,
    ): GenericClaim {
        $value ??= $this->valueSample;
        $name ??= $this->nameSample;

        return new GenericClaim(
            $value,
            $name,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(GenericClaim::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $this->assertSame($this->valueSample, $this->sut()->getValue());
        $this->assertSame($this->nameSample, $this->sut()->getName());

        $this->assertSame(
            [$this->nameSample => $this->valueSample],
            $this->sut()->jsonSerialize(),
        );
    }
}
