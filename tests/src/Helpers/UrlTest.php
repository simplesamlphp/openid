<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Helpers\Url;

#[CoversClass(Url::class)]
final class UrlTest extends TestCase
{
    protected function sut(): Url
    {
        return new Url();
    }


    public function testCanCheckUrl(): void
    {
        $this->assertTrue($this->sut()->isValid('https://example.com/'));
        $this->assertFalse($this->sut()->isValid('abc123'));
    }


    public function testCanAddParams(): void
    {
        $url = 'https://example.com/';

        $this->assertSame(
            'https://example.com/',
            $this->sut()->withParams($url, []),
        );
        $this->assertSame(
            'https://example.com/?a=b',
            $this->sut()->withParams($url, ['a' => 'b']),
        );

        $url = 'https://example.com/?a=b';
        $this->assertSame(
            'https://example.com/?a=b&c=d',
            $this->sut()->withParams($url, ['c' => 'd']),
        );
    }
}
