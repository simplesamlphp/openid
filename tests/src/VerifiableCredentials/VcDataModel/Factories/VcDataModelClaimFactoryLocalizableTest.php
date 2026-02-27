<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Exceptions\InvalidValueException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Helpers\Arr;
use SimpleSAML\OpenID\ValueAbstracts\LanguageValueObject;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\LocalizableStringValue;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims\LocalizableStringValueBag;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Factories\VcDataModelClaimFactory;

#[CoversClass(VcDataModelClaimFactory::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(ClaimFactory::class)]
#[UsesClass(LanguageValueObject::class)]
#[UsesClass(Arr::class)]
#[UsesClass(LocalizableStringValue::class)]
#[UsesClass(LocalizableStringValueBag::class)]
final class VcDataModelClaimFactoryLocalizableTest extends TestCase
{
    private VcDataModelClaimFactory $factory;


    protected function setUp(): void
    {
        $this->factory = new VcDataModelClaimFactory(
            new Helpers(),
            new ClaimFactory(new Helpers()),
        );
    }


    public function testBuildLanguageValueObjectFromSimpleArray(): void
    {
        $data = ['@value' => 'Test', '@language' => 'en'];
        $lvo = $this->factory->buildLanguageValueObject($data);

        $this->assertSame('Test', $lvo->getValue());
        $this->assertSame('en', $lvo->getLanguage());
        $this->assertNull($lvo->getDirection());
    }


    public function testBuildLanguageValueObjectWithDirection(): void
    {
        $data = ['@value' => 'مثال', '@language' => 'ar', '@direction' => 'rtl'];
        $lvo = $this->factory->buildLanguageValueObject($data);

        $this->assertSame('مثال', $lvo->getValue());
        $this->assertSame('ar', $lvo->getLanguage());
        $this->assertSame('rtl', $lvo->getDirection());
    }


    public function testBuildLanguageValueObjectThrowsOnMissingValue(): void
    {
        $data = ['@language' => 'en'];
        $this->expectException(InvalidValueException::class);
        $this->factory->buildLanguageValueObject($data);
    }


    public function testBuildLanguageValueObjectThrowsOnEmptyValue(): void
    {
        $data = ['@value' => '', '@language' => 'en'];
        $this->expectException(InvalidValueException::class);
        $this->factory->buildLanguageValueObject($data);
    }


    public function testBuildLanguageValueObjectThrowsOnInvalidDirection(): void
    {
        $data = ['@value' => 'Test', '@direction' => 'invalid'];
        $this->expectException(InvalidValueException::class);
        $this->factory->buildLanguageValueObject($data);
    }


    public function testBuildLocalizableStringValueFromString(): void
    {
        $result = $this->factory->buildLocalizableStringValueBag('Test String', 'name');

        $this->assertInstanceOf(LocalizableStringValueBag::class, $result);
        $this->assertSame('Test String', $result->getFirstValueOrFail()->getValue()->getValue());
        $this->assertNull($result->getFirstValueOrFail()->getValue()->getLanguage());
    }


    public function testBuildLocalizableStringValueFromSingleLanguageObject(): void
    {
        $data = ['@value' => 'Test', '@language' => 'en'];
        $result = $this->factory->buildLocalizableStringValueBag($data, 'description');

        $this->assertInstanceOf(LocalizableStringValueBag::class, $result);
        $this->assertSame('Test', $result->getFirstValueOrFail()->getValue()->getValue());
        $this->assertSame('en', $result->getFirstValueOrFail()->getValue()->getLanguage());
    }


    public function testBuildLocalizableStringValueFromMultipleLanguageObjects(): void
    {
        $data = [
            ['@value' => 'English', '@language' => 'en'],
            ['@value' => 'Français', '@language' => 'fr'],
            ['@value' => 'العربية', '@language' => 'ar', '@direction' => 'rtl'],
        ];
        $result = $this->factory->buildLocalizableStringValueBag($data, 'name');

        $this->assertInstanceOf(LocalizableStringValueBag::class, $result);

        $values = $result->getValue();
        $this->assertCount(3, $values);
        $this->assertSame('English', $values[0]->getValue()->getValue());
        $this->assertSame('en', $values[0]->getValue()->getLanguage());
        $this->assertSame('Français', $values[1]->getValue()->getValue());
        $this->assertSame('العربية', $values[2]->getValue()->getValue());
        $this->assertSame('rtl', $values[2]->getValue()->getDirection());
    }


    public function testBuildLocalizableStringValueThrowsOnEmptyString(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->factory->buildLocalizableStringValueBag('', 'name');
    }


    public function testBuildLocalizableStringValueThrowsOnInvalidType(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->factory->buildLocalizableStringValueBag(12345, 'name');
    }


    public function testBuildLocalizableStringValueThrowsOnInvalidArrayItem(): void
    {
        $data = [
            ['@value' => 'English', '@language' => 'en'],
            'invalid string in array',
        ];
        $this->expectException(InvalidValueException::class);
        $this->factory->buildLocalizableStringValueBag($data, 'name');
    }


    public function testBuildLocalizableStringValueThrowsOnEmptyArray(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->factory->buildLocalizableStringValueBag([], 'name');
    }
}
