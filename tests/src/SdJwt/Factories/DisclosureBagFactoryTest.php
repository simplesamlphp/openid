<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\SdJwt\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\SdJwt\DisclosureBag;
use SimpleSAML\OpenID\SdJwt\Factories\DisclosureBagFactory;

#[CoversClass(DisclosureBagFactory::class)]
#[UsesClass(DisclosureBag::class)]
final class DisclosureBagFactoryTest extends TestCase
{
    protected function setUp(): void
    {
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
            $this->sut()->build($this->createStub(\SimpleSAML\OpenID\SdJwt\Disclosure::class)),
        );
    }
}
