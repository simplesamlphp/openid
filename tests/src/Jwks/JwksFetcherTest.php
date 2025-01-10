<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jwks;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Exceptions\JwksException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jwks\Factories\SignedJwksFactory;
use SimpleSAML\OpenID\Jwks\JwksDecorator;
use SimpleSAML\OpenID\Jwks\JwksFetcher;
use PHPUnit\Framework\TestCase;

#[CoversClass(JwksFetcher::class)]
class JwksFetcherTest extends TestCase
{
    protected MockObject $httpClientDecoratorMock;
    protected MockObject $jwksFactoryMock;
    protected MockObject $signedJwksFactoryMock;
    protected MockObject $maxCacheDurationDecoratorMock;
    protected MockObject $helpersMock;
    protected MockObject $cacheDecoratorMock;
    protected MockObject $loggerMock;
    protected MockObject $jsonHelperMock;

    protected array $jwksArraySample = [
        'keys' => [
            [
                'alg' => 'RS256',
                'use' => 'sig',
                'kty' => 'RSA',
                // phpcs:ignore
                'n' => 'pJgG9F_lwc2cFEC1l6q0fjJYxKPbtVGqJpDggDpDR8MgfbH0jUZP_RvhJGpl_09Bp-PfibLiwxchHZlrCx-fHQyGMaBRivUfq_p12ECEXMaFUcasCP6cyNrDfa5Uchumau4WeC21nYI1NMawiMiWFcHpLCQ7Ul8NMaCM_dkeruhm_xG0ZCqfwu30jOyCsnZdE0izJwPTfBRLpLyivu8eHpwjoIzmwqo8H-ZsbqR0vdRu20-MNS78ppTxwK3QmJhU6VO2r730F6WH9xJd_XUDuVeM4_6Z6WVDXw3kQF-jlpfcssPP303nbqVmfFZSUgS8buToErpMqevMIKREShsjMQ',
                'e' => 'AQAB',
                'kid' => 'F4VFObNusj3PHmrHxpqh4GNiuFHlfh-2s6xMJ95fLYA',
            ],
        ],
    ];

    protected function setUp(): void
    {
        $this->httpClientDecoratorMock = $this->createMock(HttpClientDecorator::class);
        $this->jwksFactoryMock = $this->createMock(JwksFactory::class);
        $this->signedJwksFactoryMock = $this->createMock(SignedJwksFactory::class);
        $this->maxCacheDurationDecoratorMock = $this->createMock(DateIntervalDecorator::class);
        $this->helpersMock = $this->createMock(Helpers::class);
        $this->cacheDecoratorMock = $this->createMock(CacheDecorator::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->jsonHelperMock = $this->createMock(Helpers\Json::class);
        $this->helpersMock->method('json')->willReturn($this->jsonHelperMock);
    }

    protected function sut(
        ?HttpClientDecorator $httpClientDecorator = null,
        ?JwksFactory $jwksFactory = null,
        ?SignedJwksFactory $signedJwksFactory = null,
        ?DateIntervalDecorator $maxCacheDurationDecorator = null,
        ?Helpers $helpers = null,
        ?CacheDecorator $cacheDecorator = null,
        ?LoggerInterface $logger = null,
    ): JwksFetcher {
        $httpClientDecorator ??= $this->httpClientDecoratorMock;
        $jwksFactory ??= $this->jwksFactoryMock;
        $signedJwksFactory ??= $this->signedJwksFactoryMock;
        $maxCacheDurationDecorator ??= $this->maxCacheDurationDecoratorMock;
        $helpers ??= $this->helpersMock;
        $cacheDecorator ??= $this->cacheDecoratorMock;
        $logger ??= $this->loggerMock;

        return new JwksFetcher(
            $httpClientDecorator,
            $jwksFactory,
            $signedJwksFactory,
            $maxCacheDurationDecorator,
            $helpers,
            $cacheDecorator,
            $logger,
        );
    }

    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(JwksFetcher::class, $this->sut());
    }

    public function testCanGetFromCache(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(null, 'uri')
            ->willReturn('jwks-json');
        $this->jsonHelperMock->expects($this->once())->method('decode')
            ->with('jwks-json')
            ->willReturn($this->jwksArraySample);
        $this->jwksFactoryMock->expects($this->once())->method('fromKeyData')
            ->with($this->jwksArraySample);

        $this->assertInstanceOf(JwksDecorator::class, $this->sut()->fromCache('uri'));
    }

    public function testLogsErrorInCaseOfCacheError(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(null, 'uri')
            ->willThrowException(new \Exception('Error'));

        $this->loggerMock->expects($this->once())->method('error')
        ->with($this->stringContains('cache'));

        $this->assertNull($this->sut()->fromCache('uri'));
    }

    public function testReturnsNullInCaseOfNonStringValueInCache(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(null, 'uri')
            ->willReturn(123);

        $this->assertNull($this->sut()->fromCache('uri'));
    }

    public function testLogsErrorInCaseOfCacheValueDecodeError(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(null, 'uri')
            ->willReturn('jwks-json');

        $this->jsonHelperMock->expects($this->once())->method('decode')
            ->with('jwks-json')
            ->willThrowException(new JsonException('Error'));

        $this->loggerMock->expects($this->atLeastOnce())->method('error')
            ->with($this->stringContains('decode'));

        $this->assertNull($this->sut()->fromCache('uri'));
    }

    public function testLogsErrorInCaseOfNonArrayCacheValue(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(null, 'uri')
            ->willReturn('jwks-json');

        $this->jsonHelperMock->expects($this->once())->method('decode')
            ->with('jwks-json')
            ->willReturn(123);

        $this->loggerMock->expects($this->atLeastOnce())->method('error')
            ->with($this->stringContains('type'));

        $this->assertNull($this->sut()->fromCache('uri'));
    }


    public function testLogsErrorInCaseOfInvalidArrayCacheValue(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(null, 'uri')
            ->willReturn('jwks-json');

        $this->jsonHelperMock->expects($this->once())->method('decode')
            ->with('jwks-json')
            ->willReturn(['invalid']);

        $this->loggerMock->expects($this->atLeastOnce())->method('error')
            ->with($this->stringContains('format'));

        $this->assertNull($this->sut()->fromCache('uri'));
    }

    public function testCanGetFromCacheOrJwksUri(): void
    {
        $this->markTestIncomplete('TODO mivanci');
    }
}
