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
        protected readonly int|string $txCode,
        protected readonly string $description,
    ) {
        if (is_numeric($this->txCode) && $this->txCode < 0) {
            throw new \InvalidArgumentException('TxCode must be a positive integer or a string.');
        }

        $this->inputMode = is_numeric($this->txCode) ? TxCodeInputModeEnum::Numeric : TxCodeInputModeEnum::Text;
        $this->length = mb_strlen((string)$this->txCode);
    }

    public function getTxCode(): int|string
    {
        return $this->txCode;
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
