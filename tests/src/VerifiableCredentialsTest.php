<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID;

use DateInterval;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Factories\AlgorithmManagerDecoratorFactory;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerDecoratorFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsDecoratorBuilderFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\SdJwt\Factories\DisclosureBagFactory;
use SimpleSAML\OpenID\SdJwt\Factories\DisclosureFactory;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\SupportedAlgorithms;
use SimpleSAML\OpenID\SupportedSerializers;
use SimpleSAML\OpenID\VerifiableCredentials;
use SimpleSAML\OpenID\VerifiableCredentials\ClaimsPathPointerResolver;
use SimpleSAML\OpenID\VerifiableCredentials\Factories\CredentialOfferFactory;
use SimpleSAML\OpenID\VerifiableCredentials\Factories\OpenId4VciProofFactory;
use SimpleSAML\OpenID\VerifiableCredentials\Factories\TxCodeFactory;
use SimpleSAML\OpenID\VerifiableCredentials\SdJwtVc\Factories\SdJwtVcFactory;
use SimpleSAML\OpenID\VerifiableCredentials\VcDataModel\Factories\JwtVcJsonFactory;

#[\PHPUnit\Framework\Attributes\CoversClass(VerifiableCredentials::class)]
#[UsesClass(DateIntervalDecorator::class)]
#[UsesClass(DateIntervalDecoratorFactory::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(ClaimsPathPointerResolver::class)]
#[UsesClass(JwsDecoratorBuilderFactory::class)]
#[UsesClass(JwsSerializerManagerDecoratorFactory::class)]
#[UsesClass(JwsSerializerManagerDecorator::class)]
#[UsesClass(AlgorithmManagerDecoratorFactory::class)]
#[UsesClass(AlgorithmManagerDecorator::class)]
#[UsesClass(JwsDecoratorBuilder::class)]
#[UsesClass(JwsVerifierDecoratorFactory::class)]
#[UsesClass(JwsVerifierDecorator::class)]
#[UsesClass(JwksDecoratorFactory::class)]
#[UsesClass(ClaimFactory::class)]
#[UsesClass(JwtVcJsonFactory::class)]
#[UsesClass(CredentialOfferFactory::class)]
#[UsesClass(OpenId4VciProofFactory::class)]
#[UsesClass(DisclosureFactory::class)]
#[UsesClass(DisclosureBagFactory::class)]
#[UsesClass(SdJwtVcFactory::class)]
#[UsesClass(TxCodeFactory::class)]
final class VerifiableCredentialsTest extends TestCase
{
    protected MockObject $supportedSerializersMock;

    protected MockObject $supportedAlgorithmsMock;

    protected MockObject $loggerMock;

    protected DateInterval $timestampValidationLeeway;


    protected function setUp(): void
    {
        $this->supportedSerializersMock = $this->createMock(SupportedSerializers::class);
        $this->supportedAlgorithmsMock = $this->createMock(SupportedAlgorithms::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->timestampValidationLeeway = new DateInterval('PT1M');
    }


    protected function sut(
        ?SupportedSerializers $supportedSerializers = null,
        ?SupportedAlgorithms $supportedAlgorithms = null,
        ?LoggerInterface $logger = null,
        ?DateInterval $timestampValidationLeeway = null,
    ): VerifiableCredentials {
        $supportedSerializers ??= $this->supportedSerializersMock;
        $supportedAlgorithms ??= $this->supportedAlgorithmsMock;
        $logger ??= $this->loggerMock;
        $timestampValidationLeeway ??= $this->timestampValidationLeeway;

        return new VerifiableCredentials(
            $supportedSerializers,
            $supportedAlgorithms,
            $logger,
            $timestampValidationLeeway,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(VerifiableCredentials::class, $this->sut());
    }


    public function testCanBuildTools(): void
    {
        $sut = $this->sut();

        $this->assertInstanceOf(
            DateIntervalDecoratorFactory::class,
            $sut->dateIntervalDecoratorFactory(),
        );

        $this->assertInstanceOf(
            Helpers::class,
            $sut->helpers(),
        );

        $this->assertInstanceOf(
            ClaimsPathPointerResolver::class,
            $sut->claimsPathPointerResolver(),
        );

        $this->assertInstanceOf(
            JwsDecoratorBuilderFactory::class,
            $sut->jwsDecoratorBuilderFactory(),
        );

        $this->assertInstanceOf(
            JwsSerializerManagerDecoratorFactory::class,
            $sut->jwsSerializerManagerDecoratorFactory(),
        );

        $this->assertInstanceOf(
            JwsDecoratorBuilderFactory::class,
            $sut->jwsDecoratorBuilderFactory(),
        );

        $this->assertInstanceOf(
            JwsSerializerManagerDecoratorFactory::class,
            $sut->jwsSerializerManagerDecoratorFactory(),
        );

        $this->assertInstanceOf(
            JwsSerializerManagerDecorator::class,
            $sut->jwsSerializerManagerDecorator(),
        );

        $this->assertInstanceOf(
            AlgorithmManagerDecoratorFactory::class,
            $sut->algorithmManagerDecoratorFactory(),
        );

        $this->assertInstanceOf(
            AlgorithmManagerDecorator::class,
            $sut->algorithmManagerDecorator(),
        );

        $this->assertInstanceOf(
            JwsDecoratorBuilder::class,
            $sut->jwsDecoratorBuilder(),
        );

        $this->assertInstanceOf(
            JwsVerifierDecoratorFactory::class,
            $sut->jwsVerifierDecoratorFactory(),
        );

        $this->assertInstanceOf(
            JwsVerifierDecorator::class,
            $sut->jwsVerifierDecorator(),
        );

        $this->assertInstanceOf(
            JwksDecoratorFactory::class,
            $sut->jwksDecoratorFactory(),
        );

        $this->assertInstanceOf(
            JwtVcJsonFactory::class,
            $sut->jwtVcJsonFactory(),
        );

        $this->assertInstanceOf(
            CredentialOfferFactory::class,
            $sut->credentialOfferFactory(),
        );

        $this->assertInstanceOf(
            OpenId4VciProofFactory::class,
            $sut->openId4VciProofFactory(),
        );

        $this->assertInstanceOf(
            DisclosureFactory::class,
            $sut->disclosureFactory(),
        );

        $this->assertInstanceOf(
            DisclosureBagFactory::class,
            $sut->disclosureBagFactory(),
        );

        $this->assertInstanceOf(
            DisclosureFactory::class,
            $sut->disclosureFactory(),
        );

        $this->assertInstanceOf(
            DisclosureBagFactory::class,
            $sut->disclosureBagFactory(),
        );

        $this->assertInstanceOf(
            SdJwtVcFactory::class,
            $sut->sdJwtVcFactory(),
        );

        $this->assertInstanceOf(
            TxCodeFactory::class,
            $sut->txCodeFactory(),
        );
    }
}
