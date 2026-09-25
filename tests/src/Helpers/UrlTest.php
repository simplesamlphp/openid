<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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


    /**
     * @return \Iterator<string, array{string, bool}>
     */
    public static function issuerIdentifierProvider(): \Iterator
    {
        yield 'host only' => ['https://example.com', true];
        yield 'trailing slash' => ['https://example.com/', true];
        yield 'path' => ['https://example.com/tenant/one', true];
        yield 'port' => ['https://example.com:8443/path', true];
        yield 'IPv4 address' => ['https://192.0.2.1/', true];
        yield 'IPv6 literal' => ['https://[2001:db8::1]/', true];
        yield 'upper-case scheme' => ['HTTPS://example.com', true];
        yield 'percent-encoded path' => ['https://example.com/a%20b', true];
        yield 'at sign in the path' => ['https://example.com/@tenant', true];
        yield 'http' => ['http://example.com', false];
        yield 'other scheme' => ['urn:example:issuer', false];
        yield 'no authority' => ['https:example.com', false];
        yield 'empty authority' => ['https:///example.com', false];
        yield 'port without host' => ['https://:443/', false];
        yield 'empty' => ['', false];
        yield 'query' => ['https://example.com/?tenant=one', false];
        yield 'empty query' => ['https://example.com?', false];
        yield 'fragment' => ['https://example.com/#one', false];
        yield 'empty fragment' => ['https://example.com#', false];
        yield 'user' => ['https://user@example.com', false];
        yield 'user and password' => ['https://user:secret@example.com/', false];
        yield 'empty userinfo' => ['https://@example.com', false];
        yield 'backslash before at sign' => ['https://example.com\\@example.org', false];
        yield 'backslash in host' => ['https://example.org\\.example.com', false];
        yield 'trailing newline' => ["https://example.com/\n", false];
        yield 'space in path' => ['https://example.com/a b', false];
        yield 'NUL' => ["https://example.com/\0", false];
        yield 'non-ASCII' => ["https://example.com/\u{e4}", false];
        yield 'malformed percent-encoding' => ['https://example.com/%zz', false];
        yield 'brace' => ['https://example.com/{id}', false];
        yield 'underscore in host' => ['https://exa_mple.com', false];
        yield 'unclosed IPv6 literal' => ['https://[2001:db8::1/', false];
        yield 'port out of range' => ['https://example.com:99999', false];
        yield 'port not a number' => ['https://example.com:https', false];
        yield 'port with a trailing bracket' => ['https://example.com:443]/', false];
        yield 'port with a sub-delim' => ['https://example.com:443$/', false];
        yield 'signed port' => ['https://example.com:+443/', false];
        yield 'two ports' => ['https://example.com:443:443/', false];
        yield 'bracket inside a host name' => ['https://exa[mple].com/', false];
        yield 'IPv6 literal with a port' => ['https://[2001:db8::1]:8443/', true];
        yield 'IPv4-mapped IPv6 literal' => ['https://[::ffff:192.0.2.1]/', true];
        yield 'IPvFuture literal' => ['https://[v1.x]/', false];
    }


    #[DataProvider('issuerIdentifierProvider')]
    public function testCanTellAnIssuerIdentifier(string $value, bool $isIssuerIdentifier): void
    {
        $this->assertSame($isIssuerIdentifier, $this->sut()->isIssuerIdentifier($value));
    }


    /**
     * A path long enough to exhaust the PCRE JIT stack under a backtracking pattern.
     */
    public function testCanTellAnIssuerIdentifierWithALongPath(): void
    {
        $this->assertTrue($this->sut()->isIssuerIdentifier('https://example.com/' . str_repeat('a', 100000)));
        $this->assertTrue($this->sut()->isIssuerIdentifier('https://example.com/' . str_repeat('a/', 10000)));
        $this->assertTrue($this->sut()->isIssuerIdentifier('https://example.com/' . str_repeat('%41', 10000)));
        $this->assertFalse($this->sut()->isIssuerIdentifier('https://example.com/' . str_repeat('a', 100000) . '#'));
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


    public function testCanAddMultiValueParams(): void
    {
        $url = 'https://example.com/';

        $this->assertSame(
            'https://example.com/',
            $this->sut()->withMultiValueParams($url, []),
        );

        $this->assertSame(
            'https://example.com/?a=b&a=c',
            $this->sut()->withMultiValueParams($url, ['a' => ['b', 'c']]),
        );

        $this->assertSame(
            'https://example.com/?a=b&c=d',
            $this->sut()->withMultiValueParams($url, ['a' => 'b', 'c' => 'd']),
        );

        $url = 'https://example.com/?x=y';
        $this->assertSame(
            'https://example.com/?x=y&a=b&a=c',
            $this->sut()->withMultiValueParams($url, ['a' => ['b', 'c']]),
        );
    }
}
