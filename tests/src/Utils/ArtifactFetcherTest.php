<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Utils;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Exceptions\DestinationPolicyException;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\Exceptions\HttpException;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

#[CoversClass(ArtifactFetcher::class)]
final class ArtifactFetcherTest extends TestCase
{
    protected MockObject $httpClientDecoratorMock;

    protected MockObject $cacheDecoratorMock;

    protected MockObject $loggerMock;

    protected MockObject $responseMock;


    protected function setUp(): void
    {
        $this->httpClientDecoratorMock = $this->createMock(HttpClientDecorator::class);
        $this->cacheDecoratorMock = $this->createMock(CacheDecorator::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->responseMock->method('getBody')->willReturn($this->createStub(StreamInterface::class));
    }


    protected function sut(
        ?HttpClientDecorator $httpClientDecorator = null,
        ?CacheDecorator $cacheDecorator = null,
        ?LoggerInterface $logger = null,
        bool $logArtifacts = false,
        int $maxLoggedArtifactLength = ArtifactFetcher::DEFAULT_MAX_LOGGED_ARTIFACT_LENGTH,
    ): ArtifactFetcher {
        $httpClientDecorator ??= $this->httpClientDecoratorMock;
        $cacheDecorator ??= $this->cacheDecoratorMock;
        $logger ??= $this->loggerMock;

        return new ArtifactFetcher(
            $httpClientDecorator,
            $cacheDecorator,
            $logger,
            $logArtifacts,
            $maxLoggedArtifactLength,
        );
    }


    public function testKeepsTheArtifactOutOfTheLogByDefault(): void
    {
        $this->cacheDecoratorMock->method('get')->willReturn('the artifact body');

        $this->loggerMock->expects($this->once())->method('debug')
            ->with(
                $this->stringContains('found in cache'),
                $this->logicalNot($this->arrayHasKey('artifact')),
            );

        $this->assertSame('the artifact body', $this->sut()->fromCacheAsString('key'));
    }


    public function testLogsTheArtifactWhenAskedTo(): void
    {
        $this->cacheDecoratorMock->method('get')->willReturn('the artifact body');

        $this->loggerMock->expects($this->once())->method('debug')
            ->with(
                $this->stringContains('found in cache'),
                $this->arrayHasKey('artifact'),
            );

        $this->assertSame('the artifact body', $this->sut(logArtifacts: true)->fromCacheAsString('key'));
    }


    public function testCapsTheLoggedArtifact(): void
    {
        $artifact = str_repeat('a', 100);
        $this->cacheDecoratorMock->method('get')->willReturn($artifact);

        $loggedContext = [];
        $this->loggerMock->expects($this->once())->method('debug')
            ->willReturnCallback(function (string $message, array $context) use (&$loggedContext): void {
                $loggedContext = $context;
            });

        $this->sut(logArtifacts: true, maxLoggedArtifactLength: 10)->fromCacheAsString('key');

        $this->assertSame(str_repeat('a', 10), $loggedContext['artifact']);
        $this->assertSame(100, $loggedContext['artifactLength']);
        $this->assertTrue($loggedContext['artifactTruncated']);
    }


    public function testRaisesAnUnusableLoggedArtifactLength(): void
    {
        $this->cacheDecoratorMock->method('get')->willReturn('abc');

        $loggedContext = [];
        $this->loggerMock->expects($this->once())->method('debug')
            ->willReturnCallback(function (string $message, array $context) use (&$loggedContext): void {
                $loggedContext = $context;
            });

        // Zero would otherwise mean an empty string in the log rather than the shortest useful one.
        $this->sut(logArtifacts: true, maxLoggedArtifactLength: 0)->fromCacheAsString('key');

        $this->assertSame('a', $loggedContext['artifact']);
    }


    public function testReportsTheTypeOfAnUnusableCachedArtifactWithoutLoggingIt(): void
    {
        $this->cacheDecoratorMock->method('get')->willReturn(['artifact-in-array']);

        $this->loggerMock->expects($this->once())->method('warning')
            ->with(
                $this->stringContains('nexpected'),
                $this->logicalAnd(
                    $this->arrayHasKey('artifactType'),
                    $this->logicalNot($this->arrayHasKey('artifact')),
                ),
            );

        $this->assertNull($this->sut()->fromCacheAsString('key'));
    }


    public function testNeverLogsTheValueOfANonStringArtifact(): void
    {
        // Nothing but a string can be bounded by the length cap, so an oversized cached array must not be
        // handed to the logger even when artifact logging was asked for.
        $this->cacheDecoratorMock->method('get')->willReturn([str_repeat('a', 100000)]);

        $this->loggerMock->expects($this->once())->method('warning')
            ->with($this->anything(), $this->logicalNot($this->arrayHasKey('artifact')));

        $this->assertNull($this->sut(logArtifacts: true)->fromCacheAsString('key'));
    }


    public function testHoldsTheByteCapEvenWhenRepairingWidensTheArtifact(): void
    {
        $previousSubstitute = mb_substitute_character();
        // U+FFFD costs three bytes for every one-byte sequence it replaces, so repairing can push the
        // result back over the limit the cut brought it under.
        mb_substitute_character(0xFFFD);

        try {
            $this->cacheDecoratorMock->method('get')->willReturn(str_repeat("\xE2", 40));

            $loggedContext = [];
            $this->loggerMock->expects($this->once())->method('debug')
                ->willReturnCallback(function (string $message, array $context) use (&$loggedContext): void {
                    $loggedContext = $context;
                });

            $this->sut(logArtifacts: true, maxLoggedArtifactLength: 40)->fromCacheAsString('key');

            $this->assertLessThanOrEqual(40, strlen($loggedContext['artifact']));
            $this->assertTrue(mb_check_encoding($loggedContext['artifact'], 'UTF-8'));
            // Replaced bytes mean what is logged is not what was fetched, so it is reported as such.
            $this->assertTrue($loggedContext['artifactTruncated']);
        } finally {
            mb_substitute_character($previousSubstitute);
        }
    }


    public function testKeepsATruncatedLoggedArtifactEncodable(): void
    {
        // Cutting 'a€' at two bytes lands inside the multibyte character, and a record a JSON formatter
        // cannot encode would turn a successful cache read into a logging failure.
        $this->cacheDecoratorMock->method('get')->willReturn('a€');

        $loggedContext = [];
        $this->loggerMock->expects($this->once())->method('debug')
            ->willReturnCallback(function (string $message, array $context) use (&$loggedContext): void {
                $loggedContext = $context;
            });

        $this->sut(logArtifacts: true, maxLoggedArtifactLength: 2)->fromCacheAsString('key');

        $this->assertIsString($loggedContext['artifact']);
        $this->assertTrue(mb_check_encoding($loggedContext['artifact'], 'UTF-8'));
        $this->assertIsString(json_encode($loggedContext['artifact'], JSON_THROW_ON_ERROR));
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(ArtifactFetcher::class, $this->sut());
    }


    public function testReturnsNullIfCacheNotAvailable(): void
    {
        $sut = new ArtifactFetcher($this->httpClientDecoratorMock, null, $this->loggerMock);

        $this->loggerMock->expects($this->once())->method('debug')
        ->with($this->stringContains('skipping'));

        $this->assertNull($sut->fromCacheAsString('key'));
    }


    public function testReturnsNullIfNotInCache(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(null, 'key')
            ->willReturn(null);

        $this->assertNull($this->sut()->fromCacheAsString('key'));
    }


    public function testReturnsArtifactIfString(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(null, 'key')
            ->willReturn('artifact');

        $this->assertSame('artifact', $this->sut()->fromCacheAsString('key'));
    }


    public function testReturnsNullIfNotString(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(null, 'key')
            ->willReturn(['artifact-in-array']);

        $this->loggerMock->expects($this->once())->method('warning')
            ->with($this->stringContains('unexpected'));

        $this->assertNull($this->sut()->fromCacheAsString('key'));
    }


    public function testReturnsNullOnCacheFailure(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('get')
            ->with(null, 'key')
            ->willThrowException(new \Exception('Error'));

        $this->loggerMock->expects($this->once())->method('error')
            ->with($this->stringContains('error'));

        $this->assertNull($this->sut()->fromCacheAsString('key'));
    }


    public function testCanFetchFromNetwork(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())->method('request')
            ->with(HttpMethodsEnum::GET, 'uri');

        $this->assertInstanceOf(ResponseInterface::class, $this->sut()->fromNetwork('uri'));
    }


