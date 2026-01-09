<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\ValueAbstracts;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\ValueAbstracts\ClaimBag;

#[CoversClass(ClaimBag::class)]
final class ClaimBagTest extends TestCase
{
    protected array $claims = ['key' => 'value'];


    protected function sut(
        ?array $claims = null,
    ): ClaimBag {
        $claims ??= $this->claims;

        return new ClaimBag($claims);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(ClaimBag::class, $this->sut());
    }


    public function testCanGetClaims(): void
    {
        $sut = $this->sut();
        $this->assertSame(
            $this->claims,
            $sut->getAll(),
        );

        $this->assertTrue($sut->has('key'));
        $this->assertSame('value', $sut->get('key'));
    }


    public function testCanSetClaims(): void
    {
        $sut = $this->sut();
        $sut->set('key', 'newValue');
        $this->assertSame('newValue', $sut->get('key'));
    }


    public function testCanMergeClaims(): void
    {
        $sut = $this->sut();
        $sut->merge(['key' => 'newValue']);
        $this->assertSame(['key' => 'newValue'], $sut->getAll());
    }


    public function testCanRemoveClaims(): void
    {
        $sut = $this->sut();
        $sut->remove('key');
        $this->assertNull($sut->get('key'));
    }


    public function testCanGetAsString(): void
    {
        $sut = $this->sut();
        $this->assertSame('value', $sut->getAsString('key'));
        $this->assertNull($sut->getAsString('non-existing'));
    }


    public function testCanGetAsArray(): void
    {
        $sut = $this->sut();
        $sut->merge(['key' => ['value']]);

        $this->assertSame(['value'], $sut->getAsArray('key'));
        $this->assertNull($sut->getAsArray('non-existing'));
    }


    public function testCanGetAsInt(): void
    {
        $sut = $this->sut();
        $sut->merge(['key' => 1]);
        $this->assertSame(1, $sut->getAsInt('key'));
        $this->assertNull($sut->getAsInt('non-existing'));
    }
}
