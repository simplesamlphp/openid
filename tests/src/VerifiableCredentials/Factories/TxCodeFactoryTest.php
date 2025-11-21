<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\Factories;

use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\VerifiableCredentials\Factories\TxCodeFactory;
use SimpleSAML\OpenID\VerifiableCredentials\TxCode;

#[\PHPUnit\Framework\Attributes\CoversClass(TxCodeFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TxCode::class)]
final class TxCodeFactoryTest extends TestCase
{
    protected string $txCode = 'tx-code';

    protected string $description = 'description';


    protected function sut(): TxCodeFactory
    {
        return new TxCodeFactory();
    }


    public function testCanBuild(): void
    {
        $this->assertInstanceOf(
            TxCode::class,
            $this->sut()->build($this->txCode, $this->description),
        );
    }
}
