<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\Factories;

use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkFactory;
use SimpleSAML\OpenID\Federation\TrustMark;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(TrustMarkFactory::class)]
#[UsesClass(ParsedJwsFactory::class)]
#[UsesClass(ParsedJws::class)]
#[UsesClass(TrustMark::class)]
class TrustMarkFactoryTest extends TestCase
{
    protected MockObject $signatureMock;
    protected MockObject $jwsMock;
    protected MockObject $jwsDecoratorMock;
    protected MockObject $jwsParserMock;
    protected MockObject $jwsVerifierDecoratorMock;
    protected MockObject $jwksFactoryMock;
    protected MockObject $jwsSerializerManagerMock;
    protected MockObject $dateIntervalDecoratorMock;
    protected MockObject $helpersMock;
    protected MockObject $jsonHelperMock;
    protected MockObject $typeHelperMock;

    protected array $sampleHeader = [
        'alg' => 'RS256',
        'typ' => 'trust-mark+jwt',
        'kid' => 'fsQ45F0D916RdKEeTjta8DYWiodjthouHrVWgOXBrkk',
    ];

    protected array $expiredPayload = [
        'iat' => 1734016912,
        'nbf' => 1734016912,
        'exp' => 1734020512,
        'id' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ABTrustAnchor/trust-mark/member',
        'iss' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ABTrustAnchor/',
        'sub' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/',
    ];

    protected array $validPayload;

    protected function setUp(): void
    {
        $this->signatureMock = $this->createMock(Signature::class);

        $this->jwsMock = $this->createMock(JWS::class);
        $this->jwsMock->method('getPayload')
            ->willReturn('json-payload-string'); // Just so we have non-empty value.
        $this->jwsMock->method('getSignature')->willReturn($this->signatureMock);

        $this->jwsDecoratorMock = $this->createMock(JwsDecorator::class);
        $this->jwsDecoratorMock->method('jws')->willReturn($this->jwsMock);

        $this->jwsParserMock = $this->createMock(JwsParser::class);
        $this->jwsParserMock->method('parse')->willReturn($this->jwsDecoratorMock);

        $this->jwsVerifierDecoratorMock = $this->createMock(JwsVerifierDecorator::class);
        $this->jwksFactoryMock = $this->createMock(JwksFactory::class);
        $this->jwsSerializerManagerMock = $this->createMock(JwsSerializerManagerDecorator::class);
        $this->dateIntervalDecoratorMock = $this->createMock(DateIntervalDecorator::class);

        $this->helpersMock = $this->createMock(Helpers::class);
        $this->jsonHelperMock = $this->createMock(Helpers\Json::class);
        $this->helpersMock->method('json')->willReturn($this->jsonHelperMock);
        $this->typeHelperMock = $this->createMock(Helpers\Type::class);
        $this->helpersMock->method('type')->willReturn($this->typeHelperMock);

        $this->typeHelperMock->method('ensureNonEmptyString')->willReturnArgument(0);
        $this->typeHelperMock->method('ensureInt')->willReturnArgument(0);

        $this->validPayload = $this->expiredPayload;
        $this->validPayload['exp'] = time() + 3600;
    }

    protected function sut(
        ?JwsParser $jwsParser = null,
        ?JwsVerifierDecorator $jwsVerifierDecorator = null,
        ?JwksFactory $jwksFactory = null,
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
    ): TrustMarkFactory {
        $jwsParser ??= $this->jwsParserMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksFactory ??= $this->jwksFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;

        return new TrustMarkFactory(
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
        $this->assertInstanceOf(TrustMarkFactory::class, $this->sut());
    }

    public function testCanBuild(): void
    {
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $this->assertInstanceOf(
            TrustMark::class,
            $this->sut()->fromToken('token'),
        );
    }
}
