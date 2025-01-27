<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jwks;

use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jwks\SignedJwks;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(SignedJwks::class)]
#[UsesClass(ParsedJws::class)]
class SignedJwksTest extends TestCase
{
    protected MockObject $signatureMock;
    protected MockObject $jwsMock;
    protected MockObject $jwsDecoratorMock;
    protected MockObject $jwsVerifierDecoratorMock;
    protected MockObject $jwksFactoryMock;
    protected MockObject $jwsSerializerManagerDecoratorMock;
    protected MockObject $dateIntervalDecoratorMock;
    protected MockObject $helpersMock;
    protected MockObject $jsonHelperMock;
    protected MockObject $typeHelperMock;

    protected array $sampleHeader = [
        'alg' => 'RS256',
        'typ' => 'jwk-set+jwt',
        'kid' => 'LfgZECDYkSTHmbllBD5_Tkwvy3CtOpNYQ7-DfQawTww',
    ];

    protected array $expiredPayload = [
        'iat' => 1731175727,
        'nbf' => 1731175727,
        'exp' => 1731175727,
        'iss' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/',
        'sub' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/',
        'keys' => [
            [
                'alg' => 'RS256',
                'use' => 'sig',
                'kty' => 'RSA',
                // phpcs:ignore
                'n' => 'pJgG9F_lwc2cFEC1l6q0fjJYxKPbtVGqJpDggDpDR8MgfbH0jUZP_RvhJGpl_09Bp-PfibLiwxchHZlrCx-fHQyGMaBRivUfq_p12ECEXMaFUcasCP6cyNrDfa5Uchumau4WeC21nYI1NMawiMiWFcHpLCQ7Ul8NMaCM_dkeruhm_xG0ZCqfwu30jOyCsnZdE0izJwPTfBRLpLyivu8eHpwjoIzmwqo8H-ZsbqR0vdRu20-MNS78ppTxwK3QmJhU6VO2r730F6WH9xJd_XUDuVeM4_6Z6WVDXw3kQF-jlpfcssPP303nbqVmfFZSUgS8buToErpMqevMIKREShsjMQ',
                'e' => 'AQAB',
                'kid' => 'F4VFObNusj3PHmrHxpqh4GNiuFHlfh-2s6xMJ95fLYA',
            ],
        ],
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

        $this->jwsVerifierDecoratorMock = $this->createMock(JwsVerifierDecorator::class);
        $this->jwksFactoryMock = $this->createMock(JwksFactory::class);
        $this->jwsSerializerManagerDecoratorMock = $this->createMock(JwsSerializerManagerDecorator::class);
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
        ?JwsDecorator $jwsDecorator = null,
        ?JwsVerifierDecorator $jwsVerifierDecorator = null,
        ?JwksFactory $jwksFactory = null,
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
    ): SignedJwks {
        $jwsDecorator ??= $this->jwsDecoratorMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksFactory ??= $this->jwksFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;

        return new SignedJwks(
            $jwsDecorator,
            $jwsVerifierDecorator,
            $jwksFactory,
            $jwsSerializerManagerDecorator,
            $dateIntervalDecorator,
            $helpers,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->assertInstanceOf(
            SignedJwks::class,
            $this->sut(),
        );
    }

    public function testCanGetJsonSerialize(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->assertIsArray($this->sut()->jsonSerialize());
    }
}