    public function testFromNetworkThrowsOnNetworkError(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())->method('request')
            ->willThrowException(new \Exception('Error'));

        $this->expectException(FetchException::class);
        $this->expectExceptionMessage('HTTP');

        $this->loggerMock->expects($this->once())->method('error')
            ->with($this->stringContains('error'));

        $this->sut()->fromNetwork('uri');
    }


    /**
     * A refused destination is a policy decision rather than a transport failure, so it keeps its own type
     * instead of becoming the fetch error every other failure here becomes.
     */
    public function testFromNetworkKeepsARefusedDestinationRecognizable(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())->method('request')
            ->willThrowException(new DestinationPolicyException('destination refused'));

        $this->loggerMock->expects($this->once())->method('error')
            ->with($this->stringContains('destination refused'));

        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('destination refused');

        $this->sut()->fromNetwork('uri');
    }


    public function testCanReadResponseBodyAsString(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())
            ->method('readResponseBodyAsString')
            ->with($this->responseMock, 512)
            ->willReturn('artifact');

        $this->assertSame('artifact', $this->sut()->readResponseBodyAsString($this->responseMock, 512));
    }


    public function testWrapsErrorWhileReadingResponseBody(): void
    {
        $this->httpClientDecoratorMock->method('readResponseBodyAsString')
            ->willThrowException(new HttpException('Response body size exceeded the limit of 10 bytes.'));

        $this->loggerMock->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('exceeded the limit'));

        $this->expectException(FetchException::class);
        $this->expectExceptionMessage('exceeded the limit');

        $this->sut()->readResponseBodyAsString($this->responseMock);
    }


    public function testCanFetchFromNetworkAsString(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())->method('request')
            ->willReturn($this->responseMock);
        $this->httpClientDecoratorMock->method('readResponseBodyAsString')->willReturn('artifact');

        $this->assertSame('artifact', $this->sut()->fromNetworkAsString('uri'));
    }


    public function testCanCacheArtifact(): void
    {
        $this->cacheDecoratorMock->expects($this->once())->method('set')
            ->with('artifact', 60, 'key');

        $this->loggerMock->expects($this->once())->method('debug')
            ->with($this->stringContains('saved'));

        $this->sut()->cacheIt('artifact', 60, 'key');
    }


    public function testSkipsCachingIfCacheNotAvailable(): void
    {
        $this->loggerMock->expects($this->once())->method('debug')
            ->with($this->stringContains('skipping'));

        $sut = new ArtifactFetcher($this->httpClientDecoratorMock, null, $this->loggerMock);

        $sut->cacheIt('artifact', 60, 'key');
    }


    public function testCanLogCacheError(): void
    {
        $this->loggerMock->expects($this->once())->method('error')
            ->with($this->stringContains('error'));

        $this->cacheDecoratorMock->expects($this->once())->method('set')
            ->willThrowException(new \Exception('Error'));

        $this->sut()->cacheIt('artifact', 60, 'key');
    }
}
