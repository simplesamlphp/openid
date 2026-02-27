<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Claims;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\ValueAbstracts\LanguageValueObject;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\LocalizableStringValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\LocalizableStringValueBag;

#[CoversClass(LocalizableStringValueBag::class)]
#[UsesClass(LanguageValueObject::class)]
#[UsesClass(LocalizableStringValue::class)]
final class LocalizableStringValueBagTest extends TestCase
{
    public function testInstantiationWithMultipleValues(): void
    {
        $value1 = new LocalizableStringValue(new LanguageValueObject('English', 'en'), 'name');
        $value2 = new LocalizableStringValue(new LanguageValueObject('French', 'fr'), 'name');

        $bag = new LocalizableStringValueBag($value1, $value2);

        $this->assertCount(2, $bag->getValue());
        $this->assertSame('name', $bag->getName());
    }


    public function testGetValueByLanguage(): void
    {
        $value1 = new LocalizableStringValue(new LanguageValueObject('English', 'en'), 'name');
        $value2 = new LocalizableStringValue(new LanguageValueObject('Français', 'fr'), 'name');
        $value3 = new LocalizableStringValue(new LanguageValueObject('العربية', 'ar'), 'name');

        $bag = new LocalizableStringValueBag($value1, $value2, $value3);

        $frValue = $bag->getValueByLanguage('fr');
        $this->assertInstanceOf(
            LocalizableStringValue::class,
            $frValue,
        );
        $this->assertSame('Français', $frValue->getValue()->getValue());

        $arValue = $bag->getValueByLanguage('ar');
        $this->assertInstanceOf(LocalizableStringValue::class, $arValue);
        $this->assertSame('العربية', $arValue->getValue()->getValue());
    }


    public function testGetValueByLanguageReturnsNullForMissing(): void
    {
        $value1 = new LocalizableStringValue(new LanguageValueObject('English', 'en'), 'name');
        $bag = new LocalizableStringValueBag($value1);

        $missing = $bag->getValueByLanguage('de');
        $this->assertNotInstanceOf(LocalizableStringValue::class, $missing);
    }


    public function testGetValueByLanguageNullReturnsFirst(): void
    {
        $value1 = new LocalizableStringValue(new LanguageValueObject('English', 'en'), 'name');
        $value2 = new LocalizableStringValue(new LanguageValueObject('Français', 'fr'), 'name');

        $bag = new LocalizableStringValueBag($value1, $value2);

        $first = $bag->getValueByLanguage();
        $this->assertSame($value1, $first);
    }


    public function testJsonSerializeReturnsArrayOfLanguageObjects(): void
    {
        $value1 = new LocalizableStringValue(new LanguageValueObject('English', 'en'), 'name');
        $value2 = new LocalizableStringValue(new LanguageValueObject('Français', 'fr'), 'name');

        $bag = new LocalizableStringValueBag($value1, $value2);
        $serialized = $bag->jsonSerialize();

        $this->assertIsArray($serialized);
        $this->assertCount(2, $serialized);
        $this->assertSame('English', $serialized[0]['@value']);
        $this->assertSame('en', $serialized[0]['@language']);
        $this->assertSame('Français', $serialized[1]['@value']);
        $this->assertSame('fr', $serialized[1]['@language']);
    }
}
