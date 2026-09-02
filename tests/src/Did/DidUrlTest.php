<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Did;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Did\DidUrl;
use SimpleSAML\OpenID\Exceptions\DidException;

#[CoversClass(DidUrl::class)]
final class DidUrlTest extends TestCase
{
    protected function sut(string $value): DidUrl
    {
        return new DidUrl($value);
    }


    public function testCanParseBareDid(): void
    {
        $sut = $this->sut('did:web:example.org');

        $this->assertSame('did:web:example.org', $sut->getValue());
        $this->assertSame('did:web:example.org', $sut->getDid());
        $this->assertSame('web', $sut->getMethod());
        $this->assertSame('example.org', $sut->getMethodSpecificId());
        $this->assertNull($sut->getPath());
        $this->assertNull($sut->getQuery());
        $this->assertNull($sut->getFragment());
        $this->assertFalse($sut->hasFragment());
        $this->assertTrue($sut->isBareDid());
    }


    public function testCanParseFragment(): void
    {
        $sut = $this->sut('did:web:example.org#key-1');

        $this->assertSame('did:web:example.org#key-1', $sut->getValue());
        $this->assertSame('did:web:example.org', $sut->getDid());
        $this->assertSame('key-1', $sut->getFragment());
        $this->assertTrue($sut->hasFragment());
        $this->assertFalse($sut->isBareDid());
    }


    public function testCanParsePathQueryAndFragment(): void
    {
        $sut = $this->sut('did:web:example.org:oidc/path/to?service=files&x=1#key-1');

        $this->assertSame('web', $sut->getMethod());
        $this->assertSame('example.org:oidc', $sut->getMethodSpecificId());
        $this->assertSame('did:web:example.org:oidc', $sut->getDid());
        $this->assertSame('/path/to', $sut->getPath());
        $this->assertSame('service=files&x=1', $sut->getQuery());
        $this->assertSame('key-1', $sut->getFragment());
    }


    public function testCanParseEncodedPortInMethodSpecificId(): void
    {
        $sut = $this->sut('did:web:example.org%3A8443#key-1');

        $this->assertSame('example.org%3A8443', $sut->getMethodSpecificId());
        $this->assertSame('did:web:example.org%3A8443', $sut->getDid());
    }


    public function testCanParseDidKey(): void
    {
        $sut = $this->sut('did:key:z6MkhaXgBZDvotDkL5257faiztiGiC2QtKLGpbnnEGta2doK');

        $this->assertSame('key', $sut->getMethod());
        $this->assertSame('z6MkhaXgBZDvotDkL5257faiztiGiC2QtKLGpbnnEGta2doK', $sut->getMethodSpecificId());
    }


    public function testCanParseDidJwkWithBase64UrlCharacters(): void
    {
        $sut = $this->sut('did:jwk:eyJrdHkiOiJFQyJ9-_.abc#0');

        $this->assertSame('jwk', $sut->getMethod());
        $this->assertSame('eyJrdHkiOiJFQyJ9-_.abc', $sut->getMethodSpecificId());
        $this->assertSame('0', $sut->getFragment());
    }


    public function testEmptyFragmentNamesNothing(): void
    {
        $sut = $this->sut('did:web:example.org#');

        $this->assertSame('', $sut->getFragment());
        $this->assertFalse($sut->hasFragment());
        $this->assertFalse($sut->isBareDid());
    }


    #[DataProvider('relativeDidUrlDataProvider')]
    public function testRejectsRelativeDidUrl(string $value): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('Relative DID URLs are not supported');

