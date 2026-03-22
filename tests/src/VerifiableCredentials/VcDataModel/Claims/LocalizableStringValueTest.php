<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\ValueAbstracts\LanguageValueObject;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\LocalizableStringValue;

#[CoversClass(LocalizableStringValue::class)]
#[UsesClass(LanguageValueObject::class)]
final class LocalizableStringValueTest extends TestCase
{
    public function testInstantiationWithLanguageValueObject(): void
    {
        $languageValueObject = new LanguageValueObject('Test Value', 'en');
        $localizableString = new LocalizableStringValue($languageValueObject, 'name');

        $this->assertSame($languageValueObject, $localizableString->getValue());
        $this->assertSame('name', $localizableString->getName());
    }


    public function testJsonSerializeReturnsLanguageValueObjectData(): void
    {
        $languageValueObject = new LanguageValueObject('Test Value', 'en', 'ltr');
        $localizableString = new LocalizableStringValue($languageValueObject, 'description');

        $serialized = $localizableString->jsonSerialize();

        $this->assertArrayHasKey('@value', $serialized);
        $this->assertArrayHasKey('@language', $serialized);
        $this->assertArrayHasKey('@direction', $serialized);
        $this->assertSame('Test Value', $serialized['@value']);
        $this->assertSame('en', $serialized['@language']);
        $this->assertSame('ltr', $serialized['@direction']);
    }
}
