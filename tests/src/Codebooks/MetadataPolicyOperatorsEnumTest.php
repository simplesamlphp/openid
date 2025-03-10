<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Codebooks;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\MetadataPolicyOperatorsEnum;
use SimpleSAML\OpenID\Codebooks\PhpBasicTypesEnum;
use SimpleSAML\OpenID\Exceptions\MetadataPolicyException;
use stdClass;

#[CoversClass(MetadataPolicyOperatorsEnum::class)]
final class MetadataPolicyOperatorsEnumTest extends TestCase
{
    public function testItReturnsValuesInOrder(): void
    {
        $this->assertSame(
            [
                MetadataPolicyOperatorsEnum::Value->value,
                MetadataPolicyOperatorsEnum::Add->value,
                MetadataPolicyOperatorsEnum::Default->value,
                MetadataPolicyOperatorsEnum::OneOf->value,
                MetadataPolicyOperatorsEnum::SubsetOf->value,
                MetadataPolicyOperatorsEnum::SupersetOf->value,
                MetadataPolicyOperatorsEnum::Essential->value,
            ],
            MetadataPolicyOperatorsEnum::values(),
        );
    }

    public function testItHasProperSupportedOperatorValueTypes(): void
    {
        $this->assertSame(
            [
                PhpBasicTypesEnum::String->value,
                PhpBasicTypesEnum::Integer->value,
                PhpBasicTypesEnum::Double->value,
                PhpBasicTypesEnum::Boolean->value,
                PhpBasicTypesEnum::Object->value,
                PhpBasicTypesEnum::Array->value,
                PhpBasicTypesEnum::Null->value,
            ],
            MetadataPolicyOperatorsEnum::Value->getSupportedOperatorValueTypes(),
        );

        $this->assertSame(
            [
                PhpBasicTypesEnum::Array->value,
            ],
            MetadataPolicyOperatorsEnum::Add->getSupportedOperatorValueTypes(),
        );

        $this->assertSame(
            [
                PhpBasicTypesEnum::Array->value,
            ],
            MetadataPolicyOperatorsEnum::OneOf->getSupportedOperatorValueTypes(),
        );

        $this->assertSame(
            [
                PhpBasicTypesEnum::Array->value,
            ],
            MetadataPolicyOperatorsEnum::SubsetOf->getSupportedOperatorValueTypes(),
        );

        $this->assertSame(
            [
                PhpBasicTypesEnum::Array->value,
            ],
            MetadataPolicyOperatorsEnum::SupersetOf->getSupportedOperatorValueTypes(),
        );

        $this->assertSame(
            [
                PhpBasicTypesEnum::String->value,
                PhpBasicTypesEnum::Integer->value,
                PhpBasicTypesEnum::Double->value,
                PhpBasicTypesEnum::Boolean->value,
                PhpBasicTypesEnum::Object->value,
                PhpBasicTypesEnum::Array->value,
            ],
            MetadataPolicyOperatorsEnum::Default->getSupportedOperatorValueTypes(),
        );

        $this->assertSame(
            [
                PhpBasicTypesEnum::Boolean->value,
            ],
            MetadataPolicyOperatorsEnum::Essential->getSupportedOperatorValueTypes(),
        );
    }

