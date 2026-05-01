<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Core;

use Jose\Component\Signature\JWS;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Core\LogoutToken;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(LogoutToken::class)]
final class LogoutTokenTest extends TestCase
{
    protected MockObject $jwsDecoratorMock;

    protected \PHPUnit\Framework\MockObject\Stub $jwsVerifierDecoratorMock;

    protected \PHPUnit\Framework\MockObject\Stub $jwksDecoratorFactoryMock;

    protected \PHPUnit\Framework\MockObject\Stub $jwsSerializerManagerDecoratorMock;

    protected \PHPUnit\Framework\MockObject\Stub $dateIntervalDecoratorMock;

    protected MockObject $helpersMock;

    protected MockObject $jsonHelperMock;

    protected \PHPUnit\Framework\MockObject\Stub $claimFactoryMock;

    protected array $validPayload;


    protected function setUp(): void
    {
        $jwsMock = $this->createMock(JWS::class);
        $jwsMock->method('getPayload')
            ->willReturn('json-payload-string');

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

        $typeHelperMock->method('ensureNonEmptyString')->willReturnArgument(0);
        $typeHelperMock->method('ensureInt')->willReturnArgument(0);
        $typeHelperMock->method('enforceUri')->willReturnArgument(0);
        $typeHelperMock->method('ensureArrayWithValuesAsStrings')->willReturnArgument(0);

        $this->claimFactoryMock = $this->createStub(ClaimFactory::class);

        $this->validPayload = [
            'iss' => 'https://server.example.com',
            'sub' => '24400320',
            'aud' => 's6BhdRkqt3',
            'iat' => time(),
            'exp' => time() + 3600,
            'jti' => 'bWJq',
            'events' => [
                'http://schemas.openid.net/event/backchannel-logout' => (object) [],
            ],
        ];
    }


    protected function sut(
        ?JwsDecorator $jwsDecorator = null,
        ?JwsVerifierDecorator $jwsVerifierDecorator = null,
        ?JwksDecoratorFactory $jwksDecoratorFactory = null,
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
        ?ClaimFactory $claimFactory = null,
    ): LogoutToken {
        $jwsDecorator ??= $this->jwsDecoratorMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksDecoratorFactory ??= $this->jwksDecoratorFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;
        $claimFactory ??= $this->claimFactoryMock;

        return new LogoutToken(
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
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);
        $this->assertInstanceOf(LogoutToken::class, $this->sut());
    }


    public function testCanGetRequiredClaims(): void
    {
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);
        $sut = $this->sut();

        $this->assertSame($this->validPayload['iss'], $sut->getIssuer());
        $this->assertSame([$this->validPayload['aud']], $sut->getAudience());
        $this->assertSame($this->validPayload['iat'], $sut->getIssuedAt());
        $this->assertSame($this->validPayload['exp'], $sut->getExpirationTime());
        $this->assertSame($this->validPayload['jti'], $sut->getJwtId());
        $this->assertSame($this->validPayload['events'], $sut->getEvents());
    }


    public function testGetIssuerThrowsWhenMissing(): void
    {
        $payload = $this->validPayload;
        unset($payload['iss']);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('No Issuer claim found.');

        $this->sut();
    }


    public function testGetAudienceThrowsWhenMissing(): void
    {
        $payload = $this->validPayload;
        unset($payload['aud']);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('No Audience claim found.');

        $this->sut();
    }


    public function testGetIssuedAtThrowsWhenMissing(): void
    {
        $payload = $this->validPayload;
        unset($payload['iat']);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('No Issued At claim found.');

        $this->sut();
    }


    public function testGetExpirationTimeThrowsWhenMissing(): void
    {
        $payload = $this->validPayload;
        unset($payload['exp']);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('No Expiration Time claim found.');

        $this->sut();
    }


    public function testGetJwtIdThrowsWhenMissing(): void
    {
        $payload = $this->validPayload;
        unset($payload['jti']);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('No JWT ID claim found.');

        $this->sut();
    }


    public function testGetEventsThrowsWhenMissing(): void
    {
        $payload = $this->validPayload;
        unset($payload['events']);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('No Events claim found.');

        $this->sut();
    }


    public function testGetEventsThrowsWhenMalformed(): void
    {
        $payload = $this->validPayload;
        $payload['events'] = ['wrong-event' => []];
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Malformed events claim.');

        $this->sut();
    }


    public function testCanGetSessionId(): void
    {
        $payload = $this->validPayload;
        $payload['sid'] = 'session-id';
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->assertSame('session-id', $this->sut()->getSessionId());
    }


    public function testGetNonceThrowsWhenPresent(): void
    {
        $payload = $this->validPayload;
        $payload['nonce'] = 'some-nonce';
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Nonce claim is forbidden in Logout Token.');

        $this->sut();
    }


    public function testThrowsWhenBothSubAndSidAreMissing(): void
    {
        $payload = $this->validPayload;
        unset($payload['sub']);
        unset($payload['sid']);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Missing Subject and Session ID claim in Logout Token.');

        $this->sut();
    }


    public function testDoesNotThrowWhenSubIsMissingButSidIsPresent(): void
    {
        $payload = $this->validPayload;
        unset($payload['sub']);
        $payload['sid'] = 'session-id';
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->assertInstanceOf(LogoutToken::class, $this->sut());
    }


    public function testDoesNotThrowWhenSidIsMissingButSubIsPresent(): void
    {
        $payload = $this->validPayload;
        unset($payload['sid']);
        // sub is already in validPayload
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->assertInstanceOf(LogoutToken::class, $this->sut());
    }
}
