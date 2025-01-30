<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Codebooks\EntityTypesEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\EntityStatementException;
use SimpleSAML\OpenID\Exceptions\TrustChainException;
use SimpleSAML\OpenID\Federation\EntityStatement;
use SimpleSAML\OpenID\Federation\MetadataPolicyApplicator;
use SimpleSAML\OpenID\Federation\MetadataPolicyResolver;
use SimpleSAML\OpenID\Federation\TrustChain;
use SimpleSAML\OpenID\Helpers;

#[CoversClass(TrustChain::class)]
class TrustChainTest extends TestCase
{
    protected MockObject $timestampValidationLeewayDecoratorMock;
    protected MockObject $helpersMock;
    protected MockObject $metadataPolicyResolverMock;
    protected MockObject $metadataPolicyApplicatorMock;

    protected MockObject $leafMock;
    protected MockObject $subordinateMock;
    protected MockObject $trustAnchorMock;

    protected int $expirationTime;

    protected function setUp(): void
    {
        $this->timestampValidationLeewayDecoratorMock = $this->createMock(DateIntervalDecorator::class);
        $this->metadataPolicyResolverMock = $this->createMock(MetadataPolicyResolver::class);
        $this->metadataPolicyApplicatorMock = $this->createMock(MetadataPolicyApplicator::class);
        $this->helpersMock = $this->createMock(Helpers::class);

        $this->expirationTime = time() + 60;
        $this->leafMock = $this->createMock(EntityStatement::class);
        $this->leafMock->method('isConfiguration')->willReturn(true);
        $this->leafMock->method('getExpirationTime')->willReturn($this->expirationTime);

        $this->subordinateMock = $this->createMock(EntityStatement::class);
        $this->subordinateMock->method('isConfiguration')->willReturn(false);
        $this->subordinateMock->method('getExpirationTime')->willReturn($this->expirationTime);

        $this->trustAnchorMock = $this->createMock(EntityStatement::class);
        $this->trustAnchorMock->method('isConfiguration')->willReturn(true);
        $this->trustAnchorMock->method('getExpirationTime')->willReturn($this->expirationTime);
    }

    protected function sut(
        ?DateIntervalDecorator $timestampValidationLeewayDecorator = null,
        ?MetadataPolicyResolver $metadataPolicyResolver = null,
        ?MetadataPolicyApplicator $metadataPolicyApplicator = null,
        ?Helpers $helpers = null,
    ): TrustChain {
        $timestampValidationLeewayDecorator ??= $this->timestampValidationLeewayDecoratorMock;
        $metadataPolicyResolver ??= $this->metadataPolicyResolverMock;
        $metadataPolicyApplicator ??= $this->metadataPolicyApplicatorMock;
        $helpers ??= $this->helpersMock;

        return new TrustChain(
            $timestampValidationLeewayDecorator,
            $metadataPolicyResolver,
            $metadataPolicyApplicator,
            $helpers,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustChain::class, $this->sut());
    }

    public function testCanCheckIfEmpty(): void
    {
        $this->assertTrue($this->sut()->isEmpty());
        $this->assertEmpty($this->sut()->getEntities());
    }

    public function testCanCreateBasicTrustChain(): void
    {
        $sut = $this->sut();
        $sut->addLeaf($this->leafMock);
        $sut->addSubordinate($this->subordinateMock);
        $sut->addTrustAnchor($this->trustAnchorMock);

        $this->assertFalse($sut->isEmpty());
        $this->assertCount(3, $sut->getEntities());
        $this->assertSame(3, $sut->getResolvedLength());
        $this->assertNotEmpty($sut->jsonSerialize());
        $this->assertSame($this->expirationTime, $sut->getResolvedExpirationTime());
        $this->assertSame($this->leafMock, $sut->getResolvedLeaf());
        $this->assertSame($this->subordinateMock, $sut->getResolvedImmediateSuperior());
        $this->assertSame($this->trustAnchorMock, $sut->getResolvedTrustAnchor());
        $this->assertNull($sut->getResolvedMetadata(EntityTypesEnum::OpenIdRelyingParty));
    }

    public function testThrowsForNonConfigurationStatementForLeaf(): void
    {
        $this->expectException(EntityStatementException::class);
        $this->expectExceptionMessage('Configuration');

        $this->sut()->addLeaf($this->subordinateMock);
    }

    public function testThrowsForConfigurationStatementForSubordinate(): void
    {
        $this->expectException(EntityStatementException::class);
        $this->expectExceptionMessage('Subordinate');

        $sut = $this->sut();
        $sut->addLeaf($this->leafMock);
        $sut->addSubordinate($this->leafMock);
    }

    public function testThrowsForInvalidSubordinateSubject(): void
    {
        $this->expectException(EntityStatementException::class);
        $this->expectExceptionMessage('Subordinate');

        $this->subordinateMock->method('getSubject')->willReturn('something-different');

        $sut = $this->sut();
        $sut->addLeaf($this->leafMock);
        $sut->addSubordinate($this->subordinateMock);
    }

    public function testCanValidateExpirationTimeOnEmptyTrustChain(): void
    {
        $this->sut()->validateExpirationTime();
        $this->addToAssertionCount(1);
    }

