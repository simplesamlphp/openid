<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\EntityStatement\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Federation\EntityStatement\Factories\TrustMarkClaimBagFactory;
use SimpleSAML\OpenID\Federation\EntityStatement\TrustMarkClaim;
use SimpleSAML\OpenID\Federation\EntityStatement\TrustMarkClaimBag;

#[CoversClass(TrustMarkClaimBagFactory::class)]
#[UsesClass(TrustMarkClaimBag::class)]
class TrustMarkClaimBagFactoryTest extends TestCase
{
    protected MockObject $trustMarkClaimMock;

    protected function setUp(): void
    {
        $this->trustMarkClaimMock = $this->createMock(TrustMarkClaim::class);
    }

    protected function sut(): TrustMarkClaimBagFactory
    {
        return new TrustMarkClaimBagFactory();
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustMarkClaimBagFactory::class, $this->sut());
    }

    public function testCanBuildTrustMarkClaimBag(): void
    {
        $this->assertInstanceOf(TrustMarkClaimBag::class, $this->sut()->build());
        $this->assertInstanceOf(TrustMarkClaimBag::class, $this->sut()->build($this->trustMarkClaimMock));
    }
}
