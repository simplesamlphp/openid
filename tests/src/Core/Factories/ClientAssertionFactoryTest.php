<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Core\Factories;

use Jose\Component\Signature\JWS;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Core\ClientAssertion;
use SimpleSAML\OpenID\Core\Factories\ClientAssertionFactory;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Jws\JwsVerifier;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManager;

#[CoversClass(ClientAssertionFactory::class)]
#[UsesClass(ParsedJwsFactory::class)]
#[UsesClass(ClientAssertion::class)]
#[UsesClass(ParsedJws::class)]
class ClientAssertionFactoryTest extends TestCase
{
    protected MockObject $jwsMock;
    protected MockObject $jwsDecoratorMock;
    protected MockObject $jwsParserMock;
    protected MockObject $jwsVerifierMock;
    protected MockObject $jwksFactoryMock;
    protected MockObject $jwsSerializerManagerMock;
    protected MockObject $dateIntervalDecoratorMock;
    protected MockObject $helpersMock;
    protected MockObject $jsonHelperMock;

    protected array $expiredPayload = [
        'iat' => 1730820687,
        'nbf' => 1730820687,
        'exp' => 1730820687,
        'iss' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/',
        'sub' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/',
        'aud' =>
            [
                0 => 'https://82-dap.localhost.markoivancic.from.hr',
            ],
        'jti' => '1730820687',
        'client_id' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/',
    ];

    protected array $validPayload;

    protected function setUp(): void
    {
        $this->jwsMock = $this->createMock(JWS::class);
        $this->jwsMock->method('getPayload')
            ->willReturn('json-payload-string'); // Just so we have non-empty value.

        $this->jwsDecoratorMock = $this->createMock(JwsDecorator::class);
        $this->jwsDecoratorMock->method('jws')->willReturn($this->jwsMock);

        $this->jwsParserMock = $this->createMock(JwsParser::class);
        $this->jwsParserMock->method('parse')->willReturn($this->jwsDecoratorMock);

        $this->jwsVerifierMock = $this->createMock(JwsVerifier::class);
        $this->jwksFactoryMock = $this->createMock(JwksFactory::class);
        $this->jwsSerializerManagerMock = $this->createMock(JwsSerializerManager::class);
        $this->dateIntervalDecoratorMock = $this->createMock(DateIntervalDecorator::class);

        $this->helpersMock = $this->createMock(Helpers::class);
        $this->jsonHelperMock = $this->createMock(Helpers\Json::class);
        $this->helpersMock->method('json')->willReturn($this->jsonHelperMock);

        $this->validPayload = $this->expiredPayload;
        $this->validPayload['exp'] = time() + 3600;
    }

    protected function sut(
        ?JwsParser $jwsParser = null,
        ?JwsVerifier $jwsVerifier = null,
        ?JwksFactory $jwksFactory = null,
        ?JwsSerializerManager $jwsSerializerManager = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
    ): ClientAssertionFactory {
        $jwsParser ??= $this->jwsParserMock;
        $jwsVerifier ??= $this->jwsVerifierMock;
        $jwksFactory ??= $this->jwksFactoryMock;
        $jwsSerializerManager ??= $this->jwsSerializerManagerMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;

        return new ClientAssertionFactory(
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
        $this->assertInstanceOf(ClientAssertionFactory::class, $this->sut());
    }


    public function testCanBuildFromToken(): void
    {
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->assertInstanceOf(
            ClientAssertion::class,
            $this->sut()->fromToken('token'),
        );
    }
}