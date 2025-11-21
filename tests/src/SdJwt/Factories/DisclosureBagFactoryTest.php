<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\SdJwt\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\SdJwt\Disclosure;
use SimpleSAML\OpenID\SdJwt\DisclosureBag;
use SimpleSAML\OpenID\SdJwt\Factories\DisclosureBagFactory;

#[CoversClass(DisclosureBagFactory::class)]
#[UsesClass(DisclosureBag::class)]
final class DisclosureBagFactoryTest extends TestCase
{
    protected MockObject $disclosureMock;


    protected function setUp(): void
    {
        $this->disclosureMock = $this->createMock(Disclosure::class);
    }


    protected function sut(): DisclosureBagFactory
    {
        return new DisclosureBagFactory();
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(DisclosureBagFactory::class, $this->sut());
    }


    public function testCanBuild(): void
    {
        $this->assertInstanceOf(
            DisclosureBag::class,
            $this->sut()->build(),
        );

        $this->assertInstanceOf(
            DisclosureBag::class,
            $this->sut()->build($this->disclosureMock),
        );
    }
}
