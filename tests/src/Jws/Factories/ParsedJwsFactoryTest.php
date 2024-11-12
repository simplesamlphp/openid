<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jws\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Jws\JwsVerifier;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManager;

#[CoversClass(ParsedJwsFactory::class)]
#[UsesClass(ParsedJws::class)]
class ParsedJwsFactoryTest extends TestCase
{
    protected MockObject $jwsParserMock;
    protected MockObject $jwsVerifierMock;
    protected MockObject $jwksFactoryMock;
    protected MockObject $jwsSerializerManagerMock;
    protected MockObject $dateIntervalDecoratorMock;
    protected MockObject $helpersMock;

    protected array $validPayload;

    protected function setUp(): void
    {
        $this->jwsParserMock = $this->createMock(JwsParser::class);
        $this->jwsVerifierMock = $this->createMock(JwsVerifier::class);
        $this->jwksFactoryMock = $this->createMock(JwksFactory::class);
        $this->jwsSerializerManagerMock = $this->createMock(JwsSerializerManager::class);
        $this->dateIntervalDecoratorMock = $this->createMock(DateIntervalDecorator::class);
        $this->helpersMock = $this->createMock(Helpers::class);
    }

    protected function sut(
        ?JwsParser $jwsParser = null,
        ?JwsVerifier $jwsVerifier = null,
        ?JwksFactory $jwksFactory = null,
        ?JwsSerializerManager $jwsSerializerManager = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
    ): ParsedJwsFactory {
        $jwsParser ??= $this->jwsParserMock;
        $jwsVerifier ??= $this->jwsVerifierMock;
        $jwksFactory ??= $this->jwksFactoryMock;
        $jwsSerializerManager ??= $this->jwsSerializerManagerMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;

        return new ParsedJwsFactory(
            $jwsParser,
            $jwsVerifier,
            $jwksFactory,
            $jwsSerializerManager,
            $dateIntervalDecorator,
            $helpers,
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
}