    public function testItHasProperSupportedParameterValueTypes(): void
    {
        $this->assertSame(
            [
                PhpBasicTypesEnum::String->value,
                PhpBasicTypesEnum::Integer->value,
                PhpBasicTypesEnum::Double->value,
                PhpBasicTypesEnum::Boolean->value,
                PhpBasicTypesEnum::Object->value,
                PhpBasicTypesEnum::Array->value,
            ],
            MetadataPolicyOperatorsEnum::Value->getSupportedParameterValueTypes(),
        );

        $this->assertSame(
            [
                PhpBasicTypesEnum::String->value,
                PhpBasicTypesEnum::Integer->value,
                PhpBasicTypesEnum::Double->value,
                PhpBasicTypesEnum::Boolean->value,
                PhpBasicTypesEnum::Object->value,
                PhpBasicTypesEnum::Array->value,
            ],
            MetadataPolicyOperatorsEnum::Default->getSupportedParameterValueTypes(),
        );

        $this->assertSame(
            [
                PhpBasicTypesEnum::String->value,
                PhpBasicTypesEnum::Integer->value,
                PhpBasicTypesEnum::Double->value,
                PhpBasicTypesEnum::Boolean->value,
                PhpBasicTypesEnum::Object->value,
                PhpBasicTypesEnum::Array->value,
            ],
            MetadataPolicyOperatorsEnum::Essential->getSupportedParameterValueTypes(),
        );

        $this->assertSame(
            [
                PhpBasicTypesEnum::Array->value,
            ],
            MetadataPolicyOperatorsEnum::Add->getSupportedParameterValueTypes(),
        );

        $this->assertSame(
            [
                PhpBasicTypesEnum::Array->value,
            ],
            MetadataPolicyOperatorsEnum::SubsetOf->getSupportedParameterValueTypes(),
        );

        $this->assertSame(
            [
                PhpBasicTypesEnum::Array->value,
            ],
            MetadataPolicyOperatorsEnum::SupersetOf->getSupportedParameterValueTypes(),
        );

        $this->assertSame(
            [
                PhpBasicTypesEnum::String->value,
                PhpBasicTypesEnum::Integer->value,
                PhpBasicTypesEnum::Double->value,
                PhpBasicTypesEnum::Object->value,
                PhpBasicTypesEnum::Array->value,
            ],
            MetadataPolicyOperatorsEnum::OneOf->getSupportedParameterValueTypes(),
        );
    }

    public function testItHasProperSupportedOperatorContainedValueTypes(): void
    {
        $this->assertSame(
            [
                PhpBasicTypesEnum::String->value,
                PhpBasicTypesEnum::Integer->value,
                PhpBasicTypesEnum::Double->value,
                PhpBasicTypesEnum::Object->value,
                PhpBasicTypesEnum::Array->value,
            ],
            MetadataPolicyOperatorsEnum::Add->getSupportedOperatorContainedValueTypes(),
        );

        $this->assertSame(
            [
                PhpBasicTypesEnum::String->value,
                PhpBasicTypesEnum::Integer->value,
                PhpBasicTypesEnum::Double->value,
                PhpBasicTypesEnum::Object->value,
                PhpBasicTypesEnum::Array->value,
            ],
            MetadataPolicyOperatorsEnum::OneOf->getSupportedOperatorContainedValueTypes(),
        );

        $this->assertSame(
            [
                PhpBasicTypesEnum::String->value,
                PhpBasicTypesEnum::Integer->value,
                PhpBasicTypesEnum::Double->value,
                PhpBasicTypesEnum::Object->value,
                PhpBasicTypesEnum::Array->value,
            ],
            MetadataPolicyOperatorsEnum::SubsetOf->getSupportedOperatorContainedValueTypes(),
        );

        $this->assertSame(
            [
                PhpBasicTypesEnum::String->value,
                PhpBasicTypesEnum::Integer->value,
                PhpBasicTypesEnum::Double->value,
                PhpBasicTypesEnum::Object->value,
                PhpBasicTypesEnum::Array->value,
            ],
            MetadataPolicyOperatorsEnum::SupersetOf->getSupportedOperatorContainedValueTypes(),
        );
    }

    public function testSupportedOperatorContainedValueTypesThrowsForValue(): void
    {
        $this->expectException(MetadataPolicyException::class);
        MetadataPolicyOperatorsEnum::Value->getSupportedOperatorContainedValueTypes();
    }

    public function testSupportedOperatorContainedValueTypesThrowsForDefault(): void
    {
        $this->expectException(MetadataPolicyException::class);
        MetadataPolicyOperatorsEnum::Default->getSupportedOperatorContainedValueTypes();
    }

    public function testSupportedOperatorContainedValueTypesThrowsForEssential(): void
    {
        $this->expectException(MetadataPolicyException::class);
        MetadataPolicyOperatorsEnum::Essential->getSupportedOperatorContainedValueTypes();
    }

