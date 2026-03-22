<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\ValueAbstracts;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\ValueAbstracts\ScopeBag;

#[CoversClass(ScopeBag::class)]
final class ScopeBagTest extends TestCase
{
    /**
     * Test the default constructor.
     */
    public function testDefaultConstructor(): void
    {
        $bag = new ScopeBag();
        $this->assertSame(['openid'], $bag->getAll());
    }


    /**
     * Test construction with multiple values.
     */
    public function testConstructorWithMultipleValues(): void
    {
        $bag = new ScopeBag('openid', 'profile', 'email');
        $this->assertSame(['openid', 'profile', 'email'], $bag->getAll());
    }


    /**
     * Test toString with a single value.
     */
    public function testToStringWithSingleValue(): void
    {
        $bag = new ScopeBag('openid');
        $this->assertSame('openid', $bag->toString());
    }


    /**
     * Test toString with multiple values.
     */
    public function testToStringWithMultipleValues(): void
    {
        $bag = new ScopeBag('openid', 'profile', 'email');
        $this->assertSame('openid profile email', $bag->toString());
    }


    /**
     * Test __toString method.
     */
    public function testMagicToString(): void
    {
        $bag = new ScopeBag('openid', 'profile');
        $this->assertSame('openid profile', (string) $bag);
    }


    /**
     * Test that unique values are maintained (inheritance from UniqueStringBag).
     */
    public function testUniqueValues(): void
    {
        $bag = new ScopeBag('openid', 'openid', 'profile');
        $this->assertSame(['openid', 'profile'], $bag->getAll());
        $this->assertSame('openid profile', $bag->toString());
    }
}
