<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Core\Factories;

use Jose\Component\Signature\JWS;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Core\Factories\IdTokenHintFactory;
use SimpleSAML\OpenID\Core\IdToken;
use SimpleSAML\OpenID\Core\IdTokenHint;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(IdTokenHintFactory::class)]
#[UsesClass(ParsedJwsFactory::class)]
#[UsesClass(ParsedJws::class)]
#[UsesClass(IdToken::class)]
#[UsesClass(IdTokenHint::class)]
final class IdTokenHintFactoryTest extends TestCase
{
    protected MockObject $jwsDecoratorBuilderMock;

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

        $jwsDecoratorMock = $this->createMock(JwsDecorator::class);
        $jwsDecoratorMock->method('jws')->willReturn($jwsMock);

        $this->jwsDecoratorBuilderMock = $this->createMock(JwsDecoratorBuilder::class);
        $this->jwsDecoratorBuilderMock->method('fromToken')->willReturn($jwsDecoratorMock);
        $this->jwsDecoratorBuilderMock->method('fromData')->willReturn($jwsDecoratorMock);

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
        ?JwsDecoratorBuilder $jwsDecoratorBuilder = null,
        ?JwsVerifierDecorator $jwsVerifierDecorator = null,
        ?JwksDecoratorFactory $jwksDecoratorFactory = null,
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
        ?ClaimFactory $claimFactory = null,
    ): IdTokenHintFactory {
        $jwsDecoratorBuilder ??= $this->jwsDecoratorBuilderMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksDecoratorFactory ??= $this->jwksDecoratorFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;
        $claimFactory ??= $this->claimFactoryMock;

        return new IdTokenHintFactory(
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
        $this->assertInstanceOf(IdTokenHintFactory::class, $this->sut());
    }


    public function testCanBuildFromExpiredToken(): void
    {
        $this->jsonHelperMock->method('decode')->willReturn($this->expiredPayload);

        $this->assertInstanceOf(
            IdTokenHint::class,
            $this->sut()->fromToken('token'),
        );
    }


    public function testCanBuildFromExpiredData(): void
    {
        $this->jsonHelperMock->method('decode')->willReturn($this->expiredPayload);

        $this->assertInstanceOf(
            IdTokenHint::class,
            $this->sut()->fromData(
                $this->createStub(\SimpleSAML\OpenID\Jwk\JwkDecorator::class),
                SignatureAlgorithmEnum::ES256,
                $this->expiredPayload,
                [],
            ),
        );
    }
}
