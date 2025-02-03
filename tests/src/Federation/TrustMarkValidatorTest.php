<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimBag;
use SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimValue;
use SimpleSAML\OpenID\Federation\EntityStatement;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkDelegationFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkFactory;
use SimpleSAML\OpenID\Federation\TrustChainResolver;
use SimpleSAML\OpenID\Federation\TrustMark;
use SimpleSAML\OpenID\Federation\TrustMarkValidator;

#[CoversClass(TrustMarkValidator::class)]
class TrustMarkValidatorTest extends TestCase
{
    protected MockObject $trustChainResolverMock;
    protected MockObject $trustMarkFactoryMock;
    protected MockObject $trustMarkDelegationFactoryMock;
    protected MockObject $maxCacheDurationDecoratorMock;
    protected MockObject $cacheDecoratorMock;
    protected MockObject $loggerMock;
    protected MockObject $leafEntityConfigurationMock;
    protected MockObject $trustAnchorConfigurationMock;
    protected MockObject $trustMarksClaimBagMock;
    protected MockObject $trustMarksClaimValueMock;
    protected MockObject $trustMarkMock;

    protected function setUp(): void
    {
        $this->trustChainResolverMock = $this->createMock(TrustChainResolver::class);
        $this->trustMarkFactoryMock = $this->createMock(TrustMarkFactory::class);
        $this->trustMarkDelegationFactoryMock = $this->createMock(TrustMarkDelegationFactory::class);
        $this->maxCacheDurationDecoratorMock = $this->createMock(DateIntervalDecorator::class);
        $this->cacheDecoratorMock = $this->createMock(CacheDecorator::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->leafEntityConfigurationMock = $this->createMock(EntityStatement::class);
        $this->leafEntityConfigurationMock->method('getIssuer')->willReturn('leafEntityId');

        $this->trustAnchorConfigurationMock = $this->createMock(EntityStatement::class);
        $this->trustAnchorConfigurationMock->method('getIssuer')->willReturn('trustAnchorId');

        $this->trustMarksClaimBagMock = $this->createMock(TrustMarksClaimBag::class);
        $this->trustMarksClaimValueMock = $this->createMock(TrustMarksClaimValue::class);
        $this->trustMarkMock = $this->createMock(TrustMark::class);
    }

    protected function sut(
        ?TrustChainResolver $trustChainResolver = null,
        ?TrustMarkFactory $trustMarkFactory = null,
        ?TrustMarkDelegationFactory $trustMarkDelegationFactory = null,
        ?DateIntervalDecorator $maxCacheDurationDecorator = null,
        ?CacheDecorator $cacheDecorator = null,
        ?LoggerInterface $logger = null,
    ): TrustMarkValidator {
        $trustChainResolver ??= $this->trustChainResolverMock;
        $trustMarkFactory ??= $this->trustMarkFactoryMock;
        $trustMarkDelegationFactory ??= $this->trustMarkDelegationFactoryMock;
        $maxCacheDurationDecorator ??= $this->maxCacheDurationDecoratorMock;
        $cacheDecorator ??= $this->cacheDecoratorMock;
        $logger ??= $this->loggerMock;

        return new TrustMarkValidator(
            $trustChainResolver,
            $trustMarkFactory,
            $trustMarkDelegationFactory,
            $maxCacheDurationDecorator,
            $cacheDecorator,
            $logger,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustMarkValidator::class, $this->sut());
    }

    public function testCanGetIsValidationCachedFor(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(
                null,
                'trustMarkId',
                'leafEntityId',
                'trustAnchorId',
            )
            ->willReturn('trustMarkId');

        $this->assertTrue(
            $this->sut()->isValidationCachedFor(
                'trustMarkId',
                'leafEntityId',
                'trustAnchorId',
            ),
        );
    }

    public function testIsValidationCachedForReturnsFalceIfNotCached(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(
                null,
                'trustMarkId',
                'leafEntityId',
                'trustAnchorId',
            )
            ->willReturn(null);

        $this->assertFalse(
            $this->sut()->isValidationCachedFor(
                'trustMarkId',
                'leafEntityId',
                'trustAnchorId',
            ),
        );
    }

    public function testIsValidationCachedForReturnsFalseIfNoCacheInstance(): void
    {
        $sut = new TrustMarkValidator(
            $this->trustChainResolverMock,
            $this->trustMarkFactoryMock,
            $this->trustMarkDelegationFactoryMock,
            $this->maxCacheDurationDecoratorMock,
        );

        $this->assertFalse(
            $sut->isValidationCachedFor(
                'trustMarkId',
                'leafEntityId',
                'trustAnchorId',
            ),
        );
    }

    public function testForTrustMarksIdChecksCache(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(
                null,
                'trustMarkId',
                'leafEntityId',
                'trustAnchorId',
            )->willReturn('trustMarkId');

        $this->leafEntityConfigurationMock->expects($this->never())->method('getTrustMarks');

        $this->sut()->forTrustMarkId(
            'trustMarkId',
            $this->leafEntityConfigurationMock,
            $this->trustAnchorConfigurationMock,
        );
    }

    public function testForTrustMarksIdRuns(): void
    {
        $this->leafEntityConfigurationMock->expects($this->once())->method('getTrustMarks')
            ->willReturn($this->trustMarksClaimBagMock);
        $this->trustMarksClaimBagMock->expects($this->once())->method('getAllFor')
            ->with('trustMarkId')
            ->willReturn([$this->trustMarksClaimValueMock]);
        $this->trustMarksClaimValueMock->method('getTrustMarkId')->willReturn('trustMarkId');
        $this->trustMarkFactoryMock->expects($this->once())->method('fromToken')
            ->willReturn($this->trustMarkMock);
        $this->trustMarkMock->method('getIdentifier')->willReturn('trustMarkId');
        $this->trustMarkMock->method('getSubject')->willReturn('leafEntityId');
        $this->trustChainResolverMock->expects($this->once())->method('for');
        $this->trustMarkMock->expects($this->once())->method('verifyWithKeySet');
        $this->cacheDecoratorMock->expects($this->once())->method('set')
            ->with('trustMarkId');

        $this->sut()->forTrustMarkId(
            'trustMarkId',
            $this->leafEntityConfigurationMock,
            $this->trustAnchorConfigurationMock,
        );
    }
}
