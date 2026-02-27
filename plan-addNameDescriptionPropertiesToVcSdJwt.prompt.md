# Plan: Add Name and Description Properties Support to VcSdJwt

## Overview

Enhance the `VcSdJwt` implementation to support fetching optional `name` and `description` properties from Verifiable Credentials according to the VC Data Model v2.0 specification (§4.6 Names and Descriptions). These properties can be simple strings or language value objects containing `@value`, `@language`, and `@direction` properties for internationalization support.

## Context

According to the VC Data Model v2.0 specification (§4.6):

- **name**: An OPTIONAL property that expresses the name of the credential. If present, the value MUST be a string or a language value object.
- **description**: An OPTIONAL property that conveys specific details about a credential. If present, the value MUST be a string or a language value object.

Both properties can appear in two forms:

1. **Simple string form**: `"name": "Example University Degree"`
2. **Language value object form** (single or multiple):
   ```json
   "name": {
     "@value": "Example University Degree",
     "@language": "en"
   }
   ```
   or
   ```json
   "name": [
     { "@value": "Example University Degree", "@language": "en" },
     { "@value": "Exemple de Diplôme Universitaire", "@language": "fr" }
   ]
   ```

## Proposed Implementation Steps

### Step 1: Create LanguageValueObject Abstraction

**File**: `src/ValueAbstracts/LanguageValueObject.php`

Create a value object class to represent language-tagged string values with optional direction metadata:

```php
class LanguageValueObject
{
    public function __construct(
        protected readonly string $value,
        protected readonly ?string $language = null,
        protected readonly ?string $direction = null,
    ) {
        // Validation:
        // - @value is required (string)
        // - @language is optional (BCP47 language tag)
        // - @direction is optional and must be 'ltr' or 'rtl'
    }

    public function getValue(): string
    public function getLanguage(): ?string
    public function getDirection(): ?string
    public function jsonSerialize(): array
}
```

**Validation rules**:
- `@value` is required and must be a non-empty string
- `@language` is optional; if provided, should be a valid BCP47 language tag
- `@direction` is optional; if provided, must be either "ltr" or "rtl"

### Step 2: Create LocalizableStringValue Claim Value Class

**File**: `src/VerifiableCredentials/VcDataModel/Claims/LocalizableStringValue.php`

Create a claim value class that wraps a single language value object:

```php
class LocalizableStringValue implements ClaimInterface
{
    public function __construct(
        protected readonly LanguageValueObject $languageValueObject,
        protected readonly string $claimName,
    ) {
    }

    public function getValue(): LanguageValueObject
    public function getName(): string
    public function jsonSerialize(): array
}
```

This class:
- Implements `ClaimInterface`
- Wraps a `LanguageValueObject`
- Can be returned by getter methods like `getVcName()` and `getVcDescription()`

### Step 3: Create LocalizableStringValueBag Class

**File**: `src/VerifiableCredentials/VcDataModel/Claims/LocalizableStringValueBag.php`

For supporting multiple language variants:

```php
class LocalizableStringValueBag implements ClaimInterface
{
    protected readonly array $values; // LocalizableStringValue[]

    public function __construct(
        LocalizableStringValue $firstValue,
        LocalizableStringValue ...$additionalValues
    ) {
    }

    public function getValues(): array
    public function getValue(string $language = null): ?LocalizableStringValue
    public function getName(): string
    public function jsonSerialize(): array
}
```

### Step 4: Extend VcSdJwt with Name and Description Methods

**File**: `src/VerifiableCredentials/VcDataModel2/VcSdJwt.php`

Add protected properties and getter methods:

```php
protected null|false|LocalizableStringValue|LocalizableStringValueBag $vcName = null;
protected null|false|LocalizableStringValue|LocalizableStringValueBag $vcDescription = null;

/**
 * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
 * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
 */
public function getVcName(): null|LocalizableStringValue|LocalizableStringValueBag
{
    // Lazy-load pattern: check cache, fetch from payload, parse, cache result
    // If not present: cache as false, return null
}

/**
 * @throws \SimpleSAML\OpenID\Exceptions\VcDataModelException
 * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
 */
public function getVcDescription(): null|LocalizableStringValue|LocalizableStringValueBag
{
    // Same pattern as getVcName()
}
```

### Step 5: Extend VcDataModel2ClaimFactory

**File**: `src/VerifiableCredentials/VcDataModel2/Factories/VcDataModel2ClaimFactory.php`

Add builder methods:

```php
public function buildLanguageValueObject(array $data): LanguageValueObject
{
    // Validate @value exists
    // Extract @language and @direction
    // Validate @direction if present (must be 'ltr' or 'rtl')
    // Return new LanguageValueObject
}

public function buildLocalizableStringValue(
    mixed $value,
    string $claimName
): LocalizableStringValue|LocalizableStringValueBag
{
    // If $value is string: create single LanguageValueObject with just value
    // If $value is array:
    //   - If associative (single language object): parse single LanguageValueObject
    //   - If indexed (multiple language objects): parse array into LocalizableStringValueBag
    // Return LocalizableStringValue or LocalizableStringValueBag
}
```

