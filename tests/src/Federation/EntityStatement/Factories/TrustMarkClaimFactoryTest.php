<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\EntityStatement\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Exceptions\TrustMarkClaimException;
use SimpleSAML\OpenID\Federation\EntityStatement\Factories\TrustMarkClaimFactory;
use SimpleSAML\OpenID\Federation\EntityStatement\TrustMarkClaim;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkFactory;
use SimpleSAML\OpenID\Federation\TrustMark;
use SimpleSAML\OpenID\Helpers;

#[CoversClass(TrustMarkClaimFactory::class)]
#[UsesClass(TrustMarkClaim::class)]
class TrustMarkClaimFactoryTest extends TestCase
{
    protected MockObject $trustMarkFactoryMock;
    protected MockObject $helpersMock;
    protected MockObject $typeHelpersMock;
    protected MockObject $trustMarkMock;

    protected function setUp(): void
    {
        $this->trustMarkFactoryMock = $this->createMock(TrustMarkFactory::class);
        $this->helpersMock = $this->createMock(Helpers::class);
        $this->typeHelpersMock = $this->createMock(Helpers\Type::class);
        $this->helpersMock->method('type')->willReturn($this->typeHelpersMock);

        $this->typeHelpersMock->method('ensureString')->willReturnArgument(0);

        $this->trustMarkMock = $this->createMock(TrustMark::class);
    }

    protected function sut(
        ?TrustMarkFactory $trustMarkFactory = null,
        ?Helpers $helpers = null,
    ): TrustMarkClaimFactory {
        $trustMarkFactory ??= $this->trustMarkFactoryMock;
        $helpers ??= $this->helpersMock;

        return new TrustMarkClaimFactory(
            $trustMarkFactory,
            $helpers,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustMarkClaimFactory::class, $this->sut());
    }

    public function testCanBuild(): void
    {
        $this->trustMarkMock->method('getIdentifier')->willReturn('id');
        $this->assertInstanceOf(TrustMarkClaim::class, $this->sut()->build(
            'id',
            $this->trustMarkMock,
        ));
    }

    public function testCanBuildFromData(): void
    {
        $data = [
            ClaimsEnum::Id->value => 'id',
            ClaimsEnum::TrustMark->value => 'trust_mark',
        ];

        $this->trustMarkMock->method('getIdentifier')->willReturn($data[ClaimsEnum::Id->value]);

        $this->trustMarkFactoryMock->expects($this->once())
            ->method('fromToken')
            ->with('trust_mark')
            ->willReturn($this->trustMarkMock);

        $this->assertInstanceOf(TrustMarkClaim::class, $this->sut()->buildFrom($data));
    }

    public function testCanBuildFromDataWithOtherClaims(): void
    {
        $data = [
            ClaimsEnum::Id->value => 'id',
            ClaimsEnum::TrustMark->value => 'trust_mark',
            'something' => 'else',
        ];

        $this->trustMarkMock->method('getIdentifier')->willReturn($data[ClaimsEnum::Id->value]);

        $this->trustMarkFactoryMock->expects($this->once())
            ->method('fromToken')
            ->with('trust_mark')
            ->willReturn($this->trustMarkMock);

        $trustMarkClaim = $this->sut()->buildFrom($data);

        $this->assertSame(['something' => 'else'], $trustMarkClaim->getOtherClaims());
    }

    public function testBuildFromDataThrowsIfNoId(): void
    {
        $this->expectException(TrustMarkClaimException::class);
        $this->expectExceptionMessage('ID');

        $data = [
            ClaimsEnum::TrustMark->value => 'trust_mark',
        ];

        $this->assertInstanceOf(TrustMarkClaim::class, $this->sut()->buildFrom($data));
    }

    public function testBuildFromDataThrowsIfNoTrustMarkToken(): void
    {
        $this->expectException(TrustMarkClaimException::class);
        $this->expectExceptionMessage('token');

        $data = [
            ClaimsEnum::Id->value => 'id',
        ];

        $this->assertInstanceOf(TrustMarkClaim::class, $this->sut()->buildFrom($data));
    }
}