    public function testItHasProperSupportedParameterContainedValueTypes(): void
    {
        $this->assertSame(
            [
                PhpBasicTypesEnum::String->value,
                PhpBasicTypesEnum::Integer->value,
                PhpBasicTypesEnum::Double->value,
                PhpBasicTypesEnum::Object->value,
                PhpBasicTypesEnum::Array->value,
            ],
            MetadataPolicyOperatorsEnum::Add->getSupportedParameterContainedValueTypes(),
        );

        $this->assertSame(
            [
                PhpBasicTypesEnum::String->value,
                PhpBasicTypesEnum::Integer->value,
                PhpBasicTypesEnum::Double->value,
                PhpBasicTypesEnum::Object->value,
                PhpBasicTypesEnum::Array->value,
            ],
            MetadataPolicyOperatorsEnum::SubsetOf->getSupportedParameterContainedValueTypes(),
        );

        $this->assertSame(
            [
                PhpBasicTypesEnum::String->value,
                PhpBasicTypesEnum::Integer->value,
                PhpBasicTypesEnum::Double->value,
                PhpBasicTypesEnum::Object->value,
                PhpBasicTypesEnum::Array->value,
            ],
            MetadataPolicyOperatorsEnum::SupersetOf->getSupportedParameterContainedValueTypes(),
        );
    }

    public function testSupportedParameterContainedValueTypesThrowsForValue(): void
    {
        $this->expectException(MetadataPolicyException::class);
        MetadataPolicyOperatorsEnum::Value->getSupportedParameterContainedValueTypes();
    }

    public function testSupportedParameterContainedValueTypesThrowsForDefault(): void
    {
        $this->expectException(MetadataPolicyException::class);
        MetadataPolicyOperatorsEnum::Default->getSupportedParameterContainedValueTypes();
    }

    public function testSupportedParameterContainedValueTypesThrowsForOneOf(): void
    {
        $this->expectException(MetadataPolicyException::class);
        MetadataPolicyOperatorsEnum::OneOf->getSupportedParameterContainedValueTypes();
    }

    public function testSupportedParameterContainedValueTypesThrowsForEssential(): void
    {
        $this->expectException(MetadataPolicyException::class);
        MetadataPolicyOperatorsEnum::Essential->getSupportedParameterContainedValueTypes();
    }

    public function testCanCheckIsValueSubsetOf(): void
    {
        $this->assertTrue(
            MetadataPolicyOperatorsEnum::Value->isValueSubsetOf(
                'a',
                ['a', 'b'],
            ),
        );

        $this->assertTrue(
            MetadataPolicyOperatorsEnum::Value->isValueSubsetOf(
                ['a'],
                ['a', 'b'],
            ),
        );

        $this->assertFalse(
            MetadataPolicyOperatorsEnum::Value->isValueSubsetOf(
                'c',
                ['a', 'b'],
            ),
        );
    }

    public function testCanCheckIsValueSupersetOf(): void
    {
        $this->assertFalse(
            MetadataPolicyOperatorsEnum::Value->isValueSupersetOf(
                'a',
                ['a', 'b'],
            ),
        );

        $this->assertTrue(
            MetadataPolicyOperatorsEnum::Value->isValueSupersetOf(
                ['a', 'b'],
                ['a'],
            ),
        );

        $this->assertTrue(
            MetadataPolicyOperatorsEnum::Value->isValueSupersetOf(
                'a',
                ['a'],
            ),
        );
    }

