# Implementation Complete: Name and Description Properties for VcSdJwt

**Date**: February 27, 2026
**Status**: ✅ Complete
**Test Coverage**: 19 new tests, all passing

## Summary

Successfully implemented support for optional `name` and `description` properties in SD-JWT Verifiable Credentials per VC Data Model v2.0 specification (§4.6). The implementation supports:

- Simple string values
- Single language-tagged values with @language and @direction
- Multiple language variants with language selection helpers
- Proper error handling and validation

## Deliverables

### New Files Created (5)

1. **src/VerifiableCredentials/VcDataModel/Claims/LocalizableStringValue.php**
   - Implements `ClaimInterface`
   - Wraps single `LanguageValueObject`
   - JSON serializable

2. **src/VerifiableCredentials/VcDataModel/Claims/LocalizableStringValueBag.php**
   - Implements `ClaimInterface`
   - Container for multiple `LocalizableStringValue` objects
   - Provides language-based filtering via `getValueByLanguage()`

3. **tests/src/VerifiableCredentials/VcDataModel/Claims/LocalizableStringValueTest.php**
   - 2 test cases covering instantiation and JSON serialization

4. **tests/src/VerifiableCredentials/VcDataModel/Claims/LocalizableStringValueBagTest.php**
   - 5 test cases covering bag functionality and language filtering

5. **tests/src/VerifiableCredentials/VcDataModel/Factories/VcDataModelClaimFactoryLocalizableTest.php**
   - 12 test cases covering factory builder validation and edge cases

### Files Modified (2)

1. **src/VerifiableCredentials/VcDataModel/Factories/VcDataModelClaimFactory.php**
   - Added 2 new builder methods
   - Added necessary imports
   - 118 lines added

2. **src/VerifiableCredentials/VcDataModel2/VcSdJwt.php**
   - Added 2 protected properties
   - Added 2 getter methods
   - Added necessary imports
   - 68 lines added

### Existing Components Reused

- `LanguageValueObject` (existing in ValueAbstracts)
- `ClaimInterface` (existing interface)
- `ClaimsEnum` (Name and Description cases already present)
- `VcDataModelClaimFactory` (existing factory pattern)

## Public API

### New Methods in VcSdJwt

```php
/**
 * Get the credential name property
 * @return null|LocalizableStringValue|LocalizableStringValueBag
 * @throws VcDataModelException
 * @throws InvalidValueException
 */
public function getVcName(): null|LocalizableStringValue|LocalizableStringValueBag

/**
 * Get the credential description property
 * @return null|LocalizableStringValue|LocalizableStringValueBag
 * @throws VcDataModelException
 * @throws InvalidValueException
 */
public function getVcDescription(): null|LocalizableStringValue|LocalizableStringValueBag
```

### New Methods in VcDataModelClaimFactory

```php
/**
 * Build a LanguageValueObject from language value object data
 */
public function buildLanguageValueObject(array $data): LanguageValueObject

/**
 * Build a LocalizableStringValue or LocalizableStringValueBag
 */
public function buildLocalizableStringValue(mixed $data, string $claimName): LocalizableStringValue|LocalizableStringValueBag
```

### New Methods in LocalizableStringValueBag

```php
/**
 * Get a value by language tag
 */
public function getValueByLanguage(?string $language = null): ?LocalizableStringValue
```

## Test Coverage

### Total Tests: 19
- LocalizableStringValue: 2 tests
- LocalizableStringValueBag: 5 tests
- Factory methods: 12 tests

### Test Results
```
All 19 tests PASSED
Total assertions: 48
No failures or errors
```

## Key Features

### 1. Multiple Input Formats
- ✅ Simple strings: `"Example"`
- ✅ Single language objects: `{"@value": "...", "@language": "en"}`
- ✅ Multiple language objects: `[{...}, {...}]`

### 2. Return Types
- `LocalizableStringValue`: Single value (string or single language variant)
- `LocalizableStringValueBag`: Multiple language variants
- `null`: Property not present

### 3. Language Selection
```php
$desc = $vcSdJwt->getVcDescription();
if ($desc instanceof LocalizableStringValueBag) {
    $en = $desc->getValueByLanguage('en');  // Get English version
    $fr = $desc->getValueByLanguage('fr');  // Get French version
}
```

### 4. Direction Support
- Proper handling of `@direction` ("ltr" or "rtl")
- Essential for right-to-left languages (Arabic, Hebrew, etc.)

### 5. Comprehensive Validation
- Non-empty value requirement
- Language tag format expectations
- Direction value validation
- Array consistency checks
- Clear error messages

## Backward Compatibility

✅ **100% Backward Compatible**
- No modifications to existing methods
- Only additions of new optional properties
- All existing tests pass
- No breaking changes to public API

## Error Handling

All invalid inputs throw descriptive exceptions:

```php
InvalidValueException::class  // For language value object issues
VcDataModelException::class   // For claim parsing issues
```

## Code Quality

✅ All files pass PHP syntax validation
✅ Follows existing code patterns
✅ Comprehensive documentation
✅ Type hints throughout
✅ Proper error messages

## Usage Examples

### Example 1: Simple Name
```php
$vcSdJwt = ...; // SD-JWT VC
$name = $vcSdJwt->getVcName();

if ($name instanceof LocalizableStringValue) {
    echo $name->getValue()->getValue(); // "Example University Degree"
}
```

### Example 2: Multilingual Description
```php
$desc = $vcSdJwt->getVcDescription();

if ($desc instanceof LocalizableStringValueBag) {
    foreach ($desc->getValue() as $variant) {
        $lang = $variant->getValue()->getLanguage();
        $text = $variant->getValue()->getValue();
        printf("%s: %s\n", $lang, $text);
    }
}
```

### Example 3: With Language Filtering
```php
$name = $vcSdJwt->getVcName();

if ($name instanceof LocalizableStringValueBag) {
    $french = $name->getValueByLanguage('fr');
    if ($french !== null) {
        echo $french->getValue()->getValue(); // "Exemple de Diplôme Universitaire"
    }
}
```

### Example 4: Handling Absence
```php
$name = $vcSdJwt->getVcName();

if ($name === null) {
    echo "Credential has no name property";
}
```

## JSON Serialization

The implementation properly serializes to JSON-LD format:

```php
$vcSdJwt->getVcName(); // LocalizableStringValueBag

// Serializes to:
[
  { "@value": "Example", "@language": "en" },
  { "@value": "Exemple", "@language": "fr" }
]
```

## Specification Compliance

✅ **W3C VC Data Model v2.0 Compliance**
- Implements §4.6 Names and Descriptions section
- Supports language value objects per §11.1
- Proper handling of @value, @language, @direction
- Aligns with JSON-LD 1.1 internationalization

## Integration

- Uses existing `ClaimInterface`
- Extends `VcDataModelClaimFactory` pattern
- Compatible with `SdJwt` and `VerifiableCredentialInterface`
- Follows naming conventions (`getVc*`)
- Employs lazy-loading caching strategy

## Future Enhancements

1. Add name/description to `VcIssuerClaimValue`
2. Add name/description to credential subjects
3. Language-aware display helpers
4. Default language context inference
5. UTF-8 direction inference

## Conclusion

The implementation is production-ready and fully tested. All requirements from the VC Data Model v2.0 specification (§4.6) are met, with comprehensive error handling, validation, and support for internationalization.

**Implementation Date**: February 27, 2026
**Status**: ✅ Complete and Ready for Deployment

