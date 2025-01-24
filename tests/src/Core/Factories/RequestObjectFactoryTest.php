<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Core\Factories;

use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Core\Factories\RequestObjectFactory;
use SimpleSAML\OpenID\Core\RequestObject;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(RequestObjectFactory::class)]
#[UsesClass(ParsedJwsFactory::class)]
#[UsesClass(ParsedJws::class)]
class RequestObjectFactoryTest extends TestCase
{
    protected MockObject $signatureMock;
    protected MockObject $jwsMock;
    protected MockObject $jwsDecoratorMock;
    protected MockObject $jwsParserMock;
    protected MockObject $jwsVerifierDecoratorMock;
    protected MockObject $jwksFactoryMock;
    protected MockObject $jwsSerializerManagerDecoratorMock;
    protected MockObject $dateIntervalDecoratorMock;
    protected MockObject $helpersMock;
    protected MockObject $jsonHelperMock;

    protected array $sampleHeader = [
        'alg' => 'RS256',
        'typ' => 'jwt',
        'kid' => 'LfgZECDYkSTHmbllBD5_Tkwvy3CtOpNYQ7-DfQawTww',
    ];

    protected function setUp(): void
    {
        $this->signatureMock = $this->createMock(Signature::class);

        $this->jwsMock = $this->createMock(JWS::class);
        $this->jwsMock->method('getSignature')->willReturn($this->signatureMock);

        $this->jwsDecoratorMock = $this->createMock(JwsDecorator::class);
        $this->jwsDecoratorMock->method('jws')->willReturn($this->jwsMock);

        $this->jwsParserMock = $this->createMock(JwsParser::class);
        $this->jwsParserMock->method('parse')->willReturn($this->jwsDecoratorMock);

        $this->jwsVerifierDecoratorMock = $this->createMock(JwsVerifierDecorator::class);
        $this->jwksFactoryMock = $this->createMock(JwksFactory::class);
        $this->jwsSerializerManagerDecoratorMock = $this->createMock(JwsSerializerManagerDecorator::class);
        $this->dateIntervalDecoratorMock = $this->createMock(DateIntervalDecorator::class);

        $this->helpersMock = $this->createMock(Helpers::class);
        $this->jsonHelperMock = $this->createMock(Helpers\Json::class);
        $this->helpersMock->method('json')->willReturn($this->jsonHelperMock);
    }

    protected function sut(
        ?JwsParser $jwsParser = null,
        ?JwsVerifierDecorator $jwsVerifierDecorator = null,
        ?JwksFactory $jwksFactory = null,
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
    ): RequestObjectFactory {
        $jwsParser ??= $this->jwsParserMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksFactory ??= $this->jwksFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;

        return new RequestObjectFactory(
            $jwsParser,
            $jwsVerifierDecorator,
            $jwksFactory,
            $jwsSerializerManagerDecorator,
            $dateIntervalDecorator,
            $helpers,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(RequestObjectFactory::class, $this->sut());
    }


    public function testCanBuildFromToken(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $this->assertInstanceOf(
            RequestObject::class,
            $this->sut()->fromToken('token'),
        );
    }
}