### Step 6: Update ClaimsEnum (Verification)

**File**: `src/Codebooks/ClaimsEnum.php`

Verify that `Name` and `Description` cases exist and have correct values:

```php
case Name = 'name';
case Description = 'description';
```

(These already exist in the enum, so no changes needed.)

## Design Decisions

### Single vs. Multiple Values

**Decision**: Support both single `LocalizableStringValue` and array of `LocalizableStringValue` items via `LocalizableStringValueBag`.

**Rationale**:
- The spec shows examples of both single string and arrays of language variants
- Bag pattern is consistent with existing codebase (e.g., `VcCredentialSchemaClaimBag`)
- Allows flexible retrieval of names/descriptions in different languages

### Return Type Design

**Decision**: Use union type `LocalizableStringValue|LocalizableStringValueBag|null`

**Rationale**:
- Explicit about what can be returned
- Allows calling code to check type and handle accordingly
- Null indicates property was not present in credential

### Validation Strategy

**Decision**: Validate in `LanguageValueObject` constructor and builders, throw `VcDataModelException` on invalid data.

**Rationale**:
- Consistent with existing claim value classes
- Fails fast with clear error messages
- Matches pattern used in `VcSdJwt.php` getter methods

### Lazy Loading and Caching

**Decision**: Use `null|false` pattern for caching (false = not present, null = not yet loaded)

**Rationale**:
- Matches existing pattern in `VcSdJwt` for optional properties (e.g., `$vcProofClaimValue`)
- Avoids repeated lookups in payload
- Clearly distinguishes "not loaded" from "loaded but absent"

## Integration Points

### Existing Patterns to Follow

1. **Lazy loading pattern** (from `VcSdJwt`):
   ```php
   if ($this->value === false) return null;
   if ($this->value instanceof SomeClass) return $this->value;
   $data = $this->getPayloadClaim(...);
   if (is_null($data)) {
       $this->value = false;
       return null;
   }
   // ... validation and parsing ...
   return $this->value = $this->claimFactory->forVcDataModel2()->build...();
   ```

2. **ClaimFactory builder pattern** (from `VcDataModelClaimFactory`):
   - Builder methods receive raw data from payload
   - Perform validation
   - Return claim value objects
   - Throw `VcDataModelException` on invalid data

3. **ClaimInterface implementation** (from `TypeClaimValue`, `VcIssuerClaimValue`):
   - Implement `getName()`, `getValue()`, `jsonSerialize()`
   - Store data in properties
   - Provide accessor methods as needed

## Error Handling

- **Invalid language tag**: Throw `VcDataModelException('Invalid language value object')`
- **Invalid direction value**: Throw `VcDataModelException('Invalid @direction value; must be "ltr" or "rtl"')`
- **Missing @value**: Throw `VcDataModelException('Language value object must contain @value')`
- **Invalid data type**: Throw `VcDataModelException('Name/Description must be string or language value object')`

## Testing Considerations

### Unit Tests to Create

1. **LanguageValueObject**:
   - Valid instantiation with value only
   - Valid instantiation with value, language, direction
   - Validation: direction must be ltr or rtl
   - JSON serialization

2. **LocalizableStringValue**:
   - Instantiation with LanguageValueObject
   - getName() returns correct claim name
   - getValue() returns LanguageValueObject
   - JSON serialization

3. **LocalizableStringValueBag**:
   - Instantiation with multiple values
   - getValue() by language filter
   - JSON serialization

4. **VcSdJwt getter methods**:
   - getVcName() and getVcDescription() with string values
   - getVcName() and getVcDescription() with single language object
   - getVcName() and getVcDescription() with multiple language objects
   - getVcName() and getVcDescription() when property absent (returns null)
   - Exception handling for invalid data

5. **VcDataModel2ClaimFactory builders**:
   - buildLanguageValueObject() with valid data
   - buildLocalizableStringValue() with string
   - buildLocalizableStringValue() with single object
   - buildLocalizableStringValue() with array of objects
   - Error cases

## Implementation Order

1. Create `LanguageValueObject` class with validation
2. Create `LocalizableStringValue` class
3. Create `LocalizableStringValueBag` class (if multi-language support desired)
4. Update `VcDataModel2ClaimFactory` with builder methods
5. Update `VcSdJwt` with getter methods
6. Write comprehensive unit tests
7. Update related documentation

## Future Enhancements

1. **Multi-language retrieval helpers**: Methods to get all translations or specific language
2. **Default language handling**: Support credential context default language
3. **Credential subject support**: Extend name/description support to credential subjects
4. **Issuer support**: Add name/description support to `VcIssuerClaimValue`
5. **Display configuration**: Integrate with display/UI configuration if needed

