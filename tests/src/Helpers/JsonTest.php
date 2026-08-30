<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Helpers;

use JsonException;
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


    public function testDecodeRefusesDocumentNestedDeeperThanAllowed(): void
    {
        $json = str_repeat('[', 5) . '1' . str_repeat(']', 5);

        $this->assertSame([[[[[1]]]]], $this->sut()->decode($json));

        $this->expectException(JsonException::class);

        $this->sut()->decode($json, 3);
    }


    public function testDecodeRaisesAnUnusableDepthToTheLowestJsonDecodeAccepts(): void
    {
        // json_decode refuses a depth below one outright, so a caller passing one still gets a decode.
        $this->assertSame('a', $this->sut()->decode('"a"', 0));
    }


    public function testDecodeLowersADepthBeyondWhatJsonDecodeAccepts(): void
    {
        // Past its maximum json_decode raises a ValueError rather than the JsonException callers catch, so
        // a depth chosen badly has to come back as a decode rather than as an error nobody is expecting.
        $this->assertSame(['a' => 'b'], $this->sut()->decode('{"a":"b"}', PHP_INT_MAX));
        $this->assertSame(['a' => 'b'], $this->sut()->decode('{"a":"b"}', Json::MAX_DEPTH));
    }
}
