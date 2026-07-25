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
use SimpleSAML\OpenID\Exceptions\TrustChainResolutionBudgetException;
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

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\SimpleSAML\OpenID\Decorators\DateIntervalDecorator
     */
    protected \PHPUnit\Framework\MockObject\Stub $maxCacheDurationDecorator;

    protected MockObject $cacheDecoratorMock;

    protected MockObject $loggerMock;

    protected int $maxTrustChainDepth;

    protected int $maxAuthorityHints;

    protected int $maxTrustChainFetches;

    protected int $trustChainResolveTimeout;

    protected MockObject $leafEntityConfigurationMock;

    protected MockObject $intermediateEntityConfigurationMock;

    protected MockObject $trustAnchorEntityConfigurationMock;

    protected array $configChainSample = [];


    protected function setUp(): void
    {
        $this->entityStatementFetcherMock = $this->createMock(EntityStatementFetcher::class);
        $this->trustChainFactoryMock = $this->createMock(TrustChainFactory::class);
        $this->trustChainBagFactoryMock = $this->createMock(TrustChainBagFactory::class);
        $this->maxCacheDurationDecorator = $this->createStub(DateIntervalDecorator::class);
        $this->cacheDecoratorMock = $this->createMock(CacheDecorator::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->maxTrustChainDepth = 5;
        $this->maxAuthorityHints = 3;
        $this->maxTrustChainFetches = 100;
        $this->trustChainResolveTimeout = 30;

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
        ?int $maxTrustChainFetches = null,
        ?int $trustChainResolveTimeout = null,
    ): TrustChainResolver {
        return new TrustChainResolver(
            ...$this->sutConstructorArgs(
                $entityStatementFetcher,
                $trustChainFactory,
                $trustChainBagFactory,
                $maxCacheDurationDecorator,
                $cacheDecorator,
                $logger,
                $maxTrustChainDepth,
                $maxAuthorityHints,
                $maxTrustChainFetches,
                $trustChainResolveTimeout,
            ),
        );
    }


    /**
     * @return array<int,mixed>
     */
    protected function sutConstructorArgs(
        ?EntityStatementFetcher $entityStatementFetcher = null,
        ?TrustChainFactory $trustChainFactory = null,
        ?TrustChainBagFactory $trustChainBagFactory = null,
        ?DateIntervalDecorator $maxCacheDurationDecorator = null,
        ?CacheDecorator $cacheDecorator = null,
        ?LoggerInterface $logger = null,
        ?int $maxTrustChainDepth = null,
        ?int $maxAuthorityHints = null,
        ?int $maxTrustChainFetches = null,
        ?int $trustChainResolveTimeout = null,
    ): array {
        return [
            $entityStatementFetcher ?? $this->entityStatementFetcherMock,
            $trustChainFactory ?? $this->trustChainFactoryMock,
            $trustChainBagFactory ?? $this->trustChainBagFactoryMock,
            $maxCacheDurationDecorator ?? $this->maxCacheDurationDecorator,
            $cacheDecorator ?? $this->cacheDecoratorMock,
            $logger ?? $this->loggerMock,
            $maxTrustChainDepth ?? $this->maxTrustChainDepth,
            $maxAuthorityHints ?? $this->maxAuthorityHints,
            $maxTrustChainFetches ?? $this->maxTrustChainFetches,
            $trustChainResolveTimeout ?? $this->trustChainResolveTimeout,
        ];
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
            ->willReturnCallback(fn(string $entityId): \SimpleSAML\OpenID\Federation\EntityStatement =>
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
            ->willReturnCallback(fn(string $entityId): \SimpleSAML\OpenID\Federation\EntityStatement =>
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
            ->willReturnCallback(fn(string $entityId): \SimpleSAML\OpenID\Federation\EntityStatement =>
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
            ->willReturnCallback(fn(string $entityId): \SimpleSAML\OpenID\Federation\EntityStatement =>
                $this->configChainSample[$entityId] ?? throw new \Exception('No entity.'));

        $this->leafEntityConfigurationMock
            ->expects($this->once())
            ->method('getAuthorityHints')
            ->willReturn(['i', 't']);

        $this->trustChainBagFactoryMock->expects($this->once())->method('build');
        $this->cacheDecoratorMock->expects($this->exactly(2))->method('set');

        $this->sut()->for('l', ['i', 't']);
    }


    public function testCanResolveTrustChainForTrustAnchorOnly(): void
    {
        $this->entityStatementFetcherMock
            ->method('fromCacheOrWellKnownEndpoint')
            ->willReturnCallback(fn(string $entityId): \SimpleSAML\OpenID\Federation\EntityStatement =>
                $this->configChainSample[$entityId] ?? throw new \Exception('No entity.'));

        $this->trustChainFactoryMock->expects($this->once())->method('forTrustAnchor');

        $this->trustChainBagFactoryMock->expects($this->once())->method('build');
        $this->cacheDecoratorMock->expects($this->once())->method('set');

        $this->sut()->for('t', ['t']);
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
            ->willReturnCallback(fn(string $entityId): \SimpleSAML\OpenID\Federation\EntityStatement =>
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
            ->willReturnCallback(fn(string $entityId): \SimpleSAML\OpenID\Federation\EntityStatement =>
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


    /**
     * Let the leaf and the intermediate both point to each other's authority, so the recursion has more paths to
     * walk than the budget allows.
     */
    protected function prepareFullConfigChainSample(): void
    {
        $this->entityStatementFetcherMock
            ->method('fromCacheOrWellKnownEndpoint')
            ->willReturnCallback(fn(string $entityId): EntityStatement =>
                $this->configChainSample[$entityId] ?? throw new \Exception('No entity.'));

        $this->leafEntityConfigurationMock
            ->method('getAuthorityHints')
            ->willReturn(['i']);
        $this->intermediateEntityConfigurationMock
            ->method('getAuthorityHints')
            ->willReturn(['t']);
    }


    public function testCanLimitTotalNumberOfConfigurationFetches(): void
    {
        // Resolving l -> i -> t needs three configuration fetches, so two is one short.
        $sut = $this->sut(maxTrustChainFetches: 2);

        $this->prepareFullConfigChainSample();

        $this->loggerMock
            ->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('entity statement fetches'));

        $this->expectException(TrustChainResolutionBudgetException::class);
        $this->expectExceptionMessage('maximum allowed number of 2 entity statement fetches');

        $sut->getConfigurationChains('l', ['t']);
    }


    public function testFetchBudgetAlsoCoversSubordinateStatements(): void
    {
        // Three configuration fetches are affordable, the subordinate statement fetches that follow are not.
        $sut = $this->sut(maxTrustChainFetches: 3);

        $this->prepareFullConfigChainSample();

        $this->entityStatementFetcherMock
            ->expects($this->never())
            ->method('fromCacheOrFetchEndpoint');

        $this->expectException(TrustChainResolutionBudgetException::class);

        $sut->for('l', ['t']);
    }


    public function testFetchBudgetIsResetForEachResolution(): void
    {
        // Exactly enough for one resolution: three configurations plus two subordinate statements.
        $sut = $this->sut(maxTrustChainFetches: 5);

        $this->prepareFullConfigChainSample();

        $this->trustChainBagFactoryMock->expects($this->exactly(2))->method('build');

        $sut->for('l', ['t']);
        // Would throw if the budget spent above was carried over.
        $sut->for('l', ['t']);
    }


    public function testFetchBudgetIsResetForEachConfigurationChainsCall(): void
    {
        $sut = $this->sut(maxTrustChainFetches: 3);

        $this->prepareFullConfigChainSample();

        $this->assertCount(1, $sut->getConfigurationChains('l', ['t']));
        // Would throw if the budget spent above was carried over.
        $this->assertCount(1, $sut->getConfigurationChains('l', ['t']));
    }


    public function testCanLimitTrustChainResolutionDuration(): void
    {
        $sut = $this->getMockBuilder(TrustChainResolver::class)
            ->setConstructorArgs($this->sutConstructorArgs(trustChainResolveTimeout: 30))
            ->onlyMethods(['currentTimestamp'])
            ->getMock();

        // First call sets the deadline at 0 + 30, the second one is well past it.
        $sut->method('currentTimestamp')->willReturnOnConsecutiveCalls(0.0, 1000.0);

        $this->prepareFullConfigChainSample();

        $this->loggerMock
            ->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('maximum allowed duration'));

        $this->expectException(TrustChainResolutionBudgetException::class);
        $this->expectExceptionMessage('maximum allowed duration of 30 seconds');

        $sut->getConfigurationChains('l', ['t']);
    }


    public function testResolutionDeadlineIsCheckedBeforeCachedTrustChainLookup(): void
    {
        $sut = $this->getMockBuilder(TrustChainResolver::class)
            ->setConstructorArgs($this->sutConstructorArgs(trustChainResolveTimeout: 30))
            ->onlyMethods(['currentTimestamp'])
            ->getMock();

        // First call sets the deadline at 0 + 30, the second one is well past it.
        $sut->method('currentTimestamp')->willReturnOnConsecutiveCalls(0.0, 1000.0);

        // The deadline has to be enforced before any per trust anchor work, cache lookups included.
        $this->cacheDecoratorMock->expects($this->never())->method('get');
        $this->entityStatementFetcherMock->expects($this->never())->method('fromCacheOrWellKnownEndpoint');

        $this->expectException(TrustChainResolutionBudgetException::class);
        $this->expectExceptionMessage('maximum allowed duration of 30 seconds');

        $sut->for('l', ['t']);
    }


    public function testResolutionBudgetValuesAreClamped(): void
    {
        $sut = $this->sut(maxTrustChainFetches: 0, trustChainResolveTimeout: 0);
        $this->assertSame(1, $sut->getMaxTrustChainFetches());
        $this->assertSame(1, $sut->getTrustChainResolveTimeout());

        $sut = $this->sut(maxTrustChainFetches: 100000, trustChainResolveTimeout: 100000);
        $this->assertSame(1000, $sut->getMaxTrustChainFetches());
        $this->assertSame(300, $sut->getTrustChainResolveTimeout());
    }


    public function testPassesResolutionDeadlineDownToEachFetch(): void
    {
        $sut = $this->sut(trustChainResolveTimeout: 30);
        $observedDeadlines = [];
        $startedAt = microtime(true);

        $this->entityStatementFetcherMock
            ->method('fromCacheOrWellKnownEndpoint')
            ->willReturnCallback(function (
                string $entityId,
                mixed $wellKnownEnum = null,
                ?float $deadlineTimestamp = null,
            ) use (&$observedDeadlines): EntityStatement {
                $observedDeadlines[] = $deadlineTimestamp;

                return $this->configChainSample[$entityId] ?? throw new \Exception('No entity.');
            });

        $this->leafEntityConfigurationMock->method('getAuthorityHints')->willReturn(['i']);
        $this->intermediateEntityConfigurationMock->method('getAuthorityHints')->willReturn(['t']);

        $sut->getConfigurationChains('l', ['t']);

        $this->assertCount(3, $observedDeadlines);

        foreach ($observedDeadlines as $deadline) {
            $this->assertNotNull($deadline, 'Every fetch has to be bounded by the resolution deadline.');
            $this->assertGreaterThan($startedAt, $deadline);
            // The budget opens once the call is under way, a moment after the time captured above.
            $this->assertLessThanOrEqual($startedAt + 31, $deadline);
        }
    }


    public function testFetchDeadlineDoesNotRestartAsBudgetIsSpent(): void
    {
        $sut = $this->getMockBuilder(TrustChainResolver::class)
            ->setConstructorArgs($this->sutConstructorArgs(trustChainResolveTimeout: 30))
            ->onlyMethods(['currentTimestamp'])
            ->getMock();

        // Budget opens at 0, so the deadline is at 30. Each fetch then takes 10 seconds of it.
        $now = 0.0;
        // Bound by reference: an arrow function would capture the starting value and never see it move.
        $sut->method('currentTimestamp')->willReturnCallback(function () use (&$now): float {
            return $now;
        });

        $observedDeadlines = [];

        $this->entityStatementFetcherMock
            ->method('fromCacheOrWellKnownEndpoint')
            ->willReturnCallback(function (
                string $entityId,
                mixed $wellKnownEnum = null,
                ?float $deadlineTimestamp = null,
            ) use (
                &$observedDeadlines,
                &$now,
            ): EntityStatement {
                $observedDeadlines[] = $deadlineTimestamp;
                $now += 10.0;

                return $this->configChainSample[$entityId] ?? throw new \Exception('No entity.');
            });

        $this->leafEntityConfigurationMock->method('getAuthorityHints')->willReturn(['i']);
        $this->intermediateEntityConfigurationMock->method('getAuthorityHints')->willReturn(['t']);

        $sut->getConfigurationChains('l', ['t']);

        // One fixed point in time for the whole resolution, so each fetch inherits what is genuinely left of
        // it rather than a fresh allowance worked out before the previous fetch had spent its share.
        $this->assertSame([30.0, 30.0, 30.0], $observedDeadlines);
    }


    public function testCanGetMaxAuthorityHints(): void
    {
        $this->assertSame($this->maxAuthorityHints, $this->sut()->getMaxAuthorityHints());
    }
}
