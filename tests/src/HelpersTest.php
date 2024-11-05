<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Helpers\Arr;
use SimpleSAML\OpenID\Helpers\Json;
use SimpleSAML\OpenID\Helpers\Url;

#[CoversClass(Helpers::class)]
#[UsesClass(Url::class)]
#[UsesClass(Json::class)]
#[UsesClass(Arr::class)]
class HelpersTest extends TestCase
{
    protected function sut(): Helpers
    {
        return new Helpers();
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(Helpers::class, $this->sut());
    }

    public function testCanBuildTools(): void
    {
        $sut = $this->sut();

        $this->assertInstanceOf(Url::class, $sut->url());
        $this->assertInstanceOf(Json::class, $sut->json());
        $this->assertInstanceOf(Arr::class, $sut->arr());
    }
}
