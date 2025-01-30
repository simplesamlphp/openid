<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jwks;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Exceptions\HttpException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksFactory;
use SimpleSAML\OpenID\Jwks\Factories\SignedJwksFactory;
use SimpleSAML\OpenID\Jwks\JwksDecorator;
use SimpleSAML\OpenID\Jwks\JwksFetcher;
use SimpleSAML\OpenID\Jwks\SignedJwks;

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
    protected MockObject $typeHelperMock;

    protected MockObject $responseMock;
    protected MockObject $responseBodyMock;

    protected MockObject $signedJwksMock;

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

        $this->typeHelperMock = $this->createMock(Helpers\Type::class);
        $this->helpersMock->method('type')->willReturn($this->typeHelperMock);

        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->responseBodyMock = $this->createMock(StreamInterface::class);
        $this->responseMock->method('getBody')->willReturn($this->responseBodyMock);

        $this->signedJwksMock = $this->createMock(SignedJwks::class);
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
        $this->typeHelperMock->expects($this->once())
            ->method('ensureArrayWithKeysAsStrings')
            ->willReturnArgument(0);
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

    public function testCanGetFromJwksUri(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())->method('request')
            ->with(HttpMethodsEnum::GET, 'uri')
            ->willReturn($this->responseMock);

        $this->responseBodyMock->expects($this->once())->method('getContents')
            ->willReturn('jwks-json');

        $this->jsonHelperMock->expects($this->once())->method('decode')
            ->with('jwks-json')
            ->willReturn($this->jwksArraySample);

        $this->typeHelperMock->expects($this->once())
            ->method('ensureArrayWithKeysAsStrings')
            ->willReturnArgument(0);

        $this->jwksFactoryMock->expects($this->once())->method('fromKeyData')
            ->with($this->jwksArraySample);

        $this->cacheDecoratorMock->expects($this->once())->method('set')
            ->with('jwks-json', $this->anything(), 'uri');

        $this->sut()->fromJwksUri('uri');
    }

    public function testJwksUriReturnsNullOnHttpError(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())->method('request')
            ->with(HttpMethodsEnum::GET, 'uri')
            ->willThrowException(new HttpException('Error'));

        $this->loggerMock->expects($this->atLeastOnce())->method('error')
            ->with($this->stringContains('URI'));

        $this->assertNull($this->sut()->fromJwksUri('uri'));
    }

    public function testJwksUriReturnsNullOnJsonDecodeError(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())->method('request')
            ->with(HttpMethodsEnum::GET, 'uri')
            ->willReturn($this->responseMock);

        $this->responseBodyMock->expects($this->once())->method('getContents')
            ->willReturn('jwks-json');

        $this->jsonHelperMock->expects($this->once())->method('decode')
            ->with('jwks-json')
            ->willThrowException(new JsonException('Error'));

        $this->loggerMock->expects($this->atLeastOnce())->method('error')
            ->with($this->stringContains('decode'));

        $this->assertNull($this->sut()->fromJwksUri('uri'));
    }

    public function testJwksUriLogsErrorInCaseOfCacheSetError(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())->method('request')
            ->with(HttpMethodsEnum::GET, 'uri')
            ->willReturn($this->responseMock);

        $this->responseBodyMock->expects($this->once())->method('getContents')
            ->willReturn('jwks-json');

        $this->jsonHelperMock->expects($this->once())->method('decode')
            ->with('jwks-json')
            ->willReturn($this->jwksArraySample);

        $this->typeHelperMock->expects($this->once())
            ->method('ensureArrayWithKeysAsStrings')
            ->willReturnArgument(0);

        $this->jwksFactoryMock->expects($this->once())->method('fromKeyData')
            ->with($this->jwksArraySample);

        $this->cacheDecoratorMock->expects($this->once())->method('set')
            ->with('jwks-json', $this->anything(), 'uri')
            ->willThrowException(new \Exception('Error'));

        $this->loggerMock->expects($this->atLeastOnce())->method('error')
            ->with($this->stringContains('cache'));

        $this->sut()->fromJwksUri('uri');
    }

    public function testCanGetFromCacheOrJwksUri(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(null, 'uri')
            ->willReturn(null);

        $this->responseMock->method('getStatusCode')->willReturn(404);

        $this->httpClientDecoratorMock->expects($this->once())->method('request')
            ->with(HttpMethodsEnum::GET, 'uri')
            ->willReturn($this->responseMock);

        $this->responseBodyMock->expects($this->once())->method('getContents')
            ->willReturn('jwks-json');

        $this->jsonHelperMock->expects($this->once())->method('decode')
            ->with('jwks-json')
            ->willReturn($this->jwksArraySample);

        $this->typeHelperMock->expects($this->once())
            ->method('ensureArrayWithKeysAsStrings')
            ->willReturnArgument(0);

        $this->jwksFactoryMock->expects($this->once())->method('fromKeyData')
            ->with($this->jwksArraySample);

        $this->assertInstanceOf(JwksDecorator::class, $this->sut()->fromCacheOrJwksUri('uri'));
    }

    public function testCanGetFromSignedJwksUri(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())->method('request')
            ->with(HttpMethodsEnum::GET, 'uri')
            ->willReturn($this->responseMock);

        $this->responseBodyMock->expects($this->once())->method('getContents')
            ->willReturn('token');

        $this->signedJwksMock->expects($this->once())->method('verifyWithKeySet');
        $this->signedJwksMock->method('jsonSerialize')->willReturn($this->jwksArraySample);

        $this->signedJwksFactoryMock->expects($this->once())->method('fromToken')
            ->with('token')
            ->willReturn($this->signedJwksMock);

        $this->jsonHelperMock->expects($this->once())->method('encode')
            ->with($this->jwksArraySample)
            ->willReturn('jwks-json');

        $this->jwksFactoryMock->expects($this->once())->method('fromKeyData')
            ->with($this->jwksArraySample);

        $this->cacheDecoratorMock->expects($this->once())->method('set')
            ->with('jwks-json', $this->anything(), 'uri');

        $this->sut()->fromSignedJwksUri('uri', ['not-important-for-sut']);
    }

    public function testSignedJwksUriTakesExpClaimIntoAccountForCaching(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())->method('request')
            ->with(HttpMethodsEnum::GET, 'uri')
            ->willReturn($this->responseMock);

        $this->responseBodyMock->expects($this->once())->method('getContents')
            ->willReturn('token');

        $expirationTime = time() + 60;
        $this->signedJwksMock->expects($this->once())->method('verifyWithKeySet');
        $this->signedJwksMock->method('jsonSerialize')->willReturn($this->jwksArraySample);
        $this->signedJwksMock->expects($this->once())->method('getExpirationTime')
            ->willReturn($expirationTime);

        $this->signedJwksFactoryMock->expects($this->once())->method('fromToken')
            ->with('token')
            ->willReturn($this->signedJwksMock);

        $this->jsonHelperMock->expects($this->once())->method('encode')
            ->with($this->jwksArraySample)
            ->willReturn('jwks-json');

        $this->jwksFactoryMock->expects($this->once())->method('fromKeyData')
            ->with($this->jwksArraySample);

        $this->maxCacheDurationDecoratorMock->expects($this->once())
            ->method('lowestInSecondsComparedToExpirationTime')
            ->with($expirationTime)
            ->willReturn(60);

        $this->cacheDecoratorMock->expects($this->once())->method('set')
            ->with('jwks-json', 60, 'uri');

        $this->sut()->fromSignedJwksUri('uri', ['not-important-for-sut']);
    }

    public function testSignedJwksUriReturnsNullOnHttpError(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())->method('request')
            ->with(HttpMethodsEnum::GET, 'uri')
            ->willThrowException(new HttpException('Error'));

        $this->loggerMock->expects($this->atLeastOnce())->method('error')
            ->with($this->stringContains('URI'));

        $this->sut()->fromSignedJwksUri('uri', ['not-important-for-sut']);
    }

    public function testSignedJwksUriLogsErrorOnCacheSetError(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())->method('request')
            ->with(HttpMethodsEnum::GET, 'uri')
            ->willReturn($this->responseMock);

        $this->responseBodyMock->expects($this->once())->method('getContents')
            ->willReturn('token');

        $this->signedJwksMock->method('jsonSerialize')->willReturn($this->jwksArraySample);

        $this->signedJwksFactoryMock->expects($this->once())->method('fromToken')
            ->with('token')
            ->willReturn($this->signedJwksMock);

        $this->jsonHelperMock->expects($this->once())->method('encode')
            ->with($this->jwksArraySample)
            ->willReturn('jwks-json');

        $this->jwksFactoryMock->expects($this->once())->method('fromKeyData')
            ->with($this->jwksArraySample);

        $this->cacheDecoratorMock->expects($this->once())->method('set')
            ->with('jwks-json', $this->anything(), 'uri')
            ->willThrowException(new \Exception('Error'));

        $this->loggerMock->expects($this->atLeastOnce())->method('error')
            ->with($this->stringContains('cache'));

        $this->sut()->fromSignedJwksUri('uri', ['not-important-for-sut']);
    }

    public function testCanGetFromCacheOrSignedJwksUri(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(null, 'uri')
            ->willReturn(null);

        $this->httpClientDecoratorMock->expects($this->once())->method('request')
            ->with(HttpMethodsEnum::GET, 'uri')
            ->willReturn($this->responseMock);

        $this->responseBodyMock->expects($this->once())->method('getContents')
            ->willReturn('token');

        $this->signedJwksMock->method('jsonSerialize')->willReturn($this->jwksArraySample);

        $this->signedJwksFactoryMock->expects($this->once())->method('fromToken')
            ->with('token')
            ->willReturn($this->signedJwksMock);

        $this->jsonHelperMock->expects($this->once())->method('encode')
            ->with($this->jwksArraySample)
            ->willReturn('jwks-json');

        $this->jwksFactoryMock->expects($this->once())->method('fromKeyData')
            ->with($this->jwksArraySample);

        $this->cacheDecoratorMock->expects($this->once())->method('set')
            ->with('jwks-json', $this->anything(), 'uri');

        $this->sut()->fromCacheOrSignedJwksUri('uri', ['not-important-for-sut']);
    }
}
