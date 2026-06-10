<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID;

use DateInterval;
use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Factories\CacheDecoratorFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\HttpClientDecoratorFactory;
use SimpleSAML\OpenID\RequestObject;
use SimpleSAML\OpenID\RequestObject\RequestUriFetcher;
use SimpleSAML\OpenID\Serializers\JwsSerializerBag;
use SimpleSAML\OpenID\Serializers\JwsSerializerEnum;
use SimpleSAML\OpenID\SupportedAlgorithms;
use SimpleSAML\OpenID\SupportedSerializers;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

#[CoversClass(RequestObject::class)]
#[UsesClass(DateIntervalDecorator::class)]
#[UsesClass(DateIntervalDecoratorFactory::class)]
#[UsesClass(HttpClientDecorator::class)]
#[UsesClass(HttpClientDecoratorFactory::class)]
#[UsesClass(CacheDecorator::class)]
#[UsesClass(CacheDecoratorFactory::class)]
#[UsesClass(ArtifactFetcher::class)]
#[UsesClass(RequestUriFetcher::class)]
#[UsesClass(SupportedAlgorithms::class)]
#[UsesClass(SupportedSerializers::class)]
#[UsesClass(SignatureAlgorithmBag::class)]
#[UsesClass(SignatureAlgorithmEnum::class)]
#[UsesClass(JwsSerializerBag::class)]
#[UsesClass(JwsSerializerEnum::class)]
final class RequestObjectTest extends TestCase
{
    protected function sut(
        ?DateInterval $timestampValidationLeeway = null,
        ?LoggerInterface $logger = null,
        ?ArtifactFetcher $artifactFetcher = null,
        ?RequestUriFetcher $requestUriFetcher = null,
        ?Client $client = null,
        ?CacheInterface $cache = null,
    ): RequestObject {
        $timestampValidationLeeway ??= new DateInterval('PT1M');

        return new RequestObject(
            new SupportedAlgorithms(
                new SignatureAlgorithmBag(
                    SignatureAlgorithmEnum::none,
                    SignatureAlgorithmEnum::RS256,
                ),
            ),
            new SupportedSerializers(),
            $timestampValidationLeeway,
            $logger,
            $artifactFetcher,
            $requestUriFetcher,
            $client,
            $cache,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(RequestObject::class, $this->sut());
    }


    public function testGettersReturnCorrectInstances(): void
    {
        $jar = $this->sut();

        $this->assertInstanceOf(DateIntervalDecoratorFactory::class, $jar->dateIntervalDecoratorFactory());
        $this->assertInstanceOf(HttpClientDecoratorFactory::class, $jar->httpClientDecoratorFactory());
        $this->assertInstanceOf(CacheDecoratorFactory::class, $jar->cacheDecoratorFactory());
        $this->assertNotInstanceOf(\SimpleSAML\OpenID\Decorators\CacheDecorator::class, $jar->cacheDecorator());

        // Call fetcher getters which build fetchers
        $this->assertInstanceOf(ArtifactFetcher::class, $jar->artifactFetcher());
        $this->assertInstanceOf(RequestUriFetcher::class, $jar->requestUriFetcher());
    }


    public function testGettersReturnCachedInstances(): void
    {
        $jar = $this->sut();

        $this->assertSame($jar->dateIntervalDecoratorFactory(), $jar->dateIntervalDecoratorFactory());
        $this->assertSame($jar->httpClientDecoratorFactory(), $jar->httpClientDecoratorFactory());
        $this->assertSame($jar->cacheDecoratorFactory(), $jar->cacheDecoratorFactory());
        $this->assertSame($jar->artifactFetcher(), $jar->artifactFetcher());
        $this->assertSame($jar->requestUriFetcher(), $jar->requestUriFetcher());
    }


    public function testCanInstantiateWithCustomCache(): void
    {
        $cacheMock = $this->createStub(CacheInterface::class);
        $jar = $this->sut(cache: $cacheMock);

        $this->assertInstanceOf(CacheDecorator::class, $jar->cacheDecorator());
    }
}
