<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Core;

use Jose\Component\Signature\JWS;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Core\IdToken;
use SimpleSAML\OpenID\Core\IdTokenHint;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(IdTokenHint::class)]
#[UsesClass(IdToken::class)]
#[UsesClass(ParsedJws::class)]
final class IdTokenHintTest extends TestCase
{
    protected MockObject $jwsDecoratorMock;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\SimpleSAML\OpenID\Jws\JwsVerifierDecorator
     */
    protected \PHPUnit\Framework\MockObject\Stub $jwsVerifierDecoratorMock;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory
     */
    protected \PHPUnit\Framework\MockObject\Stub $jwksDecoratorFactoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator
     */
    protected \PHPUnit\Framework\MockObject\Stub $jwsSerializerManagerDecoratorMock;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\SimpleSAML\OpenID\Decorators\DateIntervalDecorator
     */
    protected \PHPUnit\Framework\MockObject\Stub $dateIntervalDecoratorMock;

    protected MockObject $helpersMock;

    protected MockObject $jsonHelperMock;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\SimpleSAML\OpenID\Factories\ClaimFactory
     */
    protected \PHPUnit\Framework\MockObject\Stub $claimFactoryMock;

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


    protected function setUp(): void
    {
        $jwsMock = $this->createMock(JWS::class);
        $jwsMock->method('getPayload')
            ->willReturn('json-payload-string'); // Just so we have non-empty value.

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
        $typeHelperMock->method('ensureArrayWithValuesAsNonEmptyStrings')->willReturnArgument(0);
        $typeHelperMock->method('enforceUri')->willReturnArgument(0);
        $typeHelperMock->method('ensureArrayWithKeysAndValuesAsNonEmptyStrings')
            ->willReturnArgument(0);

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
    ): IdTokenHint {
        $jwsDecorator ??= $this->jwsDecoratorMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksDecoratorFactory ??= $this->jwksDecoratorFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;
        $claimFactory ??= $this->claimFactoryMock;

        return new IdTokenHint(
            $jwsDecorator,
            $jwsVerifierDecorator,
            $jwksDecoratorFactory,
            $jwsSerializerManagerDecorator,
            $dateIntervalDecorator,
            $helpers,
            $claimFactory,
        );
    }


    public function testIsAnIdToken(): void
    {
        $this->jsonHelperMock->method('decode')->willReturn($this->expiredPayload);
        $this->assertInstanceOf(IdToken::class, $this->sut());
    }


    public function testCanBeCreatedFromExpiredToken(): void
    {
        $this->jsonHelperMock->method('decode')->willReturn($this->expiredPayload);
        $this->assertInstanceOf(IdTokenHint::class, $this->sut());
    }


    public function testDoesNotValidateExpiration(): void
    {
        $this->jsonHelperMock->method('decode')->willReturn($this->expiredPayload);
        $sut = $this->sut();

        // The (past) Expiration Time is readable, it is just not validated against the current time.
        $this->assertSame($this->expiredPayload['exp'], $sut->getExpirationTime());
        // Issued At is in the past, so it remains valid and readable.
        $this->assertSame($this->expiredPayload['iat'], $sut->getIssuedAt());
    }


    public function testStillValidatesFutureIssuedAt(): void
    {
        $payload = $this->expiredPayload;
        $payload['iat'] = time() + 3600;
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Issued At');

        $this->sut();
    }


    public function testStillValidatesFutureNotBefore(): void
    {
        $payload = $this->expiredPayload;
        $payload['nbf'] = time() + 3600;
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Not Before');

        $this->sut();
    }


    public function testCanGetPayloadClaims(): void
    {
        $this->jsonHelperMock->method('decode')->willReturn($this->expiredPayload);
        $sut = $this->sut();

        $this->assertSame($this->expiredPayload['iss'], $sut->getIssuer());
        $this->assertSame($this->expiredPayload['sub'], $sut->getSubject());
        $this->assertContains($this->expiredPayload['aud'], $sut->getAudience());
        $this->assertSame($this->expiredPayload['nonce'], $sut->getNonce());
        $this->assertSame($this->expiredPayload['auth_time'], $sut->getAuthTime());
        $this->assertSame($this->expiredPayload['acr'], $sut->getAuthenticationContextClassReference());
        $this->assertSame($this->expiredPayload['amr'], $sut->getAuthenticationMethodsReferences());
    }
}
