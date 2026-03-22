<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\ValueAbstracts;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\ValueAbstracts\RedirectUriBag;
use SimpleSAML\OpenID\ValueAbstracts\UniqueStringBag;

#[CoversClass(RedirectUriBag::class)]
#[CoversClass(UniqueStringBag::class)]
final class RedirectUriBagTest extends TestCase
{
    private string $defaultRedirectUri = 'https://example.com/callback';

    private array $additionalRedirectUris = [
        'https://example.com/extra1',
        'https://example.com/extra2',
    ];


    protected function sut(
        ?string $defaultRedirectUri = null,
        string ...$additionalRedirectUris,
    ): RedirectUriBag {
        return new RedirectUriBag(
            $defaultRedirectUri ?? $this->defaultRedirectUri,
            ...($additionalRedirectUris ?: $this->additionalRedirectUris),
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(RedirectUriBag::class, $this->sut());
    }


    public function testGetDefaultRedirectUriReturnsInjectedString(): void
    {
        $defaultUri = 'https://specific.example.com';
        $sut = $this->sut(defaultRedirectUri: $defaultUri);

        $this->assertSame($defaultUri, $sut->getDefaultRedirectUri());
    }


    public function testGetAllContainsDefaultAndAdditionalUris(): void
    {
        $sut = $this->sut();
        $all = $sut->getAll();

        $this->assertCount(3, $all);
        $this->assertContains($this->defaultRedirectUri, $all);
        foreach ($this->additionalRedirectUris as $uri) {
            $this->assertContains($uri, $all);
        }
    }


    public function testConstructorEnsuresUniqueUris(): void
    {
        // Try to add the default URI again in the additional ones
        $sut = $this->sut(
            $this->defaultRedirectUri,
            $this->defaultRedirectUri,
            $this->additionalRedirectUris[0],
            $this->additionalRedirectUris[0],
        );

        $all = $sut->getAll();

        $this->assertCount(2, $all);
        $this->assertSame([$this->defaultRedirectUri, $this->additionalRedirectUris[0]], array_values($all));
    }


    public function testHasReturnsTrueForExistingUris(): void
    {
        $sut = $this->sut();

        $this->assertTrue($sut->has($this->defaultRedirectUri));
        $this->assertTrue($sut->has($this->additionalRedirectUris[0]));
        $this->assertFalse($sut->has('https://notexists.example.com'));
    }


    public function testAddCanAddMoreUris(): void
    {
        $sut = $this->sut($this->defaultRedirectUri); // Only default injected
        $newUri = 'https://new.example.com';

        $sut->add($newUri);

        $this->assertTrue($sut->has($newUri));
        $this->assertContains($newUri, $sut->getAll());
    }


    public function testJsonSerializationReturnsAllValues(): void
    {
        $sut = $this->sut();
        $expected = array_merge([$this->defaultRedirectUri], $this->additionalRedirectUris);

        $this->assertSame($expected, $sut->jsonSerialize());
        $this->assertSame(json_encode($expected), json_encode($sut));
    }
}
