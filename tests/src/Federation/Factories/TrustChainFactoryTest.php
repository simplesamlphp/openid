<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation\Factories;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\TrustChainException;
use SimpleSAML\OpenID\Federation\EntityStatement;
use SimpleSAML\OpenID\Federation\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustChainFactory;
use SimpleSAML\OpenID\Federation\MetadataPolicyApplicator;
use SimpleSAML\OpenID\Federation\MetadataPolicyResolver;
use SimpleSAML\OpenID\Federation\TrustChain;

#[CoversClass(TrustChainFactory::class)]
#[UsesClass(TrustChain::class)]
class TrustChainFactoryTest extends TestCase
{
    protected MockObject $entityStatementFactoryMock;
    protected MockObject $timestampValidationLeewayMock;
    protected MockObject $metadataPolicyResolverMock;
    protected MockObject $metadataPolicyApplicatorMock;

    protected function setUp(): void
    {
        $this->entityStatementFactoryMock = $this->createMock(EntityStatementFactory::class);
        $this->timestampValidationLeewayMock = $this->createMock(DateIntervalDecorator::class);
        $this->metadataPolicyResolverMock = $this->createMock(MetadataPolicyResolver::class);
        $this->metadataPolicyApplicatorMock = $this->createMock(MetadataPolicyApplicator::class);
    }

    protected function sut(
        ?EntityStatementFactory $entityStatementFactory = null,
        ?DateIntervalDecorator $timestampValidationLeewayMock = null,
        ?MetadataPolicyResolver $metadataPolicyResolver = null,
        ?MetadataPolicyApplicator $metadataPolicyApplicator = null,
    ): TrustChainFactory {
        $entityStatementFactory ??= $this->entityStatementFactoryMock;
        $timestampValidationLeewayMock ??= $this->timestampValidationLeewayMock;
        $metadataPolicyResolver ??= $this->metadataPolicyResolverMock;
        $metadataPolicyApplicator ??= $this->metadataPolicyApplicatorMock;

        return new TrustChainFactory(
            $entityStatementFactory,
            $timestampValidationLeewayMock,
            $metadataPolicyResolver,
            $metadataPolicyApplicator,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustChainFactory::class, $this->sut());
    }

    public function testCanBuildEmptyTrustChain(): void
    {
        $this->assertInstanceOf(TrustChain::class, $this->sut()->empty());
    }

    public function testCanBuildFromStatements(): void
    {
        $expirationTime = time() + 60;

        $leaf = $this->createMock(EntityStatement::class);
        $leaf->method('isConfiguration')->willReturn(true);
        $leaf->method('getExpirationTime')->willReturn($expirationTime);

        $subordinate = $this->createMock(EntityStatement::class);
        $subordinate->method('isConfiguration')->willReturn(false);
        $subordinate->method('getExpirationTime')->willReturn($expirationTime);

        $trustAnchor = $this->createMock(EntityStatement::class);
        $trustAnchor->method('isConfiguration')->willReturn(true);
        $trustAnchor->method('getExpirationTime')->willReturn($expirationTime);

        $trustChain = $this->sut()->fromStatements($leaf, $subordinate, $trustAnchor);
        $this->assertInstanceOf(TrustChain::class, $trustChain);
        $this->assertFalse($trustChain->isEmpty());
    }

    public function testBuildFromStatementsThrowsForLessThanThreeEntityStatements(): void
    {
        $expirationTime = time() + 60;

        $leaf = $this->createMock(EntityStatement::class);
        $leaf->method('isConfiguration')->willReturn(true);
        $leaf->method('getExpirationTime')->willReturn($expirationTime);

        $subordinate = $this->createMock(EntityStatement::class);
        $subordinate->method('isConfiguration')->willReturn(false);
        $subordinate->method('getExpirationTime')->willReturn($expirationTime);

        $this->expectException(TrustChainException::class);
        $this->expectExceptionMessage('at least');

        $this->sut()->fromStatements($leaf, $subordinate);
    }

    public function testCanBuildFromTokens(): void
    {
        $expirationTime = time() + 60;

        $leaf = $this->createMock(EntityStatement::class);
        $leaf->method('isConfiguration')->willReturn(true);
        $leaf->method('getExpirationTime')->willReturn($expirationTime);

        $subordinate = $this->createMock(EntityStatement::class);
        $subordinate->method('isConfiguration')->willReturn(false);
        $subordinate->method('getExpirationTime')->willReturn($expirationTime);

        $trustAnchor = $this->createMock(EntityStatement::class);
        $trustAnchor->method('isConfiguration')->willReturn(true);
        $trustAnchor->method('getExpirationTime')->willReturn($expirationTime);

        $this->entityStatementFactoryMock->expects($this->exactly(3))
            ->method('fromToken')
            ->willReturnOnConsecutiveCalls($leaf, $subordinate, $trustAnchor);

        $this->assertInstanceOf(
            TrustChain::class,
            $this->sut()->fromTokens('token', 'token2', 'tokent3'),
        );
    }
}
