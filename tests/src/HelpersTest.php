<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Helpers\Arr;
use SimpleSAML\OpenID\Helpers\Base64Url;
use SimpleSAML\OpenID\Helpers\DateTime;
use SimpleSAML\OpenID\Helpers\Hash;
use SimpleSAML\OpenID\Helpers\Json;
use SimpleSAML\OpenID\Helpers\Random;
use SimpleSAML\OpenID\Helpers\Type;
use SimpleSAML\OpenID\Helpers\Url;

#[CoversClass(Helpers::class)]
#[UsesClass(Url::class)]
#[UsesClass(Json::class)]
#[UsesClass(Arr::class)]
#[UsesClass(Type::class)]
final class HelpersTest extends TestCase
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
        $this->assertInstanceOf(Type::class, $sut->type());
        $this->assertInstanceOf(DateTime::class, $sut->dateTime());
        $this->assertInstanceOf(Base64Url::class, $sut->base64Url());
        $this->assertInstanceOf(Hash::class, $sut->hash());
        $this->assertInstanceOf(Random::class, $sut->random());
    }
}
