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
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Core\Factories\RequestObjectFactory as ConnectRequestObjectFactory;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Factories\AlgorithmManagerDecoratorFactory;
use SimpleSAML\OpenID\Factories\CacheDecoratorFactory;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\HttpClientDecoratorFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerDecoratorFactory;
use SimpleSAML\OpenID\Federation\Factories\RequestObjectFactory as FederationRequestObjectFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jar\Factories\RequestObjectFactory as JarRequestObjectFactory;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsDecoratorBuilderFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\RequestObject;
use SimpleSAML\OpenID\RequestObject\RequestObjectFactories;
use SimpleSAML\OpenID\RequestObject\RequestObjectParser;
use SimpleSAML\OpenID\RequestObject\RequestUriFetcher;
use SimpleSAML\OpenID\Serializers\JwsSerializerBag;
use SimpleSAML\OpenID\Serializers\JwsSerializerEnum;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
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
#[UsesClass(RequestObjectFactories::class)]
#[UsesClass(RequestObjectParser::class)]
#[UsesClass(ConnectRequestObjectFactory::class)]
#[UsesClass(JarRequestObjectFactory::class)]
#[UsesClass(FederationRequestObjectFactory::class)]
#[UsesClass(AlgorithmManagerDecorator::class)]
#[UsesClass(AlgorithmManagerDecoratorFactory::class)]
#[UsesClass(JwsSerializerManagerDecoratorFactory::class)]
#[UsesClass(JwsSerializerManagerDecorator::class)]
#[UsesClass(JwsDecoratorBuilderFactory::class)]
#[UsesClass(JwsDecoratorBuilder::class)]
#[UsesClass(JwsVerifierDecoratorFactory::class)]
#[UsesClass(JwsVerifierDecorator::class)]
#[UsesClass(JwksDecoratorFactory::class)]
#[UsesClass(ClaimFactory::class)]
#[UsesClass(Helpers::class)]
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

        $this->assertInstanceOf(RequestObjectFactories::class, $jar->requestObjectFactories());
        $this->assertInstanceOf(RequestObjectParser::class, $jar->requestObjectParser());
        $this->assertInstanceOf(ConnectRequestObjectFactory::class, $jar->connectRequestObjectFactory());
        $this->assertInstanceOf(JarRequestObjectFactory::class, $jar->jarRequestObjectFactory());
        $this->assertInstanceOf(FederationRequestObjectFactory::class, $jar->federationRequestObjectFactory());
        $this->assertInstanceOf(JwsDecoratorBuilder::class, $jar->jwsDecoratorBuilder());
        $this->assertInstanceOf(JwsVerifierDecorator::class, $jar->jwsVerifierDecorator());
        $this->assertInstanceOf(JwsSerializerManagerDecorator::class, $jar->jwsSerializerManagerDecorator());
        $this->assertInstanceOf(AlgorithmManagerDecorator::class, $jar->algorithmManagerDecorator());
        $this->assertInstanceOf(AlgorithmManagerDecoratorFactory::class, $jar->algorithmManagerDecoratorFactory());
        $this->assertInstanceOf(
            JwsSerializerManagerDecoratorFactory::class,
            $jar->jwsSerializerManagerDecoratorFactory(),
        );
        $this->assertInstanceOf(JwsDecoratorBuilderFactory::class, $jar->jwsDecoratorBuilderFactory());
        $this->assertInstanceOf(JwsVerifierDecoratorFactory::class, $jar->jwsVerifierDecoratorFactory());
        $this->assertInstanceOf(JwksDecoratorFactory::class, $jar->jwksDecoratorFactory());
        $this->assertInstanceOf(ClaimFactory::class, $jar->claimFactory());
        $this->assertInstanceOf(Helpers::class, $jar->helpers());
        $this->assertInstanceOf(DateIntervalDecorator::class, $jar->timestampValidationLeewayDecorator());
    }


    public function testGettersReturnCachedInstances(): void
    {
        $jar = $this->sut();

        $this->assertSame($jar->dateIntervalDecoratorFactory(), $jar->dateIntervalDecoratorFactory());
        $this->assertSame($jar->httpClientDecoratorFactory(), $jar->httpClientDecoratorFactory());
        $this->assertSame($jar->cacheDecoratorFactory(), $jar->cacheDecoratorFactory());
        $this->assertSame($jar->artifactFetcher(), $jar->artifactFetcher());
        $this->assertSame($jar->requestUriFetcher(), $jar->requestUriFetcher());

        $this->assertSame($jar->requestObjectFactories(), $jar->requestObjectFactories());
        $this->assertSame($jar->requestObjectParser(), $jar->requestObjectParser());
        $this->assertSame($jar->connectRequestObjectFactory(), $jar->connectRequestObjectFactory());
        $this->assertSame($jar->jarRequestObjectFactory(), $jar->jarRequestObjectFactory());
        $this->assertSame($jar->federationRequestObjectFactory(), $jar->federationRequestObjectFactory());
        $this->assertSame($jar->jwsDecoratorBuilder(), $jar->jwsDecoratorBuilder());
        $this->assertSame($jar->jwsVerifierDecorator(), $jar->jwsVerifierDecorator());
        $this->assertSame($jar->jwsSerializerManagerDecorator(), $jar->jwsSerializerManagerDecorator());
        $this->assertSame($jar->algorithmManagerDecorator(), $jar->algorithmManagerDecorator());
        $this->assertSame($jar->algorithmManagerDecoratorFactory(), $jar->algorithmManagerDecoratorFactory());
        $this->assertSame($jar->jwsSerializerManagerDecoratorFactory(), $jar->jwsSerializerManagerDecoratorFactory());
        $this->assertSame($jar->jwsDecoratorBuilderFactory(), $jar->jwsDecoratorBuilderFactory());
        $this->assertSame($jar->jwsVerifierDecoratorFactory(), $jar->jwsVerifierDecoratorFactory());
        $this->assertSame($jar->jwksDecoratorFactory(), $jar->jwksDecoratorFactory());
        $this->assertSame($jar->claimFactory(), $jar->claimFactory());
        $this->assertSame($jar->helpers(), $jar->helpers());
    }


    public function testCanInstantiateWithCustomCache(): void
    {
        $cacheMock = $this->createStub(CacheInterface::class);
        $jar = $this->sut(cache: $cacheMock);

        $this->assertInstanceOf(CacheDecorator::class, $jar->cacheDecorator());
    }
}
