<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Claims;

use SimpleSAML\OpenID\ValueAbstracts\ClaimInterface;
use SimpleSAML\OpenID\ValueAbstracts\LanguageValueObject;

class LocalizableStringValue implements ClaimInterface
{
    /**
     * @param non-empty-string $claimName
     */
    public function __construct(
        protected readonly LanguageValueObject $languageValueObject,
        protected readonly string $claimName,
    ) {
    }


    public function getName(): string
    {
        return $this->claimName;
    }


    public function getValue(): LanguageValueObject
    {
        return $this->languageValueObject;
    }


    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return $this->languageValueObject->jsonSerialize();
    }
}
