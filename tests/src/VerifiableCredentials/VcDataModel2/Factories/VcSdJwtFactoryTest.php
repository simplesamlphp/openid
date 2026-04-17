<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\VerifiableCredentials\VcDataModel2\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\JwtTypesEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\SdJwt\DisclosureBag;
use SimpleSAML\OpenID\SdJwt\Factories\DisclosureFactory;
use SimpleSAML\OpenID\SdJwt\Factories\SdJwtFactory;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\Factories\VcSdJwtFactory;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel2\VcSdJwt;

#[CoversClass(VcSdJwtFactory::class)]
#[UsesClass(SdJwtFactory::class)]
#[UsesClass(VcSdJwt::class)]
#[UsesClass(ParsedJws::class)]
#[UsesClass(DateIntervalDecorator::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\DateTime::class)]
#[UsesClass(Helpers\Json::class)]
#[UsesClass(Helpers\Type::class)]
final class VcSdJwtFactoryTest extends TestCase
{
    protected JwsDecoratorBuilder&MockObject $jwsDecoratorBuilderMock;

    protected \PHPUnit\Framework\MockObject\Stub&JwsVerifierDecorator $jwsVerifierDecoratorMock;

    protected \PHPUnit\Framework\MockObject\Stub&JwksDecoratorFactory $jwksDecoratorFactoryMock;

    protected \PHPUnit\Framework\MockObject\Stub&JwsSerializerManagerDecorator $jwsSerializerManagerDecoratorMock;

    protected DateIntervalDecorator $dateIntervalDecorator;

    protected Helpers $helpers;

    protected \PHPUnit\Framework\MockObject\Stub&ClaimFactory $claimFactoryMock;

    protected \PHPUnit\Framework\MockObject\Stub&DisclosureFactory $disclosureFactoryMock;


    protected function setUp(): void
    {
        $this->jwsDecoratorBuilderMock = $this->createMock(JwsDecoratorBuilder::class);
        $this->jwsVerifierDecoratorMock = $this->createStub(JwsVerifierDecorator::class);
        $this->jwksDecoratorFactoryMock = $this->createStub(JwksDecoratorFactory::class);
        $this->jwsSerializerManagerDecoratorMock = $this->createStub(JwsSerializerManagerDecorator::class);
        $this->dateIntervalDecorator = new DateIntervalDecorator(new \DateInterval('PT0S'));
        $this->helpers = new Helpers();
        $this->claimFactoryMock = $this->createStub(ClaimFactory::class);
        $this->disclosureFactoryMock = $this->createStub(DisclosureFactory::class);
    }


    protected function sut(): VcSdJwtFactory
    {
        return new VcSdJwtFactory(
            $this->jwsDecoratorBuilderMock,
            $this->jwsVerifierDecoratorMock,
            $this->jwksDecoratorFactoryMock,
            $this->jwsSerializerManagerDecoratorMock,
            $this->dateIntervalDecorator,
            $this->helpers,
            $this->claimFactoryMock,
            $this->disclosureFactoryMock,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VcSdJwtFactory::class, $this->sut());
    }


    protected function createJwsDecoratorMock(array $payload = []): MockObject
    {
        $payload = array_merge(['iat' => time()], $payload);
        $jwsMock = $this->createMock(\Jose\Component\Signature\JWS::class);
        $jwsMock->method('getPayload')->willReturn(json_encode($payload));

        $jwsDecoratorMock = $this->createMock(JwsDecorator::class);
        $jwsDecoratorMock->method('jws')->willReturn($jwsMock);

        return $jwsDecoratorMock;
    }


    public function testCanBuildFromToken(): void
    {
        $jwsDecoratorMock = $this->createJwsDecoratorMock();

        $this->jwsDecoratorBuilderMock
            ->expects($this->once())
            ->method('fromToken')
            ->with('token')
            ->willReturn($jwsDecoratorMock);

        $this->assertInstanceOf(
            VcSdJwt::class,
            $this->sut()->fromToken('token'),
        );
    }


    public function testCanBuildFromData(): void
    {
        $signingKey = $this->createStub(JwkDecorator::class);
        $signatureAlgorithm = SignatureAlgorithmEnum::RS256;
        $payload = ['foo' => 'bar'];
        $header = ['alg' => 'RS256'];

        $jwsDecoratorMock = $this->createJwsDecoratorMock($payload);

        $this->jwsDecoratorBuilderMock
            ->expects($this->once())
            ->method('fromData')
            ->with(
                $signingKey,
                $signatureAlgorithm,
                $payload,
                $this->callback(function (array $header): true {
                    $this->assertArrayHasKey(ClaimsEnum::Typ->value, $header);
                    $this->assertSame(JwtTypesEnum::VcSdJwt->value, $header[ClaimsEnum::Typ->value]);
                    return true;
                }),
            )
            ->willReturn($jwsDecoratorMock);

        $this->assertInstanceOf(
            VcSdJwt::class,
            $this->sut()->fromData(
                $signingKey,
                $signatureAlgorithm,
                $payload,
                $header,
            ),
        );
    }


    public function testCanBuildFromDataWithDisclosureBag(): void
    {
        $signingKey = $this->createStub(JwkDecorator::class);
        $signatureAlgorithm = SignatureAlgorithmEnum::RS256;
        $payload = ['foo' => 'bar'];
        $header = ['alg' => 'RS256'];
        $disclosureBag = $this->createStub(DisclosureBag::class);
        $disclosureBag->method('all')->willReturn([]);

        $jwsDecoratorMock = $this->createJwsDecoratorMock($payload);

        $this->jwsDecoratorBuilderMock
            ->expects($this->once())
            ->method('fromData')
            ->with(
                $signingKey,
                $signatureAlgorithm,
                $payload,
                $this->callback(function (array $header): true {
                    $this->assertArrayHasKey(ClaimsEnum::Typ->value, $header);
                    $this->assertSame(JwtTypesEnum::VcSdJwt->value, $header[ClaimsEnum::Typ->value]);
                    return true;
                }),
            )
            ->willReturn($jwsDecoratorMock);

        $vcSdJwt = $this->sut()->fromData(
            $signingKey,
            $signatureAlgorithm,
            $payload,
            $header,
            $disclosureBag,
        );

        $this->assertInstanceOf(VcSdJwt::class, $vcSdJwt);
        $this->assertSame($disclosureBag, $vcSdJwt->getDisclosureBag());
    }
}
