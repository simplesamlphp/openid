<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\TrustChainException;
use SimpleSAML\OpenID\Federation\EntityStatement;
use SimpleSAML\OpenID\Federation\EntityStatementFetcher;
use SimpleSAML\OpenID\Federation\Factories\TrustChainBagFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustChainFactory;
use SimpleSAML\OpenID\Federation\TrustChainResolver;

#[CoversClass(TrustChainResolver::class)]
final class TrustChainResolverTest extends TestCase
{
    protected MockObject $entityStatementFetcherMock;

    protected MockObject $trustChainFactoryMock;

    protected MockObject $trustChainBagFactoryMock;

    protected MockObject $maxCacheDurationDecorator;

    protected MockObject $cacheDecoratorMock;

    protected MockObject $loggerMock;

    protected int $maxTrustChainDepth;

    protected int $maxAuthorityHints;

    protected MockObject $leafEntityConfigurationMock;

    protected MockObject $intermediateEntityConfigurationMock;

    protected MockObject $trustAnchorEntityConfigurationMock;

    protected array $configChainSample = [];

    protected function setUp(): void
    {
        $this->entityStatementFetcherMock = $this->createMock(EntityStatementFetcher::class);
        $this->trustChainFactoryMock = $this->createMock(TrustChainFactory::class);
        $this->trustChainBagFactoryMock = $this->createMock(TrustChainBagFactory::class);
        $this->maxCacheDurationDecorator = $this->createMock(DateIntervalDecorator::class);
        $this->cacheDecoratorMock = $this->createMock(CacheDecorator::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->maxTrustChainDepth = 5;
        $this->maxAuthorityHints = 3;

        $this->leafEntityConfigurationMock = $this->createMock(EntityStatement::class);
        $this->intermediateEntityConfigurationMock = $this->createMock(EntityStatement::class);
        $this->trustAnchorEntityConfigurationMock = $this->createMock(EntityStatement::class);

        $this->configChainSample = [
            'l' => $this->leafEntityConfigurationMock,
            'i' => $this->intermediateEntityConfigurationMock,
            't' => $this->trustAnchorEntityConfigurationMock,
        ];
    }

    protected function sut(
        ?EntityStatementFetcher $entityStatementFetcher = null,
        ?TrustChainFactory $trustChainFactory = null,
        ?TrustChainBagFactory $trustChainBagFactory = null,
        ?DateIntervalDecorator $maxCacheDurationDecorator = null,
        ?CacheDecorator $cacheDecorator = null,
        ?LoggerInterface $logger = null,
        ?int $maxTrustChainDepth = null,
        ?int $maxAuthorityHints = null,
    ): TrustChainResolver {
        $entityStatementFetcher ??= $this->entityStatementFetcherMock;
        $trustChainFactory ??= $this->trustChainFactoryMock;
        $trustChainBagFactory ??= $this->trustChainBagFactoryMock;
        $maxCacheDurationDecorator ??= $this->maxCacheDurationDecorator;
        $cacheDecorator ??= $this->cacheDecoratorMock;
        $logger ??= $this->loggerMock;
        $maxTrustChainDepth ??= $this->maxTrustChainDepth;
        $maxAuthorityHints ??= $this->maxAuthorityHints;

        return new TrustChainResolver(
            $entityStatementFetcher,
            $trustChainFactory,
            $trustChainBagFactory,
            $maxCacheDurationDecorator,
            $cacheDecorator,
            $logger,
            $maxTrustChainDepth,
            $maxAuthorityHints,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TrustChainResolver::class, $this->sut());
    }

    public function testCanGetConfigurationChains(): void
    {
        $this->entityStatementFetcherMock
            ->expects($this->exactly(3))
            ->method('fromCacheOrWellKnownEndpoint')
            ->willReturnCallback(
                fn(string $entityId) =>
                    $this->configChainSample[$entityId] ?? throw new \Exception('No entity.'),
            );

        $this->leafEntityConfigurationMock
            ->expects($this->once())
            ->method('getAuthorityHints')
            ->willReturn(['i']);
        $this->intermediateEntityConfigurationMock
            ->expects($this->once())
            ->method('getAuthorityHints')
            ->willReturn(['t']);
        $this->trustAnchorEntityConfigurationMock
            ->expects($this->never())
            ->method('getAuthorityHints');

        $configurationChains = $this->sut()->getConfigurationChains('l', ['t']);

        $this->assertCount(1, $configurationChains);
        $this->assertCount(3, $configurationChains[0]);
    }

    public function testWontStartGettingConfigurationChainsIfNoTrustAnchorIds(): void
    {
        $this->loggerMock
            ->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('start condition'));

        $this->assertEmpty($this->sut()->getConfigurationChains('l', []));
    }

