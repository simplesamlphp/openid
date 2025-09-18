<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Federation\Factories\TrustChainBagFactory;
use SimpleSAML\OpenID\Federation\TrustChain;
use SimpleSAML\OpenID\Federation\TrustChainBag;

#[CoversClass(TrustChainBagFactory::class)]
#[UsesClass(TrustChainBag::class)]
final class TrustChainBagFactoryTest extends TestCase
{
    protected MockObject $trustChainMock;


    protected function setUp(): void
    {
        $this->trustChainMock = $this->createMock(TrustChain::class);
    }


    protected function sut(): TrustChainBagFactory
    {
        return new TrustChainBagFactory();
    }


    public function tetCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustChainBag::class, $this->sut());
    }


    public function testCanBuildTrustChainBag(): void
    {
        $this->assertInstanceOf(TrustChainBag::class, $this->sut()->build($this->trustChainMock));
    }
}
