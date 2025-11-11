<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation;

use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Federation\TrustMarkStatusResponse;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(TrustMarkStatusResponse::class)]
#[UsesClass(ParsedJws::class)]
final class TrustMarkStatusResponseTest extends TestCase
{
    protected MockObject $signatureMock;

    protected MockObject $jwsDecoratorMock;

    protected MockObject $jwsVerifierDecoratorMock;

    protected MockObject $jwksFactoryMock;

    protected MockObject $jwsSerializerManagerDecoratorMock;

    protected MockObject $dateIntervalDecoratorMock;

    protected MockObject $helpersMock;

    protected MockObject $jsonHelperMock;

    protected MockObject $claimFactoryMock;

    protected array $samplePayload = [
        'iss' => 'https://www.agid.gov.it',
        'iat' => 1734016912,
        // phpcs:ignore
        'trust_mark' => 'trust-mark-string',
        'status' => 'active',
    ];

    protected array $sampleHeader = [
        'alg' => 'RS256',
        'typ' => 'trust-mark-status-response+jwt',
        'kid' => 'fsQ45F0D916RdKEeTjta8DYWiodjthouHrVWgOXBrkk',
    ];


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
        $this->jwksFactoryMock = $this->createMock(JwksFactory::class);
        $this->jwsSerializerManagerDecoratorMock = $this->createMock(JwsSerializerManagerDecorator::class);
        $this->dateIntervalDecoratorMock = $this->createMock(DateIntervalDecorator::class);

        $this->helpersMock = $this->createMock(Helpers::class);
        $this->jsonHelperMock = $this->createMock(Helpers\Json::class);
        $this->helpersMock->method('json')->willReturn($this->jsonHelperMock);
        $typeHelperMock = $this->createMock(Helpers\Type::class);
        $this->helpersMock->method('type')->willReturn($typeHelperMock);

        $typeHelperMock->method('ensureNonEmptyString')->willReturnArgument(0);
        $typeHelperMock->method('ensureInt')->willReturnArgument(0);

        $this->claimFactoryMock = $this->createMock(ClaimFactory::class);
    }


    protected function sut(
        ?JwsDecorator $jwsDecorator = null,
        ?JwsVerifierDecorator $jwsVerifierDecorator = null,
        ?JwksFactory $jwksFactory = null,
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
        ?DateIntervalDecorator $dateIntervalDecorator = null,
        ?Helpers $helpers = null,
        ?ClaimFactory $claimFactory = null,
    ): TrustMarkStatusResponse {
        $jwsDecorator ??= $this->jwsDecoratorMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksFactory ??= $this->jwksFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $dateIntervalDecorator ??= $this->dateIntervalDecoratorMock;
        $helpers ??= $this->helpersMock;
        $claimFactory ??= $this->claimFactoryMock;

        return new TrustMarkStatusResponse(
            $jwsDecorator,
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
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($this->samplePayload);

        $this->assertInstanceOf(
            TrustMarkStatusResponse::class,
            $this->sut(),
        );
    }


    public function testThrowsForMissingTrustMark(): void
    {
        $payload = $this->samplePayload;
        unset($payload['trust_mark']);
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Trust Mark');

        $this->sut();
    }


    public function testThrowsForMissingStatus(): void
    {
        $payload = $this->samplePayload;
        unset($payload['status']);
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jsonHelperMock->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Status');

        $this->sut();
    }


    public function testThrowsForInvalidTypeHeader(): void
    {
        $header = $this->sampleHeader;
        $header['typ'] = 'invalid';
        $this->signatureMock->method('getProtectedHeader')->willReturn($header);
        $this->jsonHelperMock->method('decode')->willReturn($this->samplePayload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Type');

        $this->sut();
    }
}
