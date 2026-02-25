<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jws\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(ParsedJwsFactory::class)]
#[UsesClass(ParsedJws::class)]
final class ParsedJwsFactoryTest extends TestCase
{
    protected \PHPUnit\Framework\MockObject\Stub $jwsDecoratorBuilderMock;

    protected \PHPUnit\Framework\MockObject\Stub $jwsVerifierDecoratorMock;

    protected \PHPUnit\Framework\MockObject\Stub $jwksDecoratorFactoryMock;

    protected \PHPUnit\Framework\MockObject\Stub $jwsSerializerManagerDecoratorMock;

    protected \PHPUnit\Framework\MockObject\Stub $dateIntervalDecoratorMock;

    protected \PHPUnit\Framework\MockObject\Stub $helpersMock;

    protected \PHPUnit\Framework\MockObject\Stub $claimFactoryMock;


    protected function setUp(): void
    {
        $this->jwsDecoratorBuilderMock = $this->createStub(JwsDecoratorBuilder::class);
        $this->jwsVerifierDecoratorMock = $this->createStub(JwsVerifierDecorator::class);
        $this->jwksDecoratorFactoryMock = $this->createStub(JwksDecoratorFactory::class);
        $this->jwsSerializerManagerDecoratorMock = $this->createStub(JwsSerializerManagerDecorator::class);
        $this->dateIntervalDecoratorMock = $this->createStub(DateIntervalDecorator::class);
        $this->helpersMock = $this->createStub(Helpers::class);
        $this->claimFactoryMock = $this->createStub(ClaimFactory::class);
    }


    protected function sut(
        ?JwsDecoratorBuilder $jwsDecoratorBuilder = null,
        ?JwsVerifierDecorator $jwsVerifierDecorator = null,
        ?JwksDecoratorFactory $jwksDecoratorFactory = null,
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
        ?ClaimFactory $claimFactory = null,
    ): ParsedJwsFactory {
        $jwsDecoratorBuilder ??= $this->jwsDecoratorBuilderMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksDecoratorFactory ??= $this->jwksDecoratorFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;
        $claimFactory ??= $this->claimFactoryMock;

        return new ParsedJwsFactory(
            $jwsDecoratorBuilder,
            $jwsVerifierDecorator,
            $jwksDecoratorFactory,
            $jwsSerializerManagerDecorator,
            $dateIntervalDecorator,
            $helpers,
            $claimFactory,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(ParsedJwsFactory::class, $this->sut());
    }


    public function testCanBuildFromToken(): void
    {
        $this->assertInstanceOf(
            ParsedJws::class,
            $this->sut()->fromToken('token'),
        );
    }


    public function testCanBuildFromData(): void
    {
        $this->assertInstanceOf(
            ParsedJws::class,
            $this->sut()->fromData(
                $this->createStub(\SimpleSAML\OpenID\Jwk\JwkDecorator::class),
                SignatureAlgorithmEnum::RS256,
                [],
                [],
            ),
        );
    }
}
