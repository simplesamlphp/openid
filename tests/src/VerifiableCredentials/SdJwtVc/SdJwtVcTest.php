<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\SdJwtVc;

use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\SdJwt\Disclosure;
use SimpleSAML\OpenID\SdJwt\DisclosureBag;
use SimpleSAML\OpenID\SdJwt\KbJwt;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\VerifiableCredentials\SdJwtVc\SdJwtVc;

#[CoversClass(SdJwtVc::class)]
final class SdJwtVcTest extends TestCase
{
    protected MockObject $signatureMock;

    protected MockObject $jwsDecoratorMock;

    protected MockObject $jwsVerifierDecoratorMock;

    protected MockObject $jwksDecoratorFactoryMock;

    protected MockObject $jwsSerializerManagerDecoratorMock;

    protected MockObject $dateIntervalDecoratorMock;

    protected MockObject $helpersMock;

    protected MockObject $jsonHelperMock;

    protected MockObject $arrHelperMock;

    protected MockObject $claimFactoryMock;

    protected array $expiredPayload = [
        "_sd" => [
            "09vKrJMOlyTWM0sjpu_pdOBVBQ2M1y3KhpH515nXkpY",
            "2rsjGbaC0ky8mT0pJrPioWTq0_daw1sX76poUlgCwbI",
            "EkO8dhW0dHEJbvUHlE_VCeuC9uRELOieLZhh7XbUTtA",
            "IlDzIKeiZdDwpqpK6ZfbyphFvz5FgnWa-sN6wqQXCiw",
            "JzYjH4svliH0R3PyEMfeZu6Jt69u5qehZo7F7EPYlSE",
            "PorFbpKuVu6xymJagvkFsFXAbRoc2JGlAUA2BA4o7cI",
            "TGf4oLbgwd5JQaHyKVQZU9UdGE0w5rtDsrZzfUaomLo",
            "jdrTE8YcbY4EifugihiAe_BPekxJQZICeiUQwY9QqxI",
            "jsu9yVulwQQlhFlM_3JlzMaSFzglhQG0DpfayQwLUK4",
        ],
        "iss" => "https://example.com/issuer",
        "iat" => 1683000000,
        "exp" => 1883000000,
        "vct" => "https://credentials.example.com/identity_credential",
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
        'alg' => 'ES256',
        'typ' => 'dc+sd-jwt',
        'kid' => 'F4VFObNusj3PHmrHxpqh4GNiuFHlfh-2s6xMJ95fLYA',
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
        $this->arrHelperMock = $this->createMock(Helpers\Arr::class);
        $this->helpersMock->method('arr')->willReturn($this->arrHelperMock);

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
        DisclosureBag|false|null $disclosureBag = null,
        KbJwt|false|null $kbJwt = null,
    ): SdJwtVc {
        $jwsDecorator ??= $this->jwsDecoratorMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksDecoratorFactory ??= $this->jwksDecoratorFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;
        $claimFactory ??= $this->claimFactoryMock;
        $disclosureBag = $disclosureBag === false ? null : $disclosureBag ?? $this->disclosureBagMock;
        $kbJwt = $kbJwt === false ? null : $kbJwt ?? $this->kbJwtMock;

        return new SdJwtVc(
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
            SdJwtVc::class,
            $this->sut(),
        );
    }


    public function testCanCreateInstanceWithoutDisclosureBag(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->assertInstanceOf(
            SdJwtVc::class,
            $this->sut(disclosureBag: false),
        );
    }


    public function testThrowsOnInvalidTypeHeader(): void
    {
        $this->sampleHeader['typ'] = 'invalid-type';
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Type');
        $this->sut();
    }


    public function testThrowsOnNonDisclosableClaimCase(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $disclosureMock = $this->createMock(Disclosure::class);
        $disclosureMock->method('getName')->willReturn('iss');
        $this->disclosureBagMock->method('all')->willReturn(['salt' => $disclosureMock]);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('not selectively disclosable');
        $this->expectExceptionMessage('iss');
        $this->sut();
    }


    public function testThrowsOnSdClaimWhenNoDisclosures(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);

        $this->arrHelperMock->method('containsKey')->willReturn(true);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('_Sd');
        $this->sut();
    }
}
