<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\SdJwt\Factories;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\SdJwtDisclosureType;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\SdJwt\Factories\DisclosureFactory;

#[\PHPUnit\Framework\Attributes\CoversClass(DisclosureFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\SimpleSAML\OpenID\SdJwt\Disclosure::class)]
final class DisclosureFactoryTest extends TestCase
{
    protected MockObject $helpersMock;


    protected function setUp(): void
    {
        $this->helpersMock = $this->createMock(Helpers::class);
        $radomHelperMock = $this->createMock(Helpers\Random::class);
        $this->helpersMock->method('random')->willReturn($radomHelperMock);
    }


    protected function sut(): DisclosureFactory
    {
        return new DisclosureFactory($this->helpersMock);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(DisclosureFactory::class, $this->sut());
    }


    public function testCanBuild(): void
    {
        $this->assertInstanceOf(
            \SimpleSAML\OpenID\SdJwt\Disclosure::class,
            $this->sut()->build('value', 'name'),
        );
    }


    public function testCanBuildDecoy(): void
    {
        $this->assertInstanceOf(
            \SimpleSAML\OpenID\SdJwt\Disclosure::class,
            $this->sut()->buildDecoy(SdJwtDisclosureType::ArrayElement, []),
        );
    }
}
