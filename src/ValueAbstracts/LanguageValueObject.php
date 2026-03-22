<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\ValueAbstracts;

use JsonSerializable;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\InvalidValueException;

class LanguageValueObject implements JsonSerializable
{
    /**
     * @param non-empty-string $value
     * @param non-empty-string|null $language
     * @param 'ltr'|'rtl'|null $direction
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function __construct(
        protected readonly string $value,
        protected readonly ?string $language = null,
        protected readonly ?string $direction = null,
    ) {
        if ($this->value === '0') {
            throw new InvalidValueException('Language value object @value must be a non-empty string.');
        }

        if ($this->direction !== null && !in_array($this->direction, ['ltr', 'rtl'], true)) {
            throw new InvalidValueException('Language value object @direction must be "ltr" or "rtl".');
        }

        if ($this->language === '0') {
            throw new InvalidValueException('Language value object @language must be a non-empty string.');
        }
    }


    /**
     * @return non-empty-string
     */
    public function getValue(): string
    {
        return $this->value;
    }


    /**
     * @return non-empty-string|null
     */
    public function getLanguage(): ?string
    {
        return $this->language;
    }


    /**
     * @return 'ltr'|'rtl'|null
     */
    public function getDirection(): ?string
    {
        return $this->direction;
    }


    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        $result = [
            ClaimsEnum::AtValue->value => $this->value,
        ];

        if ($this->language !== null) {
            $result[ClaimsEnum::AtLanguage->value] = $this->language;
        }

        if ($this->direction !== null) {
            $result[ClaimsEnum::AtDirection->value] = $this->direction;
        }

        return $result;
    }
}
