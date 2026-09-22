<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Helpers\MediaType;

#[CoversClass(MediaType::class)]
final class MediaTypeTest extends TestCase
{
    protected function sut(): MediaType
    {
        return new MediaType();
    }


    /**
     * @return \Iterator<string, array{string,string}>
     */
    public static function jwtTypeProvider(): \Iterator
    {
        yield 'short form gets the application/ prefix' => ['at+jwt', 'application/at+jwt'];
        yield 'type and subtype are case insensitive' => ['AT+JWT', 'application/at+jwt'];
        yield 'the full media type is left as is' => ['application/at+jwt', 'application/at+jwt'];
        yield 'the full media type is case folded' => ['Application/AT+JWT', 'application/at+jwt'];
        yield 'another top-level type is not touched' => ['text/plain', 'text/plain'];
        yield 'the JOSE type of RFC 7515' => ['JOSE', 'application/jose'];
        yield 'parameter names are case folded, values are not' => ['at+jwt;V=A', 'application/at+jwt;v=A'];
        yield 'several parameters' => ['JWT;V=A;Charset=X', 'application/jwt;v=A;charset=X'];
        yield 'a quoted value keeps its case and its separators' => [
            'jwt;Name="A;B=C"',
            'application/jwt;name="A;B=C"',
        ];
        yield 'an escaped quote does not end the quoted value' => [
            'jwt;Name="A\\"B;C=D";Other=E',
            'application/jwt;name="A\\"B;C=D";other=E',
        ];
        yield 'a parameter with a slash in its value is not prefixed' => [
            'example;part="1/2"',
            'example;part="1/2"',
        ];
        yield 'empty names no media type' => ['', ''];
    }


    #[DataProvider('jwtTypeProvider')]
    public function testCanNormalizeJwtType(string $typ, string $expected): void
    {
        $this->assertSame($expected, $this->sut()->normalizeJwtType($typ));
    }


    /**
     * @return \Iterator<string, array{string,string,bool}>
     */
    public static function jwtTypeEqualityProvider(): \Iterator
    {
        yield 'same value' => ['at+jwt', 'at+jwt', true];
        yield 'short and full form' => ['at+jwt', 'application/at+jwt', true];
        yield 'differing case' => ['at+JWT', 'AT+jwt', true];
        yield 'full form, differing case' => ['application/at+jwt', 'Application/AT+JWT', true];
        yield 'different subtype' => ['at+jwt', 'jwt', false];
        yield 'different top-level type' => ['at+jwt', 'text/at+jwt', false];
        yield 'a parameter makes it another media type' => ['at+jwt', 'at+jwt;v=1', false];
        yield 'parameter names are case insensitive' => ['jwt;V=1', 'jwt;v=1', true];
        yield 'parameter values keep their case' => ['at+jwt;v=a', 'at+jwt;v=A', false];
        yield 'whitespace is not ignored' => ['at+jwt', ' at+jwt', false];
        yield 'empty is not the application type' => ['', 'application/', false];
    }


    #[DataProvider('jwtTypeEqualityProvider')]
    public function testCanCompareJwtTypes(string $typ, string $otherTyp, bool $expected): void
    {
        $this->assertSame($expected, $this->sut()->areJwtTypesEqual($typ, $otherTyp));
        $this->assertSame($expected, $this->sut()->areJwtTypesEqual($otherTyp, $typ));
    }
}
