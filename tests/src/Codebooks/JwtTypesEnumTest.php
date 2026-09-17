<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Codebooks;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\ContentTypesEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;

#[CoversClass(JwtTypesEnum::class)]
#[CoversClass(ContentTypesEnum::class)]
final class JwtTypesEnumTest extends TestCase
{
    /**
     * RFC 9068 section 2.1 has the "typ" header carry "at+jwt", the short form of the media type it registers in
     * section 7.1; a resource server accepts either spelling (section 4).
     */
    public function testHasTheAccessTokenTypeRfc9068Defines(): void
    {
        $this->assertSame('at+jwt', JwtTypesEnum::AtJwt->value);
        $this->assertSame('application/at+jwt', ContentTypesEnum::ApplicationAtJwt->value);
        $this->assertSame(
            ContentTypesEnum::ApplicationAtJwt->value,
            'application/' . JwtTypesEnum::AtJwt->value,
        );
    }
}
