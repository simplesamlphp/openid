<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\VerifiableCredentials;

use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\TxCodeInputModeEnum;

class TxCode implements \JsonSerializable
{
    protected readonly TxCodeInputModeEnum $inputMode;

    protected readonly int $length;

    public function __construct(
        protected readonly int|string $code,
        protected readonly string $description,
    ) {
        if (is_numeric($this->code) && $this->code < 0) {
            throw new \InvalidArgumentException('TxCode must be a positive integer or a string.');
        }

        $this->inputMode = is_numeric($this->code) ? TxCodeInputModeEnum::Numeric : TxCodeInputModeEnum::Text;
        $this->length = mb_strlen((string)$this->code);
    }

    public function getCode(): int|string
    {
        return $this->code;
    }

    public function getCodeAsString(): string
    {
        return (string)$this->code;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getInputMode(): TxCodeInputModeEnum
    {
        return $this->inputMode;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            ClaimsEnum::InputMode->value => $this->inputMode->value,
            ClaimsEnum::Length->value => $this->length,
            ClaimsEnum::Description->value => $this->description,
        ];
    }
}
