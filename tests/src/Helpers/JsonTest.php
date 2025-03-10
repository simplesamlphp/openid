<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Helpers\Json;

#[CoversClass(Json::class)]
final class JsonTest extends TestCase
{
    protected function sut(): Json
    {
        return new Json();
    }

    public function testEncodeDecode(): void
    {
        $arr = ['a' => 'b'];
        $json = $this->sut()->encode($arr);

        $this->assertSame('{"a":"b"}', $json);
        $this->assertSame($arr, $this->sut()->decode($json));
    }
}