    public function testCanCheckIsOperatorValueTypeSupported(): void
    {
        // Assert true.
        $this->assertTrue(MetadataPolicyOperatorsEnum::Value->isOperatorValueTypeSupported('a'));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Value->isOperatorValueTypeSupported(1));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Value->isOperatorValueTypeSupported(1.1));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Value->isOperatorValueTypeSupported([]));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Value->isOperatorValueTypeSupported(true));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Value->isOperatorValueTypeSupported(new stdClass()));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Value->isOperatorValueTypeSupported(null));

        $this->assertTrue(MetadataPolicyOperatorsEnum::Add->isOperatorValueTypeSupported([]));
        $this->assertTrue(MetadataPolicyOperatorsEnum::OneOf->isOperatorValueTypeSupported([]));
        $this->assertTrue(MetadataPolicyOperatorsEnum::SubsetOf->isOperatorValueTypeSupported([]));
        $this->assertTrue(MetadataPolicyOperatorsEnum::SupersetOf->isOperatorValueTypeSupported([]));

        $this->assertTrue(MetadataPolicyOperatorsEnum::Default->isOperatorValueTypeSupported('a'));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Default->isOperatorValueTypeSupported(1));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Default->isOperatorValueTypeSupported(1.1));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Default->isOperatorValueTypeSupported([]));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Default->isOperatorValueTypeSupported(true));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Default->isOperatorValueTypeSupported(new stdClass()));

        $this->assertTrue(MetadataPolicyOperatorsEnum::Essential->isOperatorValueTypeSupported(true));

        // Assert false.
        $this->assertFalse(MetadataPolicyOperatorsEnum::Add->isOperatorValueTypeSupported('a'));
        $this->assertFalse(MetadataPolicyOperatorsEnum::OneOf->isOperatorValueTypeSupported('a'));
        $this->assertFalse(MetadataPolicyOperatorsEnum::SubsetOf->isOperatorValueTypeSupported('a'));
        $this->assertFalse(MetadataPolicyOperatorsEnum::SupersetOf->isOperatorValueTypeSupported('a'));

        $this->assertFalse(MetadataPolicyOperatorsEnum::Default->isOperatorValueTypeSupported(null));

        $this->assertFalse(MetadataPolicyOperatorsEnum::Essential->isOperatorValueTypeSupported('a'));

        // Assert contained values, true.
        $this->assertTrue(MetadataPolicyOperatorsEnum::Add->isOperatorValueTypeSupported(
            ['a', 1, 1.1, [], new stdClass()],
        ));
        $this->assertTrue(MetadataPolicyOperatorsEnum::OneOf->isOperatorValueTypeSupported(
            ['a', 1, 1.1, [], new stdClass()],
        ));
        $this->assertTrue(MetadataPolicyOperatorsEnum::SubsetOf->isOperatorValueTypeSupported(
            ['a', 1, 1.1, [], new stdClass()],
        ));
        $this->assertTrue(MetadataPolicyOperatorsEnum::SupersetOf->isOperatorValueTypeSupported(
            ['a', 1, 1.1, [], new stdClass()],
        ));

        // Assert contained values, false.
        $this->assertFalse(MetadataPolicyOperatorsEnum::Add->isOperatorValueTypeSupported(
            [null, false,],
        ));
        $this->assertFalse(MetadataPolicyOperatorsEnum::OneOf->isOperatorValueTypeSupported(
            [null, false,],
        ));
        $this->assertFalse(MetadataPolicyOperatorsEnum::SubsetOf->isOperatorValueTypeSupported(
            [null, false,],
        ));
        $this->assertFalse(MetadataPolicyOperatorsEnum::SupersetOf->isOperatorValueTypeSupported(
            [null, false,],
        ));
    }

    public function testCanCheckIsParameterValueTypeSupported(): void
    {
        // Assert true.
        $this->assertTrue(MetadataPolicyOperatorsEnum::Value->isParameterValueTypeSupported('a'));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Value->isParameterValueTypeSupported(1));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Value->isParameterValueTypeSupported(1.1));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Value->isParameterValueTypeSupported([]));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Value->isParameterValueTypeSupported(true));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Value->isParameterValueTypeSupported(new stdClass()));

        $this->assertTrue(MetadataPolicyOperatorsEnum::Default->isParameterValueTypeSupported('a'));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Default->isParameterValueTypeSupported(1));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Default->isParameterValueTypeSupported(1.1));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Default->isParameterValueTypeSupported([]));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Default->isParameterValueTypeSupported(true));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Default->isParameterValueTypeSupported(new stdClass()));

        $this->assertTrue(MetadataPolicyOperatorsEnum::Essential->isParameterValueTypeSupported('a'));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Essential->isParameterValueTypeSupported(1));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Essential->isParameterValueTypeSupported(1.1));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Essential->isParameterValueTypeSupported([]));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Essential->isParameterValueTypeSupported(true));
        $this->assertTrue(MetadataPolicyOperatorsEnum::Essential->isParameterValueTypeSupported(new stdClass()));

        $this->assertTrue(MetadataPolicyOperatorsEnum::Add->isParameterValueTypeSupported([]));
        $this->assertTrue(MetadataPolicyOperatorsEnum::SubsetOf->isParameterValueTypeSupported([]));
        $this->assertTrue(MetadataPolicyOperatorsEnum::SupersetOf->isParameterValueTypeSupported([]));

        $this->assertTrue(MetadataPolicyOperatorsEnum::OneOf->isParameterValueTypeSupported('a'));
        $this->assertTrue(MetadataPolicyOperatorsEnum::OneOf->isParameterValueTypeSupported(1));
        $this->assertTrue(MetadataPolicyOperatorsEnum::OneOf->isParameterValueTypeSupported(1.1));
        $this->assertTrue(MetadataPolicyOperatorsEnum::OneOf->isParameterValueTypeSupported([]));
        $this->assertTrue(MetadataPolicyOperatorsEnum::OneOf->isParameterValueTypeSupported(new stdClass()));

        // Assert false.
        $this->assertFalse(MetadataPolicyOperatorsEnum::Default->isParameterValueTypeSupported(null));
        $this->assertFalse(MetadataPolicyOperatorsEnum::Essential->isParameterValueTypeSupported(null));

        $this->assertFalse(MetadataPolicyOperatorsEnum::Add->isParameterValueTypeSupported('a'));
        $this->assertFalse(MetadataPolicyOperatorsEnum::SubsetOf->isParameterValueTypeSupported('a'));
        $this->assertFalse(MetadataPolicyOperatorsEnum::SupersetOf->isParameterValueTypeSupported('a'));

        $this->assertFalse(MetadataPolicyOperatorsEnum::OneOf->isParameterValueTypeSupported(null));

        // Assert contained values, true.
        $this->assertTrue(MetadataPolicyOperatorsEnum::Add->isParameterValueTypeSupported(
            ['a', 1, 1.1, [], new stdClass()],
        ));
        $this->assertTrue(MetadataPolicyOperatorsEnum::SubsetOf->isParameterValueTypeSupported(
            ['a', 1, 1.1, [], new stdClass()],
        ));
        $this->assertTrue(MetadataPolicyOperatorsEnum::SupersetOf->isParameterValueTypeSupported(
            ['a', 1, 1.1, [], new stdClass()],
        ));

        // Assert contained values, false.
        $this->assertFalse(MetadataPolicyOperatorsEnum::Add->isParameterValueTypeSupported(
            [null, false,],
        ));
        $this->assertFalse(MetadataPolicyOperatorsEnum::SubsetOf->isParameterValueTypeSupported(
            [null, false,],
        ));
        $this->assertFalse(MetadataPolicyOperatorsEnum::SupersetOf->isParameterValueTypeSupported(
            [null, false,],
        ));
    }

    public function testCanGetSupportedOperatorCombinations(): void
    {
        $this->assertSame(
            [
                MetadataPolicyOperatorsEnum::Value->value,
                MetadataPolicyOperatorsEnum::Add->value,
                MetadataPolicyOperatorsEnum::Default->value,
                MetadataPolicyOperatorsEnum::OneOf->value,
                MetadataPolicyOperatorsEnum::SubsetOf->value,
                MetadataPolicyOperatorsEnum::SupersetOf->value,
                MetadataPolicyOperatorsEnum::Essential->value,
            ],
            MetadataPolicyOperatorsEnum::Value->getSupportedOperatorCombinations(),
        );

        $this->assertSame(
            [
                MetadataPolicyOperatorsEnum::Add->value,
                MetadataPolicyOperatorsEnum::Value->value,
                MetadataPolicyOperatorsEnum::Default->value,
                MetadataPolicyOperatorsEnum::SubsetOf->value,
                MetadataPolicyOperatorsEnum::SupersetOf->value,
                MetadataPolicyOperatorsEnum::Essential->value,
            ],
            MetadataPolicyOperatorsEnum::Add->getSupportedOperatorCombinations(),
        );

        $this->assertSame(
            [
                MetadataPolicyOperatorsEnum::Default->value,
                MetadataPolicyOperatorsEnum::Value->value,
                MetadataPolicyOperatorsEnum::Add->value,
                MetadataPolicyOperatorsEnum::OneOf->value,
                MetadataPolicyOperatorsEnum::SubsetOf->value,
                MetadataPolicyOperatorsEnum::SupersetOf->value,
                MetadataPolicyOperatorsEnum::Essential->value,
            ],
            MetadataPolicyOperatorsEnum::Default->getSupportedOperatorCombinations(),
        );

        $this->assertSame(
            [
                MetadataPolicyOperatorsEnum::OneOf->value,
                MetadataPolicyOperatorsEnum::Value->value,
                MetadataPolicyOperatorsEnum::Default->value,
                MetadataPolicyOperatorsEnum::Essential->value,
            ],
            MetadataPolicyOperatorsEnum::OneOf->getSupportedOperatorCombinations(),
        );

        $this->assertSame(
            [
                MetadataPolicyOperatorsEnum::SubsetOf->value,
                MetadataPolicyOperatorsEnum::Value->value,
                MetadataPolicyOperatorsEnum::Add->value,
                MetadataPolicyOperatorsEnum::Default->value,
                MetadataPolicyOperatorsEnum::SupersetOf->value,
                MetadataPolicyOperatorsEnum::Essential->value,
            ],
            MetadataPolicyOperatorsEnum::SubsetOf->getSupportedOperatorCombinations(),
        );

        $this->assertSame(
            [
                MetadataPolicyOperatorsEnum::SupersetOf->value,
                MetadataPolicyOperatorsEnum::Value->value,
                MetadataPolicyOperatorsEnum::Add->value,
                MetadataPolicyOperatorsEnum::Default->value,
                MetadataPolicyOperatorsEnum::SubsetOf->value,
                MetadataPolicyOperatorsEnum::Essential->value,
            ],
            MetadataPolicyOperatorsEnum::SupersetOf->getSupportedOperatorCombinations(),
        );

        $this->assertSame(
            [
                MetadataPolicyOperatorsEnum::Essential->value,
                MetadataPolicyOperatorsEnum::Value->value,
                MetadataPolicyOperatorsEnum::Add->value,
                MetadataPolicyOperatorsEnum::Default->value,
                MetadataPolicyOperatorsEnum::OneOf->value,
                MetadataPolicyOperatorsEnum::SubsetOf->value,
                MetadataPolicyOperatorsEnum::SupersetOf->value,
            ],
            MetadataPolicyOperatorsEnum::Essential->getSupportedOperatorCombinations(),
        );
    }

    public function testCanCheckIsOperatorCombinationSupported(): void
    {
        // We have ensured proper operator combinations above, so just take a few cases to check that this method works.
        $this->assertTrue(
            MetadataPolicyOperatorsEnum::Value->isOperatorCombinationSupported([
                MetadataPolicyOperatorsEnum::Value->value,
                MetadataPolicyOperatorsEnum::Essential->value,
            ]),
        );

        $this->assertTrue(
            MetadataPolicyOperatorsEnum::Value->isOperatorCombinationSupported([
                MetadataPolicyOperatorsEnum::Essential->value,
            ]),
        );

        $this->assertFalse(
            MetadataPolicyOperatorsEnum::OneOf->isOperatorCombinationSupported([
                MetadataPolicyOperatorsEnum::Add->value,
            ]),
        );

        $this->assertFalse(
            MetadataPolicyOperatorsEnum::SupersetOf->isOperatorCombinationSupported([
                MetadataPolicyOperatorsEnum::OneOf->value,
            ]),
        );
    }

    public function testCanValidateGeneralParameterOperationRules(): void
    {
        // We have already ensured rules above, so just take a few cases to check that this method works in happy flow.
        MetadataPolicyOperatorsEnum::validateGeneralParameterOperationRules(
            [
                MetadataPolicyOperatorsEnum::Value->value => 'a',
            ],
        );
        $this->addToAssertionCount(1);

        MetadataPolicyOperatorsEnum::validateGeneralParameterOperationRules(
            [
                MetadataPolicyOperatorsEnum::Default->value => 'a',
                MetadataPolicyOperatorsEnum::SubsetOf->value => ['a', 'b'],
            ],
        );
        $this->addToAssertionCount(1);
    }

    public function testValidateGeneralParameterOperationRulesThrowsForNonSupportedOperatorValue(): void
    {
        $this->expectException(MetadataPolicyException::class);
        $this->expectExceptionMessage('Unsupported operator value type');

        MetadataPolicyOperatorsEnum::validateGeneralParameterOperationRules(
            [
                MetadataPolicyOperatorsEnum::SubsetOf->value => 'a',
            ],
        );
    }

    public function testValidateGeneralParameterOperationRulesThrowsForNonSupportedOperatorCombinations(): void
    {
        $this->expectException(MetadataPolicyException::class);
        $this->expectExceptionMessage('Unsupported operator combination');

        MetadataPolicyOperatorsEnum::validateGeneralParameterOperationRules(
            [
                MetadataPolicyOperatorsEnum::OneOf->value => ['a'],
                MetadataPolicyOperatorsEnum::SubsetOf->value => ['a', 'b'],
            ],
        );
    }

    public function testValidateSpecificParameterOperationRulesForAddAndSubsetOf(): void
    {
        // If add is combined with subset_of, the values of add MUST be a subset of the values of subset_of.
        MetadataPolicyOperatorsEnum::validateSpecificParameterOperationRules(
            [
                MetadataPolicyOperatorsEnum::Add->value => ['a'],
                MetadataPolicyOperatorsEnum::SubsetOf->value => ['a', 'b'],
            ],
        );
        $this->addToAssertionCount(1);

        $this->expectException(MetadataPolicyException::class);
        $this->expectExceptionMessage('subset');
        MetadataPolicyOperatorsEnum::validateSpecificParameterOperationRules(
            [
                MetadataPolicyOperatorsEnum::Add->value => ['c'],
                MetadataPolicyOperatorsEnum::SubsetOf->value => ['a', 'b'],
            ],
        );
    }

    public function testValidateSpecificParameterOperationRulesForSubsetOfAndSupersetOf(): void
    {
        // If subset_of is combined with superset_of, the values of subset_of MUST be a superset of the values of
        // superset_of.
        MetadataPolicyOperatorsEnum::validateSpecificParameterOperationRules(
            [
                MetadataPolicyOperatorsEnum::SubsetOf->value => ['a', 'b'],
                MetadataPolicyOperatorsEnum::SupersetOf->value => ['a'],
            ],
        );
        $this->addToAssertionCount(1);

        $this->expectException(MetadataPolicyException::class);
        MetadataPolicyOperatorsEnum::validateSpecificParameterOperationRules(
            [
                MetadataPolicyOperatorsEnum::SubsetOf->value => ['c'],
                MetadataPolicyOperatorsEnum::SupersetOf->value => ['a'],
            ],
        );
    }

    public function testCanValidateMetadataParameterValueType(): void
    {
        // We have already ensured rules, so just take a few cases to check that this method works.
        MetadataPolicyOperatorsEnum::Value->validateMetadataParameterValueType('a', 'sample');
        MetadataPolicyOperatorsEnum::SubsetOf->validateMetadataParameterValueType(['a'], 'sample');
        $this->addToAssertionCount(2);

        $this->expectException(MetadataPolicyException::class);
        $this->expectExceptionMessage('Unsupported parameter sample value type');
        MetadataPolicyOperatorsEnum::SubsetOf->validateMetadataParameterValueType('a', 'sample');
    }
}