        $this->sut($value);
    }


    public static function relativeDidUrlDataProvider(): \Iterator
    {
        yield 'fragment only' => ['#key-1'];
        yield 'path only' => ['/path/to'];
        yield 'query only' => ['?service=files'];
    }


    public function testRejectsEmptyValue(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('DID URL must not be empty.');

        $this->sut('');
    }


    #[DataProvider('malformedDidDataProvider')]
    public function testRejectsMalformedDid(string $value): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('DID URL does not contain a syntactically valid DID.');

        $this->sut($value);
    }


    public static function malformedDidDataProvider(): \Iterator
    {
        yield 'not a DID at all' => ['https://example.org/did.json'];
        yield 'scheme only' => ['did:'];
        yield 'no method specific id' => ['did:web'];
        yield 'empty method specific id' => ['did:web:'];
        yield 'uppercase method name' => ['did:WEB:example.org'];
        yield 'method specific id ending in a colon' => ['did:web:example.org:'];
        yield 'space in method specific id' => ['did:web:exam ple.org'];
        yield 'malformed percent encoding' => ['did:web:example.org%zz'];
        yield 'truncated percent encoding' => ['did:web:example.org%3'];
        yield 'illegal character in method specific id' => ['did:web:example<org'];
        yield 'uppercase scheme' => ['DID:web:example.org'];
    }


    #[DataProvider('malformedComponentDataProvider')]
    public function testRejectsMalformedComponent(string $value, string $componentName): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage(sprintf('DID URL %s component is not syntactically valid.', $componentName));

        $this->sut($value);
    }


    public static function malformedComponentDataProvider(): \Iterator
    {
        yield 'illegal character in fragment' => ['did:web:example.org#key<1', 'fragment'];
        yield 'nested fragment delimiter' => ['did:web:example.org#key#1', 'fragment'];
        yield 'illegal character in query' => ['did:web:example.org?a=<b#k', 'query'];
        yield 'illegal character in path' => ['did:web:example.org/pa<th', 'path'];
        yield 'malformed percent encoding in fragment' => ['did:web:example.org#key%zz', 'fragment'];
    }


    #[DataProvider('fragmentEncodingDataProvider')]
    public function testEncodesAFragment(string $value, string $expected): void
    {
        $this->assertSame($expected, DidUrl::encodeFragment($value));
    }


    public static function fragmentEncodingDataProvider(): \Iterator
    {
        yield 'an ordinary key id is left alone' => ['ec-vci-signing-key-01', 'ec-vci-signing-key-01'];
        yield 'a JWK thumbprint is left alone' => [
            'NzbLsXh8uDCcd-6MNwXF4W_7noWXFZAfHkxZsRGC9Xs',
            'NzbLsXh8uDCcd-6MNwXF4W_7noWXFZAfHkxZsRGC9Xs',
        ];
        yield 'sub-delims and the two extra pchars are left alone' => ['a!$&\'()*+,;=:@b', 'a!$&\'()*+,;=:@b'];
        // The module's own sample configuration offers one of these as a key id.
        yield 'a did:jwk key id keeps its colons and loses its fragment delimiter' => [
            'did:jwk:eyJrdHkiOiJFQyJ9#0',
            'did:jwk:eyJrdHkiOiJFQyJ9%230',
        ];
        yield 'a percent is encoded, which is what keeps the mapping injective' => ['a%41', 'a%2541'];
        yield 'a space is encoded' => ['key 1', 'key%201'];
        yield 'a slash and a question mark are encoded' => ['a/b?c', 'a%2Fb%3Fc'];
        yield 'a newline is encoded' => ["key\n", 'key%0A'];
        yield 'non ascii is encoded byte by byte' => ['kľúč', 'k%C4%BE%C3%BA%C4%8D'];
        yield 'an empty value stays empty' => ['', ''];
    }


    /**
     * Encoding is reversible, so a fragment read out of a published document names the key it was minted
     * from, and two distinct key ids can not collide into one fragment.
     */
    #[DataProvider('fragmentEncodingDataProvider')]
    public function testAnEncodedFragmentDecodesBackToWhatItWasMintedFrom(string $value): void
    {
        $this->assertSame($value, rawurldecode(DidUrl::encodeFragment($value)));
    }


    public function testAnEncodedFragmentIsAcceptedAsOne(): void
    {
        $keyId = 'did:jwk:eyJrdHkiOiJFQyJ9#0';

        $sut = $this->sut('did:web:example.org#' . DidUrl::encodeFragment($keyId));

        $this->assertTrue($sut->hasFragment());
        $this->assertSame('did:jwk:eyJrdHkiOiJFQyJ9%230', $sut->getFragment());
        $this->assertSame('did:web:example.org', $sut->getDid());
    }
}
