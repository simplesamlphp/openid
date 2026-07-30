<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\TokenStatusList;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Exceptions\InvalidValueException;
use SimpleSAML\OpenID\Exceptions\StatusListException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\TokenStatusList\StatusReference;

#[CoversClass(StatusReference::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Type::class)]
final class StatusReferenceTest extends TestCase
{
    protected string $uri = 'https://example.com/statuslists/1';

    protected int $idx = 0;


    protected function sut(
        ?string $uri = null,
        ?int $idx = null,
    ): StatusReference {
        $uri ??= $this->uri;
        $idx ??= $this->idx;

        return new StatusReference($uri, $idx, new Helpers());
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(StatusReference::class, $this->sut());
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut($this->uri, 42);

        $this->assertSame($this->uri, $sut->getUri());
        $this->assertSame(42, $sut->getIdx());
    }


    public function testCanJsonSerialize(): void
    {
        $this->assertSame(
            ['idx' => 0, 'uri' => 'https://example.com/statuslists/1'],
            $this->sut()->jsonSerialize(),
        );
    }


    /**
     * The URI is carried through untouched, since the Status List Token's `sub` claim has to equal it exactly.
     */
    public function testUriIsNotNormalised(): void
    {
        $uri = 'https://example.com:443/statuslists/1/?a=b#c';

        $this->assertSame($uri, $this->sut($uri)->getUri());
    }


    public function testThrowsForNegativeIndex(): void
    {
        $this->expectException(StatusListException::class);
        $this->expectExceptionMessage('non-negative integer');

        $this->sut(null, -1);
    }


    /**
     * @return \Iterator<string, array{string}>
     */
    public static function invalidUriProvider(): \Iterator
    {
        yield 'empty' => [''];
        yield 'no scheme' => ['example.com/statuslists/1'];
        yield 'plain text' => ['not a uri'];
    }


    #[DataProvider('invalidUriProvider')]
    public function testThrowsForNonUri(string $uri): void
    {
        $this->expectException(InvalidValueException::class);

        $this->sut($uri);
    }


    /**
     * The specification does not require an HTTPS URI, only one conforming to RFC 3986.
     */
    public function testAcceptsNonHttpUri(): void
    {
        $this->assertSame('urn:example:statuslist:1', $this->sut('urn:example:statuslist:1')->getUri());
    }
}