    public function testCanLimitMaximumConfigurationChainDepth(): void
    {
        $sut = $this->sut(maxTrustChainDepth: 2);

        $this->entityStatementFetcherMock
            ->expects($this->exactly(2))
            ->method('fromCacheOrWellKnownEndpoint')
            ->willReturnCallback(fn(string $entityId) =>
                $this->configChainSample[$entityId] ?? throw new \Exception('No entity.'));

        $this->leafEntityConfigurationMock
            ->method('getAuthorityHints')
            ->willReturn(['i']);
        $this->intermediateEntityConfigurationMock
            ->method('getAuthorityHints')
            ->willReturn(['t']);
        $this->trustAnchorEntityConfigurationMock
            ->expects($this->never())
            ->method('getPayloadClaim');

        $this->loggerMock
            ->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('depth'));

        $this->assertEmpty($sut->getConfigurationChains('l', ['t']));
    }

    public function testCanDetectLoopInConfigurationChains(): void
    {
        $this->entityStatementFetcherMock
            ->method('fromCacheOrWellKnownEndpoint')
            ->willReturnCallback(fn(string $entityId) =>
                $this->configChainSample[$entityId] ?? throw new \Exception('No entity.'));

        $this->leafEntityConfigurationMock
            ->method('getAuthorityHints')
            ->willReturn(['i', 'l']);

        $this->loggerMock
            ->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('loop'));

        $this->assertEmpty($this->sut()->getConfigurationChains('l', ['t']));
    }

    public function testConfigurationChainIsEmptyOnConfigurationFetchError(): void
    {
        $this->entityStatementFetcherMock->method('fromCacheOrWellKnownEndpoint')
            ->willThrowException(new \Exception('Error'));

        $this->assertEmpty($this->sut()->getConfigurationChains('l', ['t']));
    }

    public function testCanBailOnMaxAuthorityHintsRule(): void
    {
        $sut = $this->sut(maxAuthorityHints: 1);

        $this->entityStatementFetcherMock
            ->method('fromCacheOrWellKnownEndpoint')
            ->willReturnCallback(fn(string $entityId) =>
                $this->configChainSample[$entityId] ?? throw new \Exception('No entity.'));

        $this->loggerMock
            ->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('kuku'));

        $this->leafEntityConfigurationMock
            ->method('getAuthorityHints')
            ->willReturn(['i', 'l']);

        $this->assertEmpty($sut->getConfigurationChains('l', ['t']));
    }

    public function testCanResolveTrustChain(): void
    {
        $this->entityStatementFetcherMock
            ->method('fromCacheOrWellKnownEndpoint')
            ->willReturnCallback(fn(string $entityId) =>
                $this->configChainSample[$entityId] ?? throw new \Exception('No entity.'));

        $this->leafEntityConfigurationMock
            ->expects($this->once())
            ->method('getAuthorityHints')
            ->willReturn(['i']);
        $this->intermediateEntityConfigurationMock
            ->expects($this->once())
            ->method('getAuthorityHints')
            ->willReturn(['t']);
        $this->trustAnchorEntityConfigurationMock
            ->expects($this->never())
            ->method('getAuthorityHints');

        $this->trustChainBagFactoryMock->expects($this->once())->method('build');
        $this->cacheDecoratorMock->expects($this->once())->method('set');

        $this->sut()->for('l', ['t']);
    }

    public function testCanResolveMultipleTrustChains(): void
    {
        $this->entityStatementFetcherMock
            ->method('fromCacheOrWellKnownEndpoint')
            ->willReturnCallback(fn(string $entityId) =>
                $this->configChainSample[$entityId] ?? throw new \Exception('No entity.'));

        $this->leafEntityConfigurationMock
            ->expects($this->once())
            ->method('getAuthorityHints')
            ->willReturn(['i', 't']);

        $this->trustChainBagFactoryMock->expects($this->once())->method('build');
        $this->cacheDecoratorMock->expects($this->exactly(2))->method('set');

        $this->sut()->for('l', ['i', 't']);
    }

    public function testTrustChainResolveChecksCacheFirst(): void
    {
        $this->cacheDecoratorMock
            ->expects($this->once())
            ->method('get')
            ->with(null, 'l', 't')
            ->willReturn(['token']);

        $this->trustChainFactoryMock
            ->expects($this->once())
            ->method('fromTokens')
            ->with('token');

        $this->entityStatementFetcherMock
            ->expects($this->never())
            ->method('fromCacheOrWellKnownEndpoint');

        $this->sut()->for('l', ['t']);
    }

    public function testCanWarnOnCacheErrorDuringTrustChainResolution(): void
    {
        $this->cacheDecoratorMock
            ->expects($this->once())
            ->method('get')
            ->willThrowException(new \Exception('Error'));

        $this->trustChainFactoryMock
            ->expects($this->never())
            ->method('fromTokens');

        $this->loggerMock
            ->expects($this->atLeastOnce())
            ->method('warning')
            ->with($this->stringContains('cache'));

        $this->expectException(TrustChainException::class);
        $this->expectExceptionMessage('no common trust anchors');

        $this->sut()->for('l', ['t']);
    }

    public function testCanWarnOnTrustChainResolutionSubordinateStatementFetchError(): void
    {
        $this->entityStatementFetcherMock
            ->method('fromCacheOrWellKnownEndpoint')
            ->willReturnCallback(fn(string $entityId) =>
                $this->configChainSample[$entityId] ?? throw new \Exception('No entity.'));

        $this->entityStatementFetcherMock
            ->method('fromCacheOrFetchEndpoint')
            ->willThrowException(new \Exception('fetch error'));

        $this->leafEntityConfigurationMock
            ->expects($this->once())
            ->method('getAuthorityHints')
            ->willReturn(['i']);
        $this->intermediateEntityConfigurationMock
            ->expects($this->once())
            ->method('getAuthorityHints')
            ->willReturn(['t']);

        $this->loggerMock
            ->expects($this->atLeastOnce())
            ->method('error');

        $this->expectException(TrustChainException::class);

        $this->sut()->for('l', ['t']);
    }

    public function testTrustChainResolveThrowsOnTrustChainBagFactoryError(): void
    {
        $this->entityStatementFetcherMock
            ->method('fromCacheOrWellKnownEndpoint')
            ->willReturnCallback(fn(string $entityId) =>
                $this->configChainSample[$entityId] ?? throw new \Exception('No entity.'));

        $this->leafEntityConfigurationMock
            ->expects($this->once())
            ->method('getAuthorityHints')
            ->willReturn(['i']);
        $this->intermediateEntityConfigurationMock
            ->expects($this->once())
            ->method('getAuthorityHints')
            ->willReturn(['t']);

        $this->trustChainBagFactoryMock->expects($this->once())->method('build')
        ->willThrowException(new TrustChainException('Error'));

        $this->expectException(TrustChainException::class);
        $this->expectExceptionMessage('Bag');
        $this->loggerMock
            ->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('Bag'));

        $this->sut()->for('l', ['t']);
    }

    public function testTrustChainResolveThrowsOnValidationStartError(): void
    {
        $this->expectException(TrustChainException::class);
        $this->expectExceptionMessage('Validation error');

        $this->sut()->for('', []);
    }
}
