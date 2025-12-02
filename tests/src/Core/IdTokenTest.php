<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Core;

use Jose\Component\Signature\JWS;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Core\IdToken;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(IdToken::class)]
final class IdTokenTest extends TestCase
{
    protected MockObject $jwsDecoratorMock;

    protected MockObject $jwsVerifierDecoratorMock;

    protected MockObject $jwksDecoratorFactoryMock;

    protected MockObject $jwsSerializerManagerDecoratorMock;

    protected MockObject $dateIntervalDecoratorMock;

    protected MockObject $helpersMock;

    protected MockObject $jsonHelperMock;

    protected MockObject $claimFactoryMock;

    protected array $expiredPayload = [
        'iss' => 'https://server.example.com',
        'sub' => '24400320',
        'aud' => 's6BhdRkqt3',
        'nonce' => 'n-0S6_WzA2Mj',
        'exp' => 1311281970,
        'iat' => 1311280970,
        'auth_time' => 1311280969,
        'acr' => 'urn:mace:incommon:iap:silver',
        'amr' => ['otp'],
    ];

    protected array $validPayload;


    protected function setUp(): void
    {
        $jwsMock = $this->createMock(JWS::class);
        $jwsMock->method('getPayload')
            ->willReturn('json-payload-string'); // Just so we have non-empty value.

        $this->jwsDecoratorMock = $this->createMock(JwsDecorator::class);
        $this->jwsDecoratorMock->method('jws')->willReturn($jwsMock);

        $this->jwsVerifierDecoratorMock = $this->createMock(JwsVerifierDecorator::class);
        $this->jwksDecoratorFactoryMock = $this->createMock(JwksDecoratorFactory::class);
        $this->jwsSerializerManagerDecoratorMock = $this->createMock(JwsSerializerManagerDecorator::class);
        $this->dateIntervalDecoratorMock = $this->createMock(DateIntervalDecorator::class);

        $this->helpersMock = $this->createMock(Helpers::class);
        $this->jsonHelperMock = $this->createMock(Helpers\Json::class);
        $this->helpersMock->method('json')->willReturn($this->jsonHelperMock);
        $typeHelperMock = $this->createMock(Helpers\Type::class);
        $this->helpersMock->method('type')->willReturn($typeHelperMock);

        $typeHelperMock->method('ensureNonEmptyString')->willReturnArgument(0);
        $typeHelperMock->method('ensureInt')->willReturnArgument(0);
        $typeHelperMock->method('ensureArrayWithValuesAsNonEmptyStrings')->willReturnArgument(0);
        $typeHelperMock->method('enforceUri')->willReturnArgument(0);
        $typeHelperMock->method('ensureArrayWithKeysAndValuesAsNonEmptyStrings')
            ->willReturnArgument(0);

        $this->claimFactoryMock = $this->createMock(ClaimFactory::class);

        $this->validPayload = $this->expiredPayload;
        $this->validPayload['exp'] = time() + 3600;
    }


    protected function sut(
        ?JwsDecorator $jwsDecorator = null,
        ?JwsVerifierDecorator $jwsVerifierDecorator = null,
        ?JwksDecoratorFactory $jwksDecoratorFactory = null,
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
        ?ClaimFactory $claimFactory = null,
    ): IdToken {
        $jwsDecorator ??= $this->jwsDecoratorMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksDecoratorFactory ??= $this->jwksDecoratorFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;
        $claimFactory ??= $this->claimFactoryMock;

        return new IdToken(
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
        $this->assertInstanceOf(IdToken::class, $this->sut());
    }


    public function testCanGetPayloadClaims(): void
    {
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);
        $sut = $this->sut();

        $this->assertSame($this->validPayload['iss'], $sut->getIssuer());
        $this->assertSame($this->validPayload['sub'], $sut->getSubject());
        $this->assertContains($this->validPayload['aud'], $sut->getAudience());
        $this->assertSame($this->validPayload['nonce'], $sut->getNonce());
        $this->assertSame($this->validPayload['exp'], $sut->getExpirationTime());
        $this->assertSame($this->validPayload['iat'], $sut->getIssuedAt());
        $this->assertSame($this->validPayload['auth_time'], $sut->getAuthTime());
        $this->assertSame($this->validPayload['acr'], $sut->getAuthenticationContextClassReference());
        $this->assertSame($this->validPayload['amr'], $sut->getAuthenticationMethodsReferences());
    }


    public function testGetSubjectThrowsOnInvalidAscii(): void
    {
        $payload = $this->validPayload;
        $payload['sub'] = 'Ivančić';

        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('non-ASCII');

        $this->sut()->getSubject();
    }


    public function testGetSubjectThrowsOnMoreThan255Chars(): void
    {
        $payload = $this->validPayload;
        $payload['sub'] = str_repeat('a', 256);

        $this->jsonHelperMock->method('decode')->willReturn($payload);
        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('255');

        $this->sut()->getSubject();
    }


    public function testCanGetOptionalClaims(): void
    {
        $payload = $this->validPayload;
        $payload['azp'] = 'azp-value';
        $payload['at_hash'] = 'at_hash-value';
        $payload['c_hash'] = 'c_hash-value';
        $payload['sub_jwk'] = ['kty' => 'RSA'];

        $this->jsonHelperMock->method('decode')->willReturn($payload);
        $sut = $this->sut();

        $this->assertSame($payload['azp'], $sut->getAuthorizedParty());
        $this->assertSame($payload['at_hash'], $sut->getAccessTokenHash());
        $this->assertSame($payload['c_hash'], $sut->getCodeHash());
        $this->assertSame($payload['sub_jwk'], $sut->getSubJwk());
    }


    public function testCanGetOptionalClaimsAsNull(): void
    {
        $payload = $this->validPayload;
        unset($payload['auth_time']);
        unset($payload['nonce']);
        unset($payload['acr']);
        unset($payload['amr']);

        $this->jsonHelperMock->method('decode')->willReturn($payload);
        $sut = $this->sut();

        $this->assertNull($sut->getAuthTime());
        $this->assertNull($sut->getNonce());
        $this->assertNull($sut->getAuthenticationContextClassReference());
        $this->assertNull($sut->getAuthenticationMethodsReferences());

        $this->assertNull($sut->getAuthorizedParty());
        $this->assertNull($sut->getAccessTokenHash());
        $this->assertNull($sut->getCodeHash());
        $this->assertNull($sut->getSubJwk());
    }
}
