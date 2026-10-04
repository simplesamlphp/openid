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


    /**
     * @return \Iterator<string, array{string, string}>
     */
    public static function httpTargetUriProvider(): \Iterator
    {
        yield 'already normal' => ['https://server.example.com/token', 'https://server.example.com/token'];
        yield 'http' => ['http://server.example.com/token', 'http://server.example.com/token'];
        yield 'upper-case scheme' => ['HTTPS://server.example.com/token', 'https://server.example.com/token'];
        yield 'upper-case host' => ['https://Server.EXAMPLE.com/token', 'https://server.example.com/token'];
        yield 'path case is kept' => ['https://server.example.com/Token', 'https://server.example.com/Token'];
        yield 'default https port' => ['https://server.example.com:443/token', 'https://server.example.com/token'];
        yield 'default http port' => ['http://server.example.com:80/token', 'http://server.example.com/token'];
        yield 'empty port' => ['https://server.example.com:/token', 'https://server.example.com/token'];
        yield 'other port' => ['https://server.example.com:8443/token', 'https://server.example.com:8443/token'];
        yield 'http port on https' => ['https://server.example.com:80/token', 'https://server.example.com:80/token'];
        yield 'https port on http' => ['http://server.example.com:443/token', 'http://server.example.com:443/token'];
        yield 'port with leading zeros' => ['https://server.example.com:0443/', 'https://server.example.com/'];
        yield 'other port with leading zeros' => [
            'https://server.example.com:08443/',
            'https://server.example.com:8443/',
        ];
        yield 'empty path' => ['https://server.example.com', 'https://server.example.com/'];
        yield 'empty path and port' => ['https://server.example.com:443', 'https://server.example.com/'];
        yield 'trailing slash is kept' => ['https://server.example.com/token/', 'https://server.example.com/token/'];
        yield 'query' => ['https://server.example.com/token?a=b', 'https://server.example.com/token'];
        yield 'fragment' => ['https://server.example.com/token#f', 'https://server.example.com/token'];
        yield 'query and fragment' => ['https://server.example.com/token?a=b#f', 'https://server.example.com/token'];
        yield 'empty query' => ['https://server.example.com/token?', 'https://server.example.com/token'];
        yield 'query without a path' => ['https://server.example.com?a=b', 'https://server.example.com/'];
        yield 'fragment with a slash' => ['https://server.example.com/a#/b', 'https://server.example.com/a'];
        yield 'fragment with a question mark' => ['https://server.example.com/a#?b', 'https://server.example.com/a'];
        yield 'query with a hash' => ['https://server.example.com/a?b#c', 'https://server.example.com/a'];
        yield 'query is not read' => ['https://server.example.com/a?b=%zz c', 'https://server.example.com/a'];
        yield 'fragment is not read' => ["https://server.example.com/a#\u{e4}", 'https://server.example.com/a'];
        yield 'query right after the authority' => ['https://server.example.com:443?a', 'https://server.example.com/'];
        yield 'dot segment' => ['https://server.example.com/./token', 'https://server.example.com/token'];
        yield 'double dot segment' => ['https://server.example.com/a/../token', 'https://server.example.com/token'];
        yield 'double dot above the root' => [
            'https://server.example.com/../../token',
            'https://server.example.com/token',
        ];
        yield 'trailing dot segment' => ['https://server.example.com/a/.', 'https://server.example.com/a/'];
        yield 'trailing double dot segment' => ['https://server.example.com/a/b/..', 'https://server.example.com/a/'];
        yield 'the example of RFC 3986 section 5.2.4' => [
            'https://server.example.com/a/b/c/./../../g',
            'https://server.example.com/a/g',
        ];
        yield 'dots that are not a segment' => [
            'https://server.example.com/a/..b/.c',
            'https://server.example.com/a/..b/.c',
        ];
        yield 'empty segments are kept' => ['https://server.example.com//token', 'https://server.example.com//token'];
        yield 'double dot after an empty segment' => [
            'https://server.example.com/a//../b',
            'https://server.example.com/a/b',
        ];
        yield 'dot segment before a trailing slash' => [
            'https://server.example.com/a/./',
            'https://server.example.com/a/',
        ];
        yield 'leading dot segment' => ['https://server.example.com/./a', 'https://server.example.com/a'];
        yield 'double dot before a trailing slash' => [
            'https://server.example.com/a/../',
            'https://server.example.com/',
        ];
        yield 'only a double dot' => ['https://server.example.com/..', 'https://server.example.com/'];
        yield 'only a dot' => ['https://server.example.com/.', 'https://server.example.com/'];
        yield 'encoded tilde' => ['https://server.example.com/%7euser', 'https://server.example.com/~user'];
        yield 'encoded letter' => ['https://server.example.com/%74oken', 'https://server.example.com/token'];
        yield 'encoded digit, hyphen, period and underscore' => [
            'https://server.example.com/%31%2D%2E%5F',
            'https://server.example.com/1-._',
        ];
        yield 'encoded dot segment' => [
            'https://server.example.com/a/%2E%2E/token',
            'https://server.example.com/token',
        ];
        yield 'encoded slash stays encoded' => ['https://server.example.com/a%2fb', 'https://server.example.com/a%2Fb'];
        yield 'encoded space stays encoded' => ['https://server.example.com/a%20b', 'https://server.example.com/a%20b'];
        yield 'encoded percent stays encoded' => [
            'https://server.example.com/a%25b',
            'https://server.example.com/a%25b',
        ];
        yield 'encoded non-ASCII stays encoded' => [
            'https://server.example.com/%c3%a4',
            'https://server.example.com/%C3%A4',
        ];
        yield 'encoded letter in the host' => ['https://%53erver.example.com/', 'https://server.example.com/'];
        yield 'IPv4 address' => ['https://192.0.2.1:443/token', 'https://192.0.2.1/token'];
        yield 'IPv6 literal' => ['https://[2001:DB8::1]/token', 'https://[2001:db8::1]/token'];
        yield 'IPv6 literal with a port' => ['https://[2001:db8::1]:8443/token', 'https://[2001:db8::1]:8443/token'];
        yield 'IPv6 literal with the default port' => ['https://[2001:db8::1]:443/', 'https://[2001:db8::1]/'];
    }


    #[DataProvider('httpTargetUriProvider')]
    public function testCanNormalizeAnHttpTargetUri(string $uri, string $normalized): void
    {
        $this->assertSame($normalized, $this->sut()->normalizeHttpTargetUri($uri));
    }


    /**
     * @return \Iterator<string, array{string}>
     */
    public static function notAnHttpTargetUriProvider(): \Iterator
    {
        yield 'empty' => [''];
        yield 'relative reference' => ['/token'];
        yield 'network-path reference' => ['//server.example.com/token'];
        yield 'other scheme' => ['ftp://server.example.com/token'];
        yield 'URN' => ['urn:example:token'];
        yield 'no authority' => ['https:/token'];
        yield 'no authority and no slash' => ['https:server.example.com/token'];
        yield 'empty authority' => ['https:///token'];
        yield 'port without host' => ['https://:443/token'];
        yield 'empty IPv6 literal' => ['https://[]/token'];
        yield 'user' => ['https://user@server.example.com/token'];
        yield 'user and password' => ['https://user:secret@server.example.com/token'];
        yield 'empty userinfo' => ['https://@server.example.com/token'];
        yield 'port not a number' => ['https://server.example.com:https/token'];
        yield 'port out of range' => ['https://server.example.com:65536/token'];
        yield 'port too long' => ['https://server.example.com:000000443/token'];
        yield 'two ports' => ['https://server.example.com:443:443/token'];
        yield 'unclosed IPv6 literal' => ['https://[2001:db8::1/token'];
        yield 'bracket inside a host name' => ['https://server[1].example.com/token'];
        yield 'space' => ['https://server.example.com/a b'];
        yield 'trailing newline' => ["https://server.example.com/token\n"];
        yield 'NUL' => ["https://server.example.com/token\0"];
        yield 'non-ASCII' => ["https://server.example.com/\u{e4}"];
        yield 'backslash' => ['https://server.example.com\\@other.example.com/token'];
        yield 'brace' => ['https://server.example.com/{token}'];
        yield 'malformed percent-encoding' => ['https://server.example.com/%zz'];
        yield 'truncated percent-encoding' => ['https://server.example.com/token%2'];
        yield 'lone percent' => ['https://server.example.com/100%'];
        yield 'brackets in the path' => ['https://server.example.com/[x]/../token'];
        yield 'a bracket in a later segment' => ['https://server.example.com/a/b]'];
        yield 'a host in brackets which is no address' => ['https://[not-an-ip]/token'];
        yield 'an IPv4 address in brackets' => ['https://[192.0.2.1]/token'];
        yield 'an IPvFuture literal' => ['https://[v1.x]/token'];
        yield 'an IPv6 literal with a zone' => ['https://[fe80::1%25eth0]/token'];
    }


    /**
     * @return \Iterator<string, array{string, string}>
     */
    public static function optionsTargetUriProvider(): \Iterator
    {
        yield 'no path' => ['https://server.example.com', 'https://server.example.com'];
        yield 'no path, default port and query' => ['https://SERVER.example.com:443?a=b', 'https://server.example.com'];
        yield 'the root' => ['https://server.example.com/', 'https://server.example.com/'];
        yield 'dot segments up to the root' => ['https://server.example.com/a/..', 'https://server.example.com/'];
        yield 'a path' => ['https://server.example.com/token', 'https://server.example.com/token'];
    }


    /**
     * RFC 9110 section 4.2.3: an empty path is "/" only "When not being used as the target of an OPTIONS
     * request".
     */
    #[DataProvider('optionsTargetUriProvider')]
    public function testKeepsAnEmptyPathForAnOptionsRequest(string $uri, string $normalized): void
    {
        $this->assertSame($normalized, $this->sut()->normalizeHttpTargetUri($uri, true));
    }


    #[DataProvider('notAnHttpTargetUriProvider')]
    public function testRefusesToNormalizeWhatIsNotAnHttpTargetUri(string $uri): void
    {
        $this->assertNull($this->sut()->normalizeHttpTargetUri($uri));
    }


    /**
     * A million characters of dot segments, normalized in linear time, is a few milliseconds; with the copying
     * of RFC 3986 section 5.2.4's two buffers, which is quadratic, it was seconds. The bound is far above the
     * one and far below the other, so it fails only if the cost goes quadratic again.
     */
    public function testNormalizesManyDotSegmentsInLinearTime(): void
    {
        $uri = 'https://server.example.com/' . str_repeat('a/../', 200000) . 'token';

        $start = hrtime(true);
        $normalized = $this->sut()->normalizeHttpTargetUri($uri);
        $seconds = (hrtime(true) - $start) / 1e9;

        $this->assertSame('https://server.example.com/token', $normalized);
        $this->assertLessThan(1.5, $seconds);
    }


    public function testCanNormalizeALongTargetUri(): void
    {
        $this->assertSame(
            'https://server.example.com/' . str_repeat('a', 100000),
            $this->sut()->normalizeHttpTargetUri('https://server.example.com/' . str_repeat('a', 100000)),
        );
        $this->assertSame(
            'https://server.example.com/' . str_repeat('a/', 10000),
            $this->sut()->normalizeHttpTargetUri('https://server.example.com/' . str_repeat('a/', 10000)),
        );
        $this->assertSame(
            'https://server.example.com/',
            $this->sut()->normalizeHttpTargetUri('https://server.example.com/' . str_repeat('a/../', 10000)),
        );
    }
}
