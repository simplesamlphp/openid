<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\SdJwt\Factories;

use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\HashAlgorithmsEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\SdJwt\Disclosure;
use SimpleSAML\OpenID\SdJwt\DisclosureBag;
use SimpleSAML\OpenID\SdJwt\Factories\DisclosureFactory;
use SimpleSAML\OpenID\SdJwt\Factories\SdJwtFactory;
use SimpleSAML\OpenID\SdJwt\SdJwt;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(SdJwtFactory::class)]
#[UsesClass(ParsedJwsFactory::class)]
#[UsesClass(ParsedJws::class)]
#[UsesClass(SdJwt::class)]
#[UsesClass(Disclosure::class)]
#[UsesClass(DisclosureBag::class)]
#[UsesClass(HashAlgorithmsEnum::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Arr::class)]
#[UsesClass(Helpers\Base64Url::class)]
#[UsesClass(Helpers\Json::class)]
#[UsesClass(Helpers\Hash::class)]
#[UsesClass(Helpers\Type::class)]
#[UsesClass(Helpers\Random::class)]
#[UsesClass(DisclosureFactory::class)]
final class SdJwtFactoryTest extends TestCase
{
    protected MockObject $jwsDecoratorBuilderMock;

    protected \PHPUnit\Framework\MockObject\Stub $jwsVerifierDecoratorMock;

    protected \PHPUnit\Framework\MockObject\Stub $jwksDecoratorFactoryMock;

    protected \PHPUnit\Framework\MockObject\Stub $jwsSerializerManagerDecoratorMock;

    protected \PHPUnit\Framework\MockObject\Stub $dateIntervalDecoratorMock;

    protected MockObject $helpersMock;

    protected \PHPUnit\Framework\MockObject\Stub $claimFactoryMock;

    protected \PHPUnit\Framework\MockObject\Stub $disclosureFactoryMock;

    protected array $sampleHeader = [
        'alg' => 'ES256',
        'typ' => 'example+sd-jwt',
        'kid' => 'F4VFObNusj3PHmrHxpqh4GNiuFHlfh-2s6xMJ95fLYA',
    ];

    protected array $expiredPayload = [
        'iat' => 1734009487,
        'nbf' => 1734009487,
        'exp' => 1734009487,
        'iss' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/',

    ];

    protected array $validPayload;


    protected function setUp(): void
    {
        $jwsMock = $this->createMock(JWS::class);
        $jwsMock->method('getPayload')
            ->willReturn('json-payload-string'); // Just so we have non-empty value.
        $jwsMock->method('getSignature')->willReturn($this->createStub(Signature::class));

        $jwsDecoratorMock = $this->createMock(JwsDecorator::class);
        $jwsDecoratorMock->method('jws')->willReturn($jwsMock);

        $this->jwsDecoratorBuilderMock = $this->createMock(JwsDecoratorBuilder::class);
        $this->jwsDecoratorBuilderMock->method('fromToken')->willReturn($jwsDecoratorMock);

        $this->jwsVerifierDecoratorMock = $this->createStub(JwsVerifierDecorator::class);
        $this->jwksDecoratorFactoryMock = $this->createStub(JwksDecoratorFactory::class);
        $this->jwsSerializerManagerDecoratorMock = $this->createStub(JwsSerializerManagerDecorator::class);
        $this->dateIntervalDecoratorMock = $this->createStub(DateIntervalDecorator::class);

        $this->helpersMock = $this->createMock(Helpers::class);
        $this->helpersMock->method('json')->willReturn($this->createStub(Helpers\Json::class));
        $typeHelperMock = $this->createMock(Helpers\Type::class);
        $this->helpersMock->method('type')->willReturn($typeHelperMock);

        $typeHelperMock->method('ensureNonEmptyString')->willReturnArgument(0);
        $typeHelperMock->method('ensureInt')->willReturnArgument(0);

        $this->claimFactoryMock = $this->createStub(ClaimFactory::class);

        $this->validPayload = $this->expiredPayload;
        $this->validPayload['exp'] = time() + 3600;

        $this->disclosureFactoryMock = $this->createStub(DisclosureFactory::class);
    }


    protected function sut(
        ?JwsDecoratorBuilder $jwsDecoratorBuilder = null,
        ?JwsVerifierDecorator $jwsVerifierDecorator = null,
        ?JwksDecoratorFactory $jwksDecoratorFactory = null,
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
        ?ClaimFactory $claimFactory = null,
        ?DisclosureFactory $disclosureFactory = null,
    ): SdJwtFactory {
        $jwsDecoratorBuilder ??= $this->jwsDecoratorBuilderMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksDecoratorFactory ??= $this->jwksDecoratorFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;
        $claimFactory ??= $this->claimFactoryMock;
        $disclosureFactory ??= $this->disclosureFactoryMock;

        return new SdJwtFactory(
            $jwsDecoratorBuilder,
            $jwsVerifierDecorator,
            $jwksDecoratorFactory,
            $jwsSerializerManagerDecorator,
            $dateIntervalDecorator,
            $helpers,
            $claimFactory,
            $disclosureFactory,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(SdJwtFactory::class, $this->sut());
    }


    public function testCanBuildFromData(): void
    {
        $this->assertInstanceOf(
            SdJwt::class,
            $this->sut()->fromData(
                $this->createStub(\SimpleSAML\OpenID\Jwk\JwkDecorator::class),
                SignatureAlgorithmEnum::ES256,
                $this->validPayload,
                $this->sampleHeader,
                $this->createStub(\SimpleSAML\OpenID\SdJwt\DisclosureBag::class),
            ),
        );
    }


    public function testCanUpdatePayloadWithDisclosures(): void
    {
        // ["_26bc4LT-ac6q2KI6cBW5es","family_name","Möbius"]
        $helpers = new Helpers();
        $disclosureFactory = new DisclosureFactory($helpers);

        $disclosure = $disclosureFactory->build(
            value: 'Möbius',
            name: 'family_name',
            salt: '_26bc4LT-ac6q2KI6cBW5es',
        );

        $disclosureBag = new DisclosureBag($disclosure);

        $sut = $this->sut(helpers: $helpers, disclosureFactory: $disclosureFactory);

        $payload = [
            'given_name' => 'John',
        ];

        $payload = $sut->updatePayloadWithDisclosures(
            $payload,
            $disclosureBag,
            HashAlgorithmsEnum::SHA_256,
        );

        $this->assertArrayHasKey(ClaimsEnum::_SdAlg->value, $payload);
        $this->assertArrayHasKey(ClaimsEnum::_Sd->value, $payload);
        $this->assertNotEmpty($payload[ClaimsEnum::_Sd->value]);
        $this->assertContains('TZjouOTrBKEwUNjNDs9yeMzBoQn8FFLPaJjRRmAtwrM', $payload[ClaimsEnum::_Sd->value]);
    }
}
