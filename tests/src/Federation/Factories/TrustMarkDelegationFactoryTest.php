<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\Factories;

use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkDelegationFactory;
use SimpleSAML\OpenID\Federation\TrustMarkDelegation;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsParser;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(TrustMarkDelegationFactory::class)]
#[UsesClass(TrustMarkDelegation::class)]
final class TrustMarkDelegationFactoryTest extends TestCase
{
    protected MockObject $signatureMock;



    protected MockObject $jwsParserMock;

    protected MockObject $jwsVerifierDecoratorMock;

    protected MockObject $jwksFactoryMock;

    protected MockObject $jwsSerializerManagerMock;

    protected MockObject $dateIntervalDecoratorMock;

    protected MockObject $helpersMock;

    protected MockObject $jsonHelperMock;


    protected MockObject $claimFactoryMock;

    protected array $sampleHeader = [
        'alg' => 'RS256',
        'typ' => 'trust-mark-delegation+jwt',
        'kid' => 'fsQ45F0D916RdKEeTjta8DYWiodjthouHrVWgOXBrkk',
    ];

    protected array $expiredPayload = [
        'iat' => 1734016912,
        'nbf' => 1734016912,
        'exp' => 1734020512,
        // phpcs:ignore
        'trust_mark_id' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ABTrustAnchor/trust-mark/member',
        'iss' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ABTrustAnchor/',
        'sub' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/',
        'ref' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ABTrustAnchor/ref/trust-mark/member',
    ];

    protected array $validPayload;

    protected function setUp(): void
    {
        $this->signatureMock = $this->createMock(Signature::class);

        $jwsMock = $this->createMock(JWS::class);
        $jwsMock->method('getPayload')
            ->willReturn('json-payload-string'); // Just so we have non-empty value.
        $jwsMock->method('getSignature')->willReturn($this->signatureMock);

        $jwsDecoratorMock = $this->createMock(JwsDecorator::class);
        $jwsDecoratorMock->method('jws')->willReturn($jwsMock);

        $this->jwsParserMock = $this->createMock(JwsParser::class);
        $this->jwsParserMock->method('parse')->willReturn($jwsDecoratorMock);

        $this->jwsVerifierDecoratorMock = $this->createMock(JwsVerifierDecorator::class);
        $this->jwksFactoryMock = $this->createMock(JwksFactory::class);
        $this->jwsSerializerManagerMock = $this->createMock(JwsSerializerManagerDecorator::class);
        $this->dateIntervalDecoratorMock = $this->createMock(DateIntervalDecorator::class);

        $this->helpersMock = $this->createMock(Helpers::class);
        $this->jsonHelperMock = $this->createMock(Helpers\Json::class);
        $this->helpersMock->method('json')->willReturn($this->jsonHelperMock);
        $typeHelperMock = $this->createMock(Helpers\Type::class);
        $this->helpersMock->method('type')->willReturn($typeHelperMock);

        $typeHelperMock->method('ensureNonEmptyString')->willReturnArgument(0);
        $typeHelperMock->method('ensureInt')->willReturnArgument(0);

        $this->claimFactoryMock = $this->createMock(ClaimFactory::class);

        $this->validPayload = $this->expiredPayload;
        $this->validPayload['exp'] = time() + 3600;
    }

    protected function sut(
        ?JwsParser $jwsParser = null,
        ?JwsVerifierDecorator $jwsVerifierDecorator = null,
        ?JwksFactory $jwksFactory = null,
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
        ?ClaimFactory $claimFactory = null,
    ): TrustMarkDelegationFactory {
        $jwsParser ??= $this->jwsParserMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksFactory ??= $this->jwksFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;
        $claimFactory ??= $this->claimFactoryMock;

        return new TrustMarkDelegationFactory(
            $jwsParser,
            $jwsVerifierDecorator,
            $jwksFactory,
            $jwsSerializerManagerDecorator,
            $dateIntervalDecorator,
            $helpers,
            $claimFactory,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustMarkDelegationFactory::class, $this->sut());
    }



    public function testCanBuild(): void
    {
        $this->jsonHelperMock->method('decode')->willReturn($this->validPayload);
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $this->assertInstanceOf(
            TrustMarkDelegation::class,
            $this->sut()->fromToken('token'),
        );
    }
}
