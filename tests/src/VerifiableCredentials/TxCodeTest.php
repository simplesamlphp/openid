<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials;

use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\TxCodeInputModeEnum;
use SimpleSAML\OpenID\VerifiableCredentials\TxCode;

#[\PHPUnit\Framework\Attributes\CoversClass(TxCode::class)]
final class TxCodeTest extends TestCase
{
    protected int|string $txCode = 'tx-code';

    protected string $description = 'description';


    protected function sut(
        int|string|null $txCode = null,
        ?string $description = null,
    ): TxCode {
        $txCode ??= $this->txCode;
        $description ??= $this->description;

        return new TxCode($txCode, $description);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TxCode::class, $this->sut());
    }


    public function testThrowsForNegativeCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->sut(-1);
    }


    public function testCanGetProperties(): void
    {
        $sut = $this->sut();
        $this->assertSame($this->txCode, $sut->getCode());
        $this->assertSame($this->txCode, $sut->getCodeAsString());
        $this->assertSame($this->description, $sut->getDescription());
        $this->assertSame(TxCodeInputModeEnum::Text, $sut->getInputMode());
        $this->assertSame(strlen((string) $this->txCode), $sut->getLength());
        $this->assertArrayHasKey('input_mode', $sut->jsonSerialize());
    }
}
