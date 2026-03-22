<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\ValueAbstracts;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Exceptions\InvalidValueException;
use SimpleSAML\OpenID\ValueAbstracts\LanguageValueObject;

#[CoversClass(LanguageValueObject::class)]
final class LanguageValueObjectTest extends TestCase
{
    /**
     * Test successful object creation with only value.
     */
    public function testSuccessfulCreationOnlyValue(): void
    {
        $value = 'Hello';
        $obj = new LanguageValueObject($value);

        $this->assertSame($value, $obj->getValue());
        $this->assertNull($obj->getLanguage());
        $this->assertNull($obj->getDirection());
        $this->assertSame(['@value' => $value], $obj->jsonSerialize());
    }


    /**
     * Test successful object creation with value and language.
     */
    public function testSuccessfulCreationWithValueAndLanguage(): void
    {
        $value = 'Hello';
        $language = 'en';
        $obj = new LanguageValueObject($value, $language);

        $this->assertSame($value, $obj->getValue());
        $this->assertSame($language, $obj->getLanguage());
        $this->assertNull($obj->getDirection());
        $this->assertSame(
            [
                '@value' => $value,
                '@language' => $language,
            ],
            $obj->jsonSerialize(),
        );
    }


    /**
     * Test successful object creation with value, language, and direction.
     */
    public function testSuccessfulCreationFull(): void
    {
        $value = 'Hello';
        $language = 'en';
        $direction = 'ltr';
        $obj = new LanguageValueObject($value, $language, $direction);

        $this->assertSame($value, $obj->getValue());
        $this->assertSame($language, $obj->getLanguage());
        $this->assertSame($direction, $obj->getDirection());
        $this->assertSame(
            [
                '@value' => $value,
                '@language' => $language,
                '@direction' => $direction,
            ],
            $obj->jsonSerialize(),
        );
    }


    /**
     * Test successful object creation with value and direction (language is null).
     */
    public function testSuccessfulCreationValueAndDirection(): void
    {
        $value = 'Hello';
        $direction = 'rtl';
        $obj = new LanguageValueObject($value, null, $direction);

        $this->assertSame($value, $obj->getValue());
        $this->assertNull($obj->getLanguage());
        $this->assertSame($direction, $obj->getDirection());
        $this->assertSame(
            [
                '@value' => $value,
                '@direction' => $direction,
            ],
            $obj->jsonSerialize(),
        );
    }


    /**
     * Test exception when value is '0'.
     */
    public function testExceptionWhenValueIsZero(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Language value object @value must be a non-empty string.');
        new LanguageValueObject('0');
    }


    /**
     * Test exception when language is '0'.
     */
    public function testExceptionWhenLanguageIsZero(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Language value object @language must be a non-empty string.');
        new LanguageValueObject('Hello', '0');
    }


    /**
     * Test exception when direction is invalid.
     */
    public function testExceptionWhenDirectionIsInvalid(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Language value object @direction must be "ltr" or "rtl".');
        new LanguageValueObject('Hello', 'en', 'invalid');
    }
}
