<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Codebooks;

use Iterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\UriPattern;

#[CoversClass(UriPattern::class)]
final class UriPatternTest extends TestCase
{
    #[DataProvider('uriDataProvider')]
    public function testMatchesOnlyWhatItShould(UriPattern $uriPattern, string $value, bool $expected): void
    {
        $this->assertSame($expected, preg_match($uriPattern->value, $value) === 1);
    }


    public static function uriDataProvider(): Iterator
    {
        yield 'https URL' => [UriPattern::HttpNoQueryNoFragment, 'https://example.com/issuer', true];
        yield 'http URL' => [UriPattern::HttpNoQueryNoFragment, 'http://example.com', true];
        yield 'URL with a query' => [UriPattern::HttpNoQueryNoFragment, 'https://example.com?a=b', false];
        yield 'URL with a fragment' => [UriPattern::HttpNoQueryNoFragment, 'https://example.com#f', false];
        yield 'not a URL' => [UriPattern::HttpNoQueryNoFragment, 'example.com', false];

        yield 'did URI' => [UriPattern::Uri, 'did:web:example.org', true];
        yield 'urn URI' => [UriPattern::Uri, 'urn:ietf:params:oauth:grant-type:device_code', true];
        yield 'bare word' => [UriPattern::Uri, 'a', false];

        // Both patterns end in $D. Without the D, PCRE matches $ immediately before a trailing newline, so
        // these passed validation and carried the newline onward - into a fetch, a log line, and the `id` of
        // an issued credential, depending on which caller was validating.
        yield 'URL with a trailing line feed' => [
            UriPattern::HttpNoQueryNoFragment,
            "https://example.com/issuer\n",
            false,
        ];
        yield 'URI with a trailing line feed' => [UriPattern::Uri, "did:web:example.org\n", false];
        yield 'URL with a trailing carriage return' => [
            UriPattern::HttpNoQueryNoFragment,
            "https://example.com/issuer\r",
            false,
        ];
        yield 'URI with a trailing carriage return' => [UriPattern::Uri, "did:web:example.org\r", false];
    }
}
