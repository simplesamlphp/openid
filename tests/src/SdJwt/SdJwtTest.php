<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\SdJwt;

use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\HashAlgorithmsEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\SdJwt\Disclosure;
use SimpleSAML\OpenID\SdJwt\DisclosureBag;
use SimpleSAML\OpenID\SdJwt\KbJwt;
use SimpleSAML\OpenID\SdJwt\SdJwt;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[\PHPUnit\Framework\Attributes\CoversClass(SdJwt::class)]
final class SdJwtTest extends TestCase
{
    protected MockObject $signatureMock;

    protected MockObject $jwsDecoratorMock;

    protected MockObject $jwsVerifierDecoratorMock;

    protected MockObject $jwksDecoratorFactoryMock;

    protected MockObject $jwsSerializerManagerDecoratorMock;

    protected MockObject $dateIntervalDecoratorMock;

    protected MockObject $helpersMock;

    protected MockObject $jsonHelperMock;

    protected MockObject $claimFactoryMock;

    protected array $expiredPayload = [
        "_sd" => [
            "CrQe7S5kqBAHt-nMYXgc6bdt2SH5aTY1sU_M-PgkjPI",
            "JzYjH4svliH0R3PyEMfeZu6Jt69u5qehZo7F7EPYlSE",
            "PorFbpKuVu6xymJagvkFsFXAbRoc2JGlAUA2BA4o7cI",
            "TGf4oLbgwd5JQaHyKVQZU9UdGE0w5rtDsrZzfUaomLo",
            "XQ_3kPKt1XyX7KANkqVR6yZ2Va5NrPIvPYbyMvRKBMM",
            "XzFrzwscM6Gn6CJDc6vVK8BkMnfG8vOSKfpPIZdAfdE",
            "gbOsI4Edq2x2Kw-w5wPEzakob9hV1cRD0ATN3oQL9JM",
            "jsu9yVulwQQlhFlM_3JlzMaSFzglhQG0DpfayQwLUK4",
        ],
        "iss" => "https://issuer.example.com",
        "iat" => 1683000000,
        "exp" => 1883000000,
        "sub" => "user_42",
        "nationalities" => [
            [
                "..." => "pFndjkZ_VCzmyTa6UjlZo3dh-ko8aIKQc9DlGzhaVYo",
            ],
            [
                "..." => "7Cf6JkPudry3lcbwHgeZ8khAv1U1OSlerP0VkBJrWZ0",
            ],
        ],
        "_sd_alg" => "sha-256",
        "cnf" => [
            "jwk" => [
                "kty" => "EC",
                "crv" => "P-256",
                "x" => "TCAER19Zvu3OHF4j4W4vfSVoHIP1ILilDls7vCeGemc",
                "y" => "ZxjiWWbZMQGHVWKVQ4hbSIirsVfuecCE6t4jT9F2HZQ",
            ],
        ],
    ];

    protected array $sampleHeader = [
        'alg' => 'RS256',
        'typ' => 'example+sd-jwt',
        'kid' => 'fsQ45F0D916RdKEeTjta8DYWiodjthouHrVWgOXBrkk',
    ];

    protected array $validPayload;

    protected MockObject $disclosureBagMock;

    protected MockObject $kbJwtMock;


    protected function setUp(): void
    {
        $this->signatureMock = $this->createMock(Signature::class);

        $jwsMock = $this->createMock(JWS::class);
        $jwsMock->method('getPayload')
            ->willReturn('json-payload-string'); // Just so we have non-empty value.
        $jwsMock->method('getSignature')->willReturn($this->signatureMock);

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
        $typeHelperMock->method('ensureArray')->willReturnArgument(0);

        $this->claimFactoryMock = $this->createMock(ClaimFactory::class);

        $this->validPayload = $this->expiredPayload;
        $this->validPayload['exp'] = time() + 3600;

        $this->disclosureBagMock = $this->createMock(DisclosureBag::class);
        $this->kbJwtMock = $this->createMock(KbJwt::class);
    }


    protected function sut(
        ?JwsDecorator $jwsDecorator = null,
        ?JwsVerifierDecorator $jwsVerifierDecorator = null,
        ?JwksDecoratorFactory $jwksDecoratorFactory = null,
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
        ?ClaimFactory $claimFactory = null,
        ?DisclosureBag $disclosureBag = null,
        ?KbJwt $kbJwt = null,
    ): SdJwt {
        $jwsDecorator ??= $this->jwsDecoratorMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksDecoratorFactory ??= $this->jwksDecoratorFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;
        $claimFactory ??= $this->claimFactoryMock;
        $disclosureBag ??= $this->disclosureBagMock;
        $kbJwt ??= $this->kbJwtMock;

        return new SdJwt(
            $jwsDecorator,
            $jwsVerifierDecorator,
            $jwksDecoratorFactory,
            $jwsSerializerManagerDecorator,
            $dateIntervalDecorator,
            $helpers,
            $claimFactory,
            $disclosureBag,
            $kbJwt,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->assertInstanceOf(
            SdJwt::class,
            $this->sut(),
        );
    }


    public function testCanGetCommonProperties(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $sut = $this->sut();

        $this->assertInstanceOf(
            HashAlgorithmsEnum::class,
            $sut->getSelectiveDisclosureAlgorithm(),
        );
        $this->assertSame(
            $this->validPayload['cnf'],
            $sut->getConfirmation(),
        );
        $this->assertInstanceOf(
            DisclosureBag::class,
            $sut->getDisclosureBag(),
        );
        $this->assertInstanceOf(
            KbJwt::class,
            $sut->getKbJwt(),
        );
    }


    public function testCanGetNullableCommonProperties(): void
    {
        $payload = $this->validPayload;
        unset($payload['_sd_alg']);
        unset($payload['cnf']);

        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $sut = $this->sut();

        $this->assertNotInstanceOf(HashAlgorithmsEnum::class, $sut->getSelectiveDisclosureAlgorithm());
        $this->assertNull($sut->getConfirmation());
    }


    public function testCanGetToken(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $disclosureMock = $this->createMock(Disclosure::class);
        $disclosureMock->method('getSalt')->willReturn('salt');
        $this->disclosureBagMock->method('all')->willReturn(['salt' => $disclosureMock]);

        // Since we don't do full token generation in tests, just make sure we have the expected number of tildes.
        $this->assertSame("~~", $this->sut()->getToken());
    }


    public function testCanGetTokenWithFilteredDisclosures(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $disclosureMock = $this->createMock(Disclosure::class);
        $disclosureMock->method('getSalt')->willReturn('salt');
        $disclosureMock2 = $this->createMock(Disclosure::class);
        $disclosureMock2->method('getSalt')->willReturn('salt2');
        $this->disclosureBagMock->method('all')->willReturn(
            ['salt' => $disclosureMock, 'salt2' => $disclosureMock2],
        );
        $disclosureBagMock2 = $this->createMock(DisclosureBag::class);
        $disclosureBagMock2->method('all')->willReturn(['salt2' => $disclosureMock2]);

        // Since we don't do full token generation in tests, just make sure we have the expected number of tildes.
        $this->assertSame("~~~", $this->sut()->getToken());
        $this->assertSame("~~", $this->sut()->getToken(disclosureBag: $disclosureBagMock2));
    }


    public function testCanGetUndisclosedToken(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        // We don't do full token generation in tests, so we expect an empty string here.
        $this->assertEmpty($this->sut()->getUndisclosedToken());
    }
}
