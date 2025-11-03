<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Federation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\TrustMarkException;
use SimpleSAML\OpenID\Federation\Claims\TrustMarkIssuersClaimBag;
use SimpleSAML\OpenID\Federation\Claims\TrustMarkIssuersClaimValue;
use SimpleSAML\OpenID\Federation\Claims\TrustMarkOwnersClaimBag;
use SimpleSAML\OpenID\Federation\Claims\TrustMarkOwnersClaimValue;
use SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimBag;
use SimpleSAML\OpenID\Federation\Claims\TrustMarksClaimValue;
use SimpleSAML\OpenID\Federation\EntityStatement;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkDelegationFactory;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkFactory;
use SimpleSAML\OpenID\Federation\TrustChainResolver;
use SimpleSAML\OpenID\Federation\TrustMark;
use SimpleSAML\OpenID\Federation\TrustMarkDelegation;
use SimpleSAML\OpenID\Federation\TrustMarkValidator;

#[CoversClass(TrustMarkValidator::class)]
final class TrustMarkValidatorTest extends TestCase
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

    protected MockObject $trustMarkOwnersClaimBagMock;

    protected MockObject $trustMarkOwnersClaimValueMock;

    protected MockObject $trustMarkDelegationMock;

    protected MockObject $trustMarkIssuersClaimBagMock;

    protected MockObject $trustMarkIssuersClaimValueMock;


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

        $this->trustMarkOwnersClaimBagMock = $this->createMock(TrustMarkOwnersClaimBag::class);
        $this->trustMarkOwnersClaimValueMock = $this->createMock(TrustMarkOwnersClaimValue::class);

        $this->trustMarkDelegationMock = $this->createMock(TrustMarkDelegation::class);

        $this->trustMarkIssuersClaimBagMock = $this->createMock(TrustMarkIssuersClaimBag::class);
        $this->trustMarkIssuersClaimValueMock = $this->createMock(TrustMarkIssuersClaimValue::class);
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
                'trustMarkType',
                'leafEntityId',
                'trustAnchorId',
            )
            ->willReturn('trustMarkType');

        $this->assertTrue(
            $this->sut()->isValidationCachedFor(
                'trustMarkType',
                'leafEntityId',
                'trustAnchorId',
            ),
        );
    }


    public function testIsValidationCachedForReturnsFalseIfNotCached(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(
                null,
                'trustMarkType',
                'leafEntityId',
                'trustAnchorId',
            )
            ->willReturn(null);

        $this->assertFalse(
            $this->sut()->isValidationCachedFor(
                'trustMarkType',
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
                'trustMarkType',
                'leafEntityId',
                'trustAnchorId',
            ),
        );
    }


    public function testFromCacheOrDoForTrustMarkTypeChecksCache(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(
                null,
                'trustMarkType',
                'leafEntityId',
                'trustAnchorId',
            )->willReturn('trustMarkType');

        $this->leafEntityConfigurationMock->expects($this->never())->method('getTrustMarks');

        $this->sut()->fromCacheOrDoForTrustMarkType(
            'trustMarkType',
            $this->leafEntityConfigurationMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testFromCacheOrDoForTrustMarkTypeRuns(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get');
        $this->leafEntityConfigurationMock->expects($this->once())->method('getTrustMarks')
            ->willReturn($this->trustMarksClaimBagMock);
        $this->trustMarksClaimBagMock->expects($this->once())->method('getAllFor')
            ->with('trustMarkType')
            ->willReturn([$this->trustMarksClaimValueMock]);
        $this->trustMarksClaimValueMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkFactoryMock->expects($this->once())->method('fromToken')
            ->willReturn($this->trustMarkMock);
        $this->trustMarkMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkMock->method('getSubject')->willReturn('leafEntityId');
        $this->trustChainResolverMock->expects($this->once())->method('for');
        $this->trustMarkMock->expects($this->once())->method('verifyWithKeySet');
        $this->cacheDecoratorMock->expects($this->once())->method('set')
            ->with('trustMarkType');

        $this->sut()->fromCacheOrDoForTrustMarkType(
            'trustMarkType',
            $this->leafEntityConfigurationMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testDoForTrustMarkTypeThrowsIfNoTrustMarks(): void
    {
        $this->cacheDecoratorMock->expects($this->never())->method('get');
        $this->leafEntityConfigurationMock->expects($this->once())->method('getTrustMarks')
            ->willReturn(null);

        $this->expectException(TrustMarkException::class);
        $this->expectExceptionMessage('available');

        $this->sut()->doForTrustMarkType(
            'trustMarkType',
            $this->leafEntityConfigurationMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testDoForTrustMarkTypeThrowsIfNoTrustMarkWithGivenId(): void
    {
        $this->cacheDecoratorMock->expects($this->never())->method('get');
        $this->leafEntityConfigurationMock->expects($this->once())->method('getTrustMarks')
            ->willReturn($this->trustMarksClaimBagMock);
        $this->trustMarksClaimBagMock->expects($this->once())->method('getAllFor')
            ->with('trustMarkType')
            ->willReturn([]);


        $this->expectException(TrustMarkException::class);
        $this->expectExceptionMessage('no claims');

        $this->sut()->doForTrustMarkType(
            'trustMarkType',
            $this->leafEntityConfigurationMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testDoForTrustMarkTypeThrowsForInvalidClaimValue(): void
    {
        $this->cacheDecoratorMock->expects($this->never())->method('get');
        $this->leafEntityConfigurationMock->expects($this->once())->method('getTrustMarks')
            ->willReturn($this->trustMarksClaimBagMock);
        $this->trustMarksClaimBagMock->expects($this->once())->method('getAllFor')
            ->with('trustMarkType')
            ->willReturn([$this->trustMarksClaimValueMock]);
        $this->trustMarksClaimValueMock->method('getTrustMarkType')->willReturn('invalid');


        $this->expectException(TrustMarkException::class);
        $this->expectExceptionMessage('Could not validate');

        $this->sut()->doForTrustMarkType(
            'trustMarkType',
            $this->leafEntityConfigurationMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testFromCacheOrDoForTrustMarksClaimValueChecksCache(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(
                null,
                'trustMarkType',
                'leafEntityId',
                'trustAnchorId',
            )->willReturn('trustMarkType');
        $this->trustMarksClaimValueMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkFactoryMock->expects($this->never())->method('fromToken');

        $this->sut()->fromCacheOrDoForTrustMarksClaimValue(
            $this->trustMarksClaimValueMock,
            $this->leafEntityConfigurationMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testFromCacheOrDoForTrustMarksClaimValueRuns(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get');
        $this->trustMarksClaimValueMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkFactoryMock->expects($this->once())->method('fromToken')
            ->willReturn($this->trustMarkMock);
        $this->trustMarkMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkMock->method('getSubject')->willReturn('leafEntityId');
        $this->trustChainResolverMock->expects($this->once())->method('for');
        $this->trustMarkMock->expects($this->once())->method('verifyWithKeySet');
        $this->cacheDecoratorMock->expects($this->once())->method('set')
            ->with('trustMarkType');

        $this->sut()->fromCacheOrDoForTrustMarksClaimValue(
            $this->trustMarksClaimValueMock,
            $this->leafEntityConfigurationMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testValidateTrustMarksClaimValueThrowsForDifferentPayloadValues(): void
    {
        $this->trustMarksClaimValueMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarksClaimValueMock->method('getOtherClaims')
            ->willReturn(['key' => 'value']);
        $this->trustMarkFactoryMock->expects($this->once())->method('fromToken')
            ->willReturn($this->trustMarkMock);
        $this->trustMarkMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkMock->method('getPayload')
            ->willReturn(['key' => 'differentValue']);

        $this->expectException(TrustMarkException::class);
        $this->expectExceptionMessage('different');

        $this->sut()->validateTrustMarksClaimValue(
            $this->trustMarksClaimValueMock,
        );
    }


    public function testFromCacheOrDoForTrustMarkChecksCache(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(
                null,
                'trustMarkType',
                'leafEntityId',
                'trustAnchorId',
            )->willReturn('trustMarkType');

        $this->trustMarkMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkMock->expects($this->never())->method('getSubject');

        $this->sut()->fromCacheOrDoForTrustMark(
            $this->trustMarkMock,
            $this->leafEntityConfigurationMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testFromCacheOrDoForTrustMarkRuns(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get');
        $this->trustMarkMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkMock->method('getSubject')->willReturn('leafEntityId');
        $this->trustChainResolverMock->expects($this->once())->method('for');
        $this->trustMarkMock->expects($this->once())->method('verifyWithKeySet');
        $this->cacheDecoratorMock->expects($this->once())->method('set')
            ->with('trustMarkType');

        $this->sut()->fromCacheOrDoForTrustMark(
            $this->trustMarkMock,
            $this->leafEntityConfigurationMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testDoForTrustMarkTakesIntoAccountTrustMarkExpirationForCacheTtl(): void
    {
        $this->cacheDecoratorMock->expects($this->never())->method('get');
        $leafEntityConfigurationExpirationTime = time() + 120;
        $this->leafEntityConfigurationMock->method('getExpirationTime')
            ->willReturn($leafEntityConfigurationExpirationTime);
        $this->trustMarkMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkMock->method('getSubject')->willReturn('leafEntityId');
        $trustMarkExpirationTime = time() + 60;
        $this->trustMarkMock->method('getExpirationTime')->willReturn($trustMarkExpirationTime);
        $this->maxCacheDurationDecoratorMock->expects($this->once())
            ->method('lowestInSecondsComparedToExpirationTime')
            ->with($trustMarkExpirationTime);
        $this->trustChainResolverMock->expects($this->once())->method('for');
        $this->trustMarkMock->expects($this->once())->method('verifyWithKeySet');
        $this->cacheDecoratorMock->expects($this->once())->method('set')
            ->with('trustMarkType');

        $this->sut()->doForTrustMark(
            $this->trustMarkMock,
            $this->leafEntityConfigurationMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testDoForTrustMarksLogsCacheError(): void
    {
        $this->cacheDecoratorMock->expects($this->never())->method('get');
        $this->trustMarkMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkMock->method('getSubject')->willReturn('leafEntityId');
        $this->trustChainResolverMock->expects($this->once())->method('for');
        $this->trustMarkMock->expects($this->once())->method('verifyWithKeySet');
        $this->cacheDecoratorMock->expects($this->once())->method('set')
            ->willThrowException(new \Exception('error'));
        $this->loggerMock->expects($this->atLeastOnce())->method('error')
            ->with($this->stringContains('Error caching'));

        $this->sut()->doForTrustMark(
            $this->trustMarkMock,
            $this->leafEntityConfigurationMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testDoForTrustMarkCanHandleTrustAnchorAsTrustMarkIssuer(): void
    {
        $this->cacheDecoratorMock->expects($this->never())->method('get');
        $this->trustMarkMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkMock->method('getSubject')->willReturn('leafEntityId');
        $this->trustMarkMock->method('getIssuer')->willReturn('trustAnchorId');
        $this->trustAnchorConfigurationMock->expects($this->once())->method('getJwks');
        $this->trustChainResolverMock->expects($this->never())->method('for');
        $this->trustMarkMock->expects($this->once())->method('verifyWithKeySet');
        $this->cacheDecoratorMock->expects($this->once())->method('set');

        $this->sut()->doForTrustMark(
            $this->trustMarkMock,
            $this->leafEntityConfigurationMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testValidateSubjectClaimThrowsForInvalidSubject(): void
    {
        $this->trustMarkMock->method('getSubject')->willReturn('invalidSubject');

        $this->expectException(TrustMarkException::class);
        $this->expectExceptionMessage('subject');

        $this->sut()->validateSubjectClaim(
            $this->trustMarkMock,
            $this->leafEntityConfigurationMock,
        );
    }


    public function testValidateTrustChainForTrustMarkIssuerThrowsForInvalidChain(): void
    {
        $this->trustMarkMock->method('getIssuer')
            ->willReturn('trustMarkIssuerId');
        $this->trustChainResolverMock->expects($this->once())->method('for')
            ->with('trustMarkIssuerId', ['trustAnchorId'])
            ->willThrowException(new \Exception('error'));

        $this->expectException(TrustMarkException::class);
        $this->expectExceptionMessage('Trust Chain for Issuer');

        $this->sut()->validateTrustChainForTrustMarkIssuer(
            $this->trustMarkMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testValidateTrustMarkSignatureThrowsForInvalidSignature(): void
    {
        $this->trustMarkMock->expects($this->once())
            ->method('verifyWithKeySet')
            ->willThrowException(new \Exception('error'));

        $this->expectException(TrustMarkException::class);
        $this->expectExceptionMessage('signature');

        $trustMarkIssuerEntityConfiguration = $this->createMock(EntityStatement::class);

        $this->sut()->validateTrustMarkSignature(
            $this->trustMarkMock,
            $trustMarkIssuerEntityConfiguration,
        );
    }


    public function testCanValidateTrustMarkDelegation(): void
    {
        $this->trustAnchorConfigurationMock->expects($this->once())
            ->method('getTrustMarkOwners')
            ->willReturn($this->trustMarkOwnersClaimBagMock);
        $this->trustMarkOwnersClaimBagMock->expects($this->once())
            ->method('get')
            ->with('trustMarkType')
            ->willReturn($this->trustMarkOwnersClaimValueMock);

        $this->trustMarkMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkMock->method('getDelegation')->willReturn('delegationToken');

        $this->trustMarkDelegationFactoryMock->expects($this->once())
            ->method('fromToken')
            ->with('delegationToken')
            ->willReturn($this->trustMarkDelegationMock);

        $this->trustMarkDelegationMock->expects($this->once())->method('verifyWithKeySet');

        $this->trustMarkDelegationMock->method('getIssuer')->willReturn('trustMarkOwnerId');
        $this->trustMarkOwnersClaimValueMock->method('getSubject')->willReturn('trustMarkOwnerId');

        $this->trustMarkMock->method('getIssuer')->willReturn('trustMarkIssuerId');
        $this->trustMarkDelegationMock->method('getSubject')->willReturn('trustMarkIssuerId');

        $this->trustMarkDelegationMock->method('getTrustMarkType')->willReturn('trustMarkType');

        $this->sut()->validateTrustMarkDelegation(
            $this->trustMarkMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testValidateTrustMarkDelegationSkipsIfTrustMarkOwnerNotDefinedOnTrustAnchor(): void
    {
        $this->trustAnchorConfigurationMock->expects($this->once())
            ->method('getTrustMarkOwners')
            ->willReturn($this->trustMarkOwnersClaimBagMock);
        $this->trustMarkMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkOwnersClaimBagMock->expects($this->once())
            ->method('get')
            ->with('trustMarkType')
            ->willReturn(null);

        $debugMessageContainedSkipped = false;
        $this->loggerMock->expects($this->atLeastOnce())->method('debug')
            ->willReturnCallback(function (string $message) use (&$debugMessageContainedSkipped): void {
                $debugMessageContainedSkipped = $debugMessageContainedSkipped ||
                str_contains($message, 'Skipping');
            });
        $this->trustMarkMock->expects($this->never())->method('getDelegation');

        $this->sut()->validateTrustMarkDelegation(
            $this->trustMarkMock,
            $this->trustAnchorConfigurationMock,
        );

        $this->assertTrue($debugMessageContainedSkipped);
    }


    public function testValidateTrustMarkDelegationThrowsForMissingDelegationClaim(): void
    {
        $this->trustAnchorConfigurationMock->expects($this->once())
            ->method('getTrustMarkOwners')
            ->willReturn($this->trustMarkOwnersClaimBagMock);
        $this->trustMarkOwnersClaimBagMock->expects($this->once())
            ->method('get')
            ->with('trustMarkType')
            ->willReturn($this->trustMarkOwnersClaimValueMock);

        $this->trustMarkMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkMock->method('getDelegation')->willReturn(null);

        $this->expectException(TrustMarkException::class);
        $this->expectExceptionMessage('Delegation claim');

        $this->sut()->validateTrustMarkDelegation(
            $this->trustMarkMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testValidateTrustMarkDelegationThrowsForInvalidSignature(): void
    {
        $this->trustAnchorConfigurationMock->expects($this->once())
            ->method('getTrustMarkOwners')
            ->willReturn($this->trustMarkOwnersClaimBagMock);
        $this->trustMarkOwnersClaimBagMock->expects($this->once())
            ->method('get')
            ->with('trustMarkType')
            ->willReturn($this->trustMarkOwnersClaimValueMock);

        $this->trustMarkMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkMock->method('getDelegation')->willReturn('delegationToken');

        $this->trustMarkDelegationFactoryMock->expects($this->once())
            ->method('fromToken')
            ->with('delegationToken')
            ->willReturn($this->trustMarkDelegationMock);

        $this->trustMarkDelegationMock->expects($this->once())->method('verifyWithKeySet')
            ->willThrowException(new \Exception('error'));

        $this->expectException(TrustMarkException::class);
        $this->expectExceptionMessage('signature');

        $this->sut()->validateTrustMarkDelegation(
            $this->trustMarkMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testValidateTrustMarkDelegationThrowsForInvalidDelegationIssuer(): void
    {
        $this->trustAnchorConfigurationMock->expects($this->once())
            ->method('getTrustMarkOwners')
            ->willReturn($this->trustMarkOwnersClaimBagMock);
        $this->trustMarkOwnersClaimBagMock->expects($this->once())
            ->method('get')
            ->with('trustMarkType')
            ->willReturn($this->trustMarkOwnersClaimValueMock);

        $this->trustMarkMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkMock->method('getDelegation')->willReturn('delegationToken');

        $this->trustMarkDelegationFactoryMock->expects($this->once())
            ->method('fromToken')
            ->with('delegationToken')
            ->willReturn($this->trustMarkDelegationMock);

        $this->trustMarkDelegationMock->expects($this->once())->method('verifyWithKeySet');

        $this->trustMarkDelegationMock->method('getIssuer')->willReturn('invalidOwnerId');
        $this->trustMarkOwnersClaimValueMock->method('getSubject')->willReturn('trustMarkOwnerId');

        $this->expectException(TrustMarkException::class);
        $this->expectExceptionMessage('Trust Mark Delegation Issuer');

        $this->sut()->validateTrustMarkDelegation(
            $this->trustMarkMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testValidateTrustMarkDelegationThrowsForInvalidTrustMarkIssuer(): void
    {
        $this->trustAnchorConfigurationMock->expects($this->once())
            ->method('getTrustMarkOwners')
            ->willReturn($this->trustMarkOwnersClaimBagMock);
        $this->trustMarkOwnersClaimBagMock->expects($this->once())
            ->method('get')
            ->with('trustMarkType')
            ->willReturn($this->trustMarkOwnersClaimValueMock);

        $this->trustMarkMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkMock->method('getDelegation')->willReturn('delegationToken');

        $this->trustMarkDelegationFactoryMock->expects($this->once())
            ->method('fromToken')
            ->with('delegationToken')
            ->willReturn($this->trustMarkDelegationMock);

        $this->trustMarkDelegationMock->expects($this->once())->method('verifyWithKeySet');

        $this->trustMarkDelegationMock->method('getIssuer')->willReturn('trustMarkOwnerId');
        $this->trustMarkOwnersClaimValueMock->method('getSubject')->willReturn('trustMarkOwnerId');

        $this->trustMarkMock->method('getIssuer')->willReturn('invalidTrustMarkIssuerId');
        $this->trustMarkDelegationMock->method('getSubject')->willReturn('trustMarkIssuerId');

        $this->expectException(TrustMarkException::class);
        $this->expectExceptionMessage('Trust Mark Issuer');

        $this->sut()->validateTrustMarkDelegation(
            $this->trustMarkMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testValidateTrustMarkDelegationThrowsForInvalidTrustMarkType(): void
    {
        $this->trustAnchorConfigurationMock->expects($this->once())
            ->method('getTrustMarkOwners')
            ->willReturn($this->trustMarkOwnersClaimBagMock);
        $this->trustMarkOwnersClaimBagMock->expects($this->once())
            ->method('get')
            ->with('trustMarkType')
            ->willReturn($this->trustMarkOwnersClaimValueMock);

        $this->trustMarkMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkMock->method('getDelegation')->willReturn('delegationToken');

        $this->trustMarkDelegationFactoryMock->expects($this->once())
            ->method('fromToken')
            ->with('delegationToken')
            ->willReturn($this->trustMarkDelegationMock);

        $this->trustMarkDelegationMock->expects($this->once())->method('verifyWithKeySet');

        $this->trustMarkDelegationMock->method('getIssuer')->willReturn('trustMarkOwnerId');
        $this->trustMarkOwnersClaimValueMock->method('getSubject')->willReturn('trustMarkOwnerId');

        $this->trustMarkMock->method('getIssuer')->willReturn('trustMarkIssuerId');
        $this->trustMarkDelegationMock->method('getSubject')->willReturn('trustMarkIssuerId');

        $this->trustMarkDelegationMock->method('getTrustMarkType')->willReturn('otherTrustMarkType');

        $this->expectException(TrustMarkException::class);
        $this->expectExceptionMessage('Trust Mark Type');

        $this->sut()->validateTrustMarkDelegation(
            $this->trustMarkMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testDoForTrustMarkValidatesTrustMarkIssuers(): void
    {
        $this->trustMarkMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkMock->method('getSubject')->willReturn('leafEntityId');
        $this->trustAnchorConfigurationMock->expects($this->once())->method('getTrustMarkIssuers');

        $this->sut()->doForTrustMark(
            $this->trustMarkMock,
            $this->leafEntityConfigurationMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testValidateTrustMarkIssuersPassesIfNoTrustMarkIssuersDefined(): void
    {
        $this->trustMarkMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkMock->method('getSubject')->willReturn('leafEntityId');
        $this->trustMarkIssuersClaimBagMock->expects($this->once())->method('get')
            ->with('trustMarkType')
            ->willReturn(null);
        $this->trustAnchorConfigurationMock->expects($this->once())->method('getTrustMarkIssuers')
            ->willReturn($this->trustMarkIssuersClaimBagMock);

        $this->trustMarkMock->expects($this->atLeastOnce())->method('getTrustMarkType');

        $this->sut()->validateTrustMarkIssuers(
            $this->trustMarkMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testValidateTrustMarkIssuersPassesForEmptyTrustMarkIssuers(): void
    {
        $this->trustMarkMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkMock->method('getSubject')->willReturn('leafEntityId');
        $this->trustMarkIssuersClaimValueMock->method('getTrustMarkIssuers')->willReturn([]);
        $this->trustMarkIssuersClaimBagMock->expects($this->once())->method('get')
            ->with('trustMarkType')
            ->willReturn($this->trustMarkIssuersClaimValueMock);
        $this->trustAnchorConfigurationMock->expects($this->once())->method('getTrustMarkIssuers')
            ->willReturn($this->trustMarkIssuersClaimBagMock);

        $this->trustMarkMock->expects($this->atLeastOnce())->method('getTrustMarkType');

        $this->sut()->validateTrustMarkIssuers(
            $this->trustMarkMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testValidateTrustMarkIssuersThrowsForIssuerNotAdvertisedByTrustAnchor(): void
    {
        $this->trustMarkMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkMock->method('getSubject')->willReturn('leafEntityId');
        $this->trustMarkIssuersClaimValueMock->method('getTrustMarkIssuers')
            ->willReturn(['https://example.com/trust-mark-issuer']);
        $this->trustMarkIssuersClaimBagMock->expects($this->once())->method('get')
            ->with('trustMarkType')
            ->willReturn($this->trustMarkIssuersClaimValueMock);
        $this->trustAnchorConfigurationMock->expects($this->once())->method('getTrustMarkIssuers')
            ->willReturn($this->trustMarkIssuersClaimBagMock);

        $this->expectException(TrustMarkException::class);
        $this->expectExceptionMessage('not issued by any');
        ;

        $this->sut()->validateTrustMarkIssuers(
            $this->trustMarkMock,
            $this->trustAnchorConfigurationMock,
        );
    }


    public function testValidateTrustMarkIssuersPassesForIssuerAdvertisedByTrustAnchor(): void
    {
        $this->trustMarkMock->method('getTrustMarkType')->willReturn('trustMarkType');
        $this->trustMarkMock->method('getSubject')->willReturn('leafEntityId');
        $this->trustMarkMock->method('getIssuer')->willReturn('trustMarkIssuerId');

        $this->trustMarkIssuersClaimValueMock->method('getTrustMarkIssuers')
            ->willReturn(['trustMarkIssuerId']);
        $this->trustMarkIssuersClaimBagMock->expects($this->once())->method('get')
            ->with('trustMarkType')
            ->willReturn($this->trustMarkIssuersClaimValueMock);
        $this->trustAnchorConfigurationMock->expects($this->once())->method('getTrustMarkIssuers')
            ->willReturn($this->trustMarkIssuersClaimBagMock);

        $this->sut()->validateTrustMarkIssuers(
            $this->trustMarkMock,
            $this->trustAnchorConfigurationMock,
        );
    }
}
