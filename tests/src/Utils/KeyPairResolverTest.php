<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Utils;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Exceptions\OpenIdException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Helpers\Type;
use SimpleSAML\OpenID\Utils\KeyPairResolver;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPair;
use SimpleSAML\OpenID\ValueAbstracts\SignatureKeyPairBag;

#[CoversClass(KeyPairResolver::class)]
#[UsesClass(Type::class)]
final class KeyPairResolverTest extends TestCase
{
    private MockObject $helpersMock;

    private MockObject $loggerMock;

    private MockObject $signatureKeyPairBagMock;

    private MockObject $defaultKeyPairMock;


    protected function setUp(): void
    {
        $this->helpersMock = $this->createMock(\SimpleSAML\OpenID\Helpers::class);
        $typeHelper = new Type();
        $this->helpersMock->method('type')->willReturn($typeHelper);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->signatureKeyPairBagMock = $this->createMock(SignatureKeyPairBag::class);

        $this->defaultKeyPairMock = $this->createMock(SignatureKeyPair::class);
        $this->defaultKeyPairMock->method('getSignatureAlgorithm')
            ->willReturn(SignatureAlgorithmEnum::RS256);

        $this->signatureKeyPairBagMock->method('getFirstOrFail')
            ->willReturn($this->defaultKeyPairMock);
    }


    protected function sut(
        ?Helpers $helpers = null,
        ?LoggerInterface $logger = null,
    ): KeyPairResolver {
        $helpers ??= $this->helpersMock;
        $logger ??= $this->loggerMock;

        return new KeyPairResolver($helpers, $logger);
    }


    public function testResolveWithReceiverDesignatedAlgorithm(): void
    {
        $receiverMetadata = [
            'receiver_alg' => 'RS256',
        ];

        $keyPairMock = $this->createMock(SignatureKeyPair::class);
        $keyPairMock->method('getSignatureAlgorithm')
            ->willReturn(SignatureAlgorithmEnum::RS256);
        $this->signatureKeyPairBagMock->expects($this->once())
            ->method('getFirstByAlgorithmOrFail')
            ->with(SignatureAlgorithmEnum::RS256)
            ->willReturn($keyPairMock);

        $result = $this->sut()->resolveSignatureKeyPairByAlgorithm(
            $this->signatureKeyPairBagMock,
            $receiverMetadata,
            [],
            'receiver_alg',
        );

        $this->assertSame($keyPairMock, $result);
    }


    public function testResolveWithSenderDesignatedAlgorithm(): void
    {
        $senderMetadata = [
            'sender_alg' => 'ES256',
        ];

        $keyPairMock = $this->createMock(SignatureKeyPair::class);
        $keyPairMock->method('getSignatureAlgorithm')
            ->willReturn(SignatureAlgorithmEnum::ES256);

        $this->signatureKeyPairBagMock->expects($this->once())
            ->method('getFirstByAlgorithmOrFail')
            ->with(SignatureAlgorithmEnum::ES256)
            ->willReturn($keyPairMock);

        $result = $this->sut()->resolveSignatureKeyPairByAlgorithm(
            $this->signatureKeyPairBagMock,
            [],
            $senderMetadata,
            null,
            null,
            'sender_alg',
        );

        $this->assertSame($keyPairMock, $result);
    }


    public function testResolveConflictDesignatedAlgorithmsThrows(): void
    {
        $receiverMetadata = ['alg' => 'RS256'];
        $senderMetadata = ['alg' => 'ES256'];

        $this->expectException(OpenIdException::class);
        $this->expectExceptionMessage('Conflict in designated signature algorithms');

        $this->sut()->resolveSignatureKeyPairByAlgorithm(
            $this->signatureKeyPairBagMock,
            $receiverMetadata,
            $senderMetadata,
            'alg',
            null,
            'alg',
        );
    }


    public function testResolveCommonlySupportedAlgorithms(): void
    {
        $receiverMetadata = ['supported' => ['RS256', 'ES256']];
        $senderMetadata = ['supported' => ['ES256', 'PS256']];

        $this->signatureKeyPairBagMock->method('getAllAlgorithmNamesUnique')
            ->willReturn(['RS256', 'ES256', 'PS256']);

        $keyPairMock = $this->createMock(SignatureKeyPair::class);
        $keyPairMock->method('getSignatureAlgorithm')
            ->willReturn(SignatureAlgorithmEnum::ES256);
        $this->signatureKeyPairBagMock->expects($this->once())
            ->method('getFirstByAlgorithmOrFail')
            ->with(SignatureAlgorithmEnum::ES256)
            ->willReturn($keyPairMock);

        $result = $this->sut()->resolveSignatureKeyPairByAlgorithm(
            $this->signatureKeyPairBagMock,
            $receiverMetadata,
            $senderMetadata,
            null,
            'supported',
            null,
            'supported',
        );

        $this->assertSame($keyPairMock, $result);
    }


    public function testResolveFallbackToDefaultWhenNoMatch(): void
    {
        $receiverMetadata = ['supported' => ['RS256']];
        $senderMetadata = ['supported' => ['ES256']];

        $this->signatureKeyPairBagMock->method('getAllAlgorithmNamesUnique')
            ->willReturn(['PS256']);


        $result = $this->sut()->resolveSignatureKeyPairByAlgorithm(
            $this->signatureKeyPairBagMock,
            $receiverMetadata,
            $senderMetadata,
            null,
            'supported',
            null,
            'supported',
        );

        $this->assertSame($this->defaultKeyPairMock, $result);
    }
}
