<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jar;

use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jar\RequestObject;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(RequestObject::class)]
#[UsesClass(ParsedJws::class)]
#[UsesClass(SignatureAlgorithmEnum::class)]
final class RequestObjectTest extends TestCase
{
    protected MockObject $jwsDecoratorMock;

    protected \PHPUnit\Framework\MockObject\Stub $jwsVerifierDecoratorMock;

    protected \PHPUnit\Framework\MockObject\Stub $jwksDecoratorFactoryMock;

    protected \PHPUnit\Framework\MockObject\Stub $jwsSerializerManagerDecoratorMock;

    protected \PHPUnit\Framework\MockObject\Stub $dateIntervalDecoratorMock;

    protected MockObject $helpersMock;

    protected MockObject $jsonHelperMock;

    protected \PHPUnit\Framework\MockObject\Stub $claimFactoryMock;

    protected array $sampleHeader = [
        'alg' => 'RS256',
        'typ' => 'oauth-authz-req+jwt',
        'kid' => 'request-object-key',
    ];

    protected array $payload = [
        'iat' => 1731178665,
        'nbf' => 1731178665,
        'exp' => 1731178665,
        'aud' => [
            'https://82-dap.localhost.markoivancic.from.hr',
        ],
        'iss' => 'client-id-uri',
        'jti' => '1731178665',
        'client_id' => 'client-id-uri',
    ];


    protected function setUp(): void
    {
        $this->payload['exp'] = time() + 3600;

        $signatureMock = $this->createMock(Signature::class);
        $signatureMock->method('getProtectedHeader')->willReturnCallback(
            fn(): array => $this->sampleHeader,
        );

        $jwsMock = $this->createMock(JWS::class);
        $jwsMock->method('getPayload')
            ->willReturn('json-payload-string');
        $jwsMock->method('getSignature')->willReturn($signatureMock);

        $this->jwsDecoratorMock = $this->createMock(JwsDecorator::class);
        $this->jwsDecoratorMock->method('jws')->willReturn($jwsMock);

        $this->jwsVerifierDecoratorMock = $this->createStub(JwsVerifierDecorator::class);
        $this->jwksDecoratorFactoryMock = $this->createStub(JwksDecoratorFactory::class);
        $this->jwsSerializerManagerDecoratorMock = $this->createStub(JwsSerializerManagerDecorator::class);
        $this->dateIntervalDecoratorMock = $this->createStub(DateIntervalDecorator::class);

        $this->helpersMock = $this->createMock(Helpers::class);
        $this->jsonHelperMock = $this->createMock(Helpers\Json::class);
        $this->helpersMock->method('json')->willReturn($this->jsonHelperMock);
        $typeHelperMock = $this->createMock(Helpers\Type::class);
        $this->helpersMock->method('type')->willReturn($typeHelperMock);

        $typeHelperMock->method('ensureArrayWithValuesAsStrings')->willReturnArgument(0);
        $typeHelperMock->method('ensureInt')->willReturnArgument(0);
        $typeHelperMock->method('ensureNonEmptyString')->willReturnArgument(0);

        $this->claimFactoryMock = $this->createStub(ClaimFactory::class);
    }


    protected function sut(
        ?JwsDecorator $jwsDecorator = null,
        ?JwsVerifierDecorator $jwsVerifierDecorator = null,
        ?JwksDecoratorFactory $jwksDecoratorFactory = null,
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
        ?ClaimFactory $claimFactory = null,
    ): RequestObject {
        $jwsDecorator ??= $this->jwsDecoratorMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksDecoratorFactory ??= $this->jwksDecoratorFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;
        $claimFactory ??= $this->claimFactoryMock;

        return new RequestObject(
            $jwsDecorator,
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
        $this->jsonHelperMock->method('decode')->willReturn($this->payload);

        $this->assertInstanceOf(
            RequestObject::class,
            $this->sut(),
        );
    }


    public function testThrowsIfClientIdIsMissing(): void
    {
        unset($this->payload['client_id']);
        $this->jsonHelperMock->method('decode')->willReturn($this->payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Client ID');

        $this->sut();
    }


    public function testIssuerIsOptional(): void
    {
        unset($this->payload['iss']);
        $this->jsonHelperMock->method('decode')->willReturn($this->payload);

        $this->assertNull($this->sut()->getIssuer());
    }


    public function testAudienceIsOptional(): void
    {
        unset($this->payload['aud']);
        $this->jsonHelperMock->method('decode')->willReturn($this->payload);

        $this->assertNull($this->sut()->getAudience());
    }


    public function testExpirationTimeIsOptional(): void
    {
        unset($this->payload['exp']);
        $this->jsonHelperMock->method('decode')->willReturn($this->payload);

        $this->assertNull($this->sut()->getExpirationTime());
    }


    public function testThrowsIfAlgorithmHeaderIsMissing(): void
    {
        unset($this->sampleHeader['alg']);
        $this->jsonHelperMock->method('decode')->willReturn($this->payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Algorithm');

        $this->sut();
    }


    public function testThrowsIfAlgorithmIsNone(): void
    {
        $this->sampleHeader['alg'] = 'none';
        $this->jsonHelperMock->method('decode')->willReturn($this->payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('none');

        $this->sut();
    }


    public function testThrowsIfRequestParamIsPresentInPayload(): void
    {
        $this->payload['request'] = 'nested-request-object';
        $this->jsonHelperMock->method('decode')->willReturn($this->payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('request');

        $this->sut();
    }


    public function testThrowsIfRequestUriParamIsPresentInPayload(): void
    {
        $this->payload['request_uri'] = 'https://example.org/request.jwt';
        $this->jsonHelperMock->method('decode')->willReturn($this->payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('request_uri');

        $this->sut();
    }
}