    public function testThrowsForInvalidExpirationTime(): void
    {
        $leafMock = $this->createMock(EntityStatement::class);
        $leafMock->method('isConfiguration')->willReturn(true);
        $leafMock->method('getExpirationTime')->willReturn(time() - 60);

        $this->expectException(TrustChainException::class);
        $this->expectExceptionMessage('expiration');

        $sut = $this->sut();
        $sut->addLeaf($leafMock);
    }

    public function testThrowsForNonResolvedState(): void
    {
        $this->expectException(TrustChainException::class);
        $this->expectExceptionMessage('resolved');

        $this->sut()->getResolvedLength();
    }

    public function testThrowsForResolvedState(): void
    {
        $sut = $this->sut();
        $sut->addLeaf($this->leafMock);
        $sut->addSubordinate($this->subordinateMock);
        $sut->addTrustAnchor($this->trustAnchorMock);

        $this->expectException(TrustChainException::class);
        $this->expectExceptionMessage('resolved');

        $sut->addTrustAnchor($this->trustAnchorMock);
    }

    public function testCanGetResolvedMetadata(): void
    {
        $leafMetadata = [
            'openid_relying_party' => [
                'contacts' => [
                    'helpdesk@leaf.org',
                ],
            ],
        ];
        $this->leafMock->expects($this->once())->method('getMetadata')
            ->willReturn($leafMetadata);

        $subordinateMetadata = [
            'openid_relying_party' => [
                'some_claim' => 'something',
            ],
        ];
        $subordinateMetadataPolicy = [
            'openid_relying_party' => [
                'contacts' => [
                    'add' => ['helpdesk@subordinate.org'],
                ],
            ],
        ];
        $this->subordinateMock->expects($this->once())->method('getMetadata')
            ->willReturn($subordinateMetadata);
        $this->subordinateMock->expects($this->once())->method('getMetadataPolicy')
            ->willReturn($subordinateMetadataPolicy);

        $this->metadataPolicyResolverMock->expects($this->once())->method('ensureFormat')
            ->with($subordinateMetadataPolicy)
            ->willReturn($subordinateMetadataPolicy);

        $this->metadataPolicyResolverMock->expects($this->once())->method('for')
            ->with(
                EntityTypesEnum::OpenIdRelyingParty,
                [$subordinateMetadataPolicy],
                [],
            )->willReturn($subordinateMetadataPolicy);

        $this->metadataPolicyApplicatorMock->expects($this->once())->method('for')
            ->with(
                $subordinateMetadataPolicy,
                [
                    'contacts' => [
                        'helpdesk@leaf.org',
                    ],
                    'some_claim' => 'something',
                ],
            ); // I don't return value, as it is not important to check it here...

        $sut = $this->sut();
        $sut->addLeaf($this->leafMock);
        $sut->addSubordinate($this->subordinateMock);
        $sut->addTrustAnchor($this->trustAnchorMock);

        // I'm only interested if all the calls are made as intended.
        $this->assertIsArray($sut->getResolvedMetadata(EntityTypesEnum::OpenIdRelyingParty));
        // Validate that we only resolve metadata once.
        $this->assertIsArray($sut->getResolvedMetadata(EntityTypesEnum::OpenIdRelyingParty));
    }

    public function testCanGetResolvedMetadataIfNoPoliciesAreDefined(): void
    {
        $leafMetadata = [
            'openid_relying_party' => [
                'contacts' => [
                    'helpdesk@leaf.org',
                ],
            ],
        ];
        $this->leafMock->expects($this->once())->method('getMetadata')
            ->willReturn($leafMetadata);


        $this->subordinateMock->expects($this->once())->method('getMetadata')
            ->willReturn(null);
        $this->subordinateMock->expects($this->once())->method('getMetadataPolicy')
            ->willReturn(null);

        $this->metadataPolicyApplicatorMock->expects($this->never())->method('for');

        $sut = $this->sut();
        $sut->addLeaf($this->leafMock);
        $sut->addSubordinate($this->subordinateMock);
        $sut->addTrustAnchor($this->trustAnchorMock);

        $this->assertIsArray($sut->getResolvedMetadata(EntityTypesEnum::OpenIdRelyingParty));
        // Validate that we only resolve metadata once.
        $this->assertIsArray($sut->getResolvedMetadata(EntityTypesEnum::OpenIdRelyingParty));
    }

    public function testThrowsOnAttemtpToAddMultipleLeafs(): void
    {
        $this->expectException(TrustChainException::class);
        $this->expectExceptionMessage('empty');

        $sut = $this->sut();
        $sut->addLeaf($this->leafMock);
        $sut->addLeaf($this->leafMock);
    }

    public function testThrowsOnAttemtpToAddSubodrinateWithoutLeaf(): void
    {
        $this->expectException(TrustChainException::class);
        $this->expectExceptionMessage('non-empty');

        $sut = $this->sut();
        $sut->addSubordinate($this->subordinateMock);
    }

    public function testThrowsOnAttemtpToAddTrustAnchorWithoutSubordinate(): void
    {
        $this->expectException(TrustChainException::class);
        $this->expectExceptionMessage('at least');

        $sut = $this->sut();
        $sut->addLeaf($this->leafMock);
        $sut->addTrustAnchor($this->trustAnchorMock);
    }
}
