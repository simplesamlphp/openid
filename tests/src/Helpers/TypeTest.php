<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Exceptions\InvalidValueException;
use SimpleSAML\OpenID\Helpers\Type;

#[CoversClass(Type::class)]
class TypeTest extends TestCase
{
    protected function sut(): Type
    {
        return new Type();
    }

    public function testCanEnsure(): void
    {
        $this->assertSame('a', $this->sut()->ensureString('a'));
        $this->assertSame('1', $this->sut()->ensureString(1));
        $this->assertSame('0', $this->sut()->ensureString(0));
        $this->assertSame('1', $this->sut()->ensureString(true));
        $this->assertSame('', $this->sut()->ensureString(false));
    }

    public function testEnsureThrowsForNonScalar(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Unsafe');

        $this->sut()->ensureString(null);
    }

    public function testEnsureThrowsForNonStringableObject(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Unsafe');

        $this->sut()->ensureString(new \stdClass());
    }

    public function testCanEnsureNonEmpty(): void
    {
        $this->assertSame('a', $this->sut()->ensureNonEmptyString('a'));
        $this->assertSame('1', $this->sut()->ensureNonEmptyString(true));
    }

    public function testEnsureNonEmptyThrowsForEmptyString(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Empty');

        $this->sut()->ensureNonEmptyString('');
    }

    public function testEnsureNonEmptyThrowsForZeroString(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Empty');

        $this->sut()->ensureNonEmptyString('0');
    }

    public function testEnsureNonEmptyThrowsForNull(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Unsafe');

        $this->sut()->ensureNonEmptyString(null);
    }

    public function testEnsureNonEmptyThrowsForZero(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Empty');

        $this->sut()->ensureNonEmptyString(0);
    }

    public function testEnsureNonEmptyThrowsForFalse(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Empty');

        $this->sut()->ensureNonEmptyString(false);
    }

    public function testCanEnsureAll(): void
    {
        $this->assertSame(
            ['0', '1', '2'],
            $this->sut()->ensureStrings([0, 1, 2]),
        );
    }

    public function testCanEnsureAllNonEmpty(): void
    {
        $this->assertSame(
            ['1', '2'],
            $this->sut()->ensureNonEmptyStrings([1, 2]),
        );
    }
}
