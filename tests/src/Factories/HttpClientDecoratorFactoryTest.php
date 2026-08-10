<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Factories;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Exceptions\DestinationPolicyException;
use SimpleSAML\OpenID\Factories\HttpClientDecoratorFactory;
use SimpleSAML\OpenID\Network\AddressPinner;
use SimpleSAML\OpenID\Network\AddressResolver;
use SimpleSAML\OpenID\Network\AddressValidator;
use SimpleSAML\OpenID\Network\DestinationGuardMiddleware;
use SimpleSAML\OpenID\Network\DestinationPolicy;
use SimpleSAML\OpenID\Network\ValidatedDestination;
use SimpleSAML\OpenID\Utils\SizeLimitedStream;

#[CoversClass(HttpClientDecoratorFactory::class)]
#[UsesClass(HttpClientDecorator::class)]
#[UsesClass(DestinationPolicy::class)]
#[UsesClass(DestinationGuardMiddleware::class)]
#[UsesClass(AddressValidator::class)]
#[UsesClass(AddressResolver::class)]
#[UsesClass(AddressPinner::class)]
#[UsesClass(ValidatedDestination::class)]
#[UsesClass(SizeLimitedStream::class)]
final class HttpClientDecoratorFactoryTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Psr\Log\LoggerInterface
     */
    protected MockObject $loggerMock;


    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
    }


    protected function sut(?LoggerInterface $logger = null): HttpClientDecoratorFactory
    {
        return new HttpClientDecoratorFactory($logger);
    }


    /**
     * Factory that reports weakened configuration to the logger mock.
     */
    protected function sutWithLogger(): HttpClientDecoratorFactory
    {
        return $this->sut($this->loggerMock);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(HttpClientDecoratorFactory::class, $this->sut());
    }


    public function testBuildWithClient(): void
    {
        $client = new \GuzzleHttp\Client();
        $decorator = $this->sut()->build($client);
        $this->assertSame($client, $decorator->client);
    }


    public function testBuildWithoutClientAndConfig(): void
    {
        $decorator = $this->sut()->build();
        $this->assertInstanceOf(HttpClientDecorator::class, $decorator);
        $this->assertNotEmpty($decorator->client->getConfig('allow_redirects'));
    }


    public function testBuildWithConfig(): void
    {
        $config = ['timeout' => 10.0, 'connect_timeout' => 5.0];
        $decorator = $this->sut()->build(null, $config);

        $this->assertEqualsWithDelta(10.0, $decorator->client->getConfig('timeout'), PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(5.0, $decorator->client->getConfig('connect_timeout'), PHP_FLOAT_EPSILON);
        $this->assertNotEmpty($decorator->client->getConfig('allow_redirects'));
    }


    public function testBuildWithConfigOverridingDefaults(): void
    {
        $decorator = $this->sut()->build(null, ['allow_redirects' => false]);
        $this->assertFalse($decorator->client->getConfig('allow_redirects'));
    }


    public function testRedirectDefaultsAreRestrictive(): void
    {
        $allowRedirects = $this->sut()->build()->client->getConfig('allow_redirects');

        $this->assertSame(3, $allowRedirects['max']);
        // Federation endpoints are https only, so a redirect must not be able to downgrade the connection.
        $this->assertSame(['https'], $allowRedirects['protocols']);
        $this->assertTrue($allowRedirects['strict']);
        $this->assertFalse($allowRedirects['referer']);
    }


    public function testWarnsWhenTimeoutIsDisabled(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('"timeout" is set to 0, which disables the timeout'));

        $this->sutWithLogger()->build(null, ['timeout' => 0]);
    }


    public function testWarnsWhenConnectTimeoutIsDisabled(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('"connect_timeout"'));

        $this->sutWithLogger()->build(null, ['connect_timeout' => 0]);
    }


    public function testRespectsDisabledTimeoutDespiteWarning(): void
    {
        // The caller's intent is honoured, the configuration is only reported.
        $decorator = $this->sut()->build(null, ['timeout' => 0]);

        $this->assertSame(0, $decorator->client->getConfig('timeout'));
    }


    public function testDoesNotWarnForValidTimeouts(): void
    {
        $this->loggerMock->expects($this->never())->method('warning');

        $this->sutWithLogger()->build(null, ['timeout' => 30, 'connect_timeout' => 5.0]);
    }


    public function testWarnsWhenRedirectHardeningIsUndone(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('"allow_redirects" is set to true'));

        $this->sutWithLogger()->build(null, ['allow_redirects' => true]);
    }


    public function testDoesNotWarnWhenRedirectsAreDisabledEntirely(): void
    {
        $this->loggerMock->expects($this->never())->method('warning');

        $this->sutWithLogger()->build(null, ['allow_redirects' => false]);
    }


    public function testPartialRedirectConfigKeepsHardenedDefaults(): void
    {
        // A shallow merge would drop protocols/strict/referer here, and Guzzle would then backfill them from
        // its own permissive defaults rather than from ours.
        $decorator = $this->sut()->build(null, ['allow_redirects' => ['track_redirects' => true]]);
        $allowRedirects = $decorator->client->getConfig('allow_redirects');

        $this->assertTrue($allowRedirects['track_redirects']);
        $this->assertSame(3, $allowRedirects['max']);
        $this->assertSame(['https'], $allowRedirects['protocols']);
        $this->assertTrue($allowRedirects['strict']);
    }


    public function testPartialRedirectConfigStillLetsCallerWin(): void
    {
        $decorator = $this->sut()->build(null, ['allow_redirects' => ['max' => 7]]);
        $allowRedirects = $decorator->client->getConfig('allow_redirects');

        $this->assertSame(7, $allowRedirects['max']);
        $this->assertSame(['https'], $allowRedirects['protocols']);
    }


    public function testEmptyRedirectArrayStillDisablesRedirects(): void
    {
        // Guzzle reads an empty array as "do not follow redirects", so it must not be backfilled.
        $decorator = $this->sut()->build(null, ['allow_redirects' => []]);

        $this->assertSame([], $decorator->client->getConfig('allow_redirects'));
    }


    public function testWarnsWhenRedirectProtocolsPermitHttp(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('protocols other than https'));

        $this->sutWithLogger()->build(null, ['allow_redirects' => ['protocols' => ['http', 'https']]]);
    }


    public function testWarnsWhenRedirectHopLimitIsRaised(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('permits 7 redirect hops'));

        $this->sutWithLogger()->build(null, ['allow_redirects' => ['max' => 7]]);
    }


    public function testDoesNotWarnWhenRedirectHopLimitIsLowered(): void
    {
        $this->loggerMock->expects($this->never())->method('warning');

        $this->sutWithLogger()->build(null, ['allow_redirects' => ['max' => 1]]);
    }


    public function testKnowsRequestTimeoutFromDefaults(): void
    {
        $this->assertEqualsWithDelta(
            (float)HttpClientDecorator::DEFAULT_HTTP_CLIENT_CONFIG[RequestOptions::TIMEOUT],
            $this->sut()->build()->getRequestTimeout(),
            PHP_FLOAT_EPSILON,
        );
    }


    public function testKnowsRequestTimeoutFromConfig(): void
    {
        $this->assertEqualsWithDelta(
            30.0,
            $this->sut()->build(null, ['timeout' => 30])->getRequestTimeout(),
            PHP_FLOAT_EPSILON,
        );
    }


    public function testRequestTimeoutIsUnknownForPreBuiltClient(): void
    {
        // Its configuration belongs to whoever built it, so it is not assumed here.
        $this->assertNull($this->sut()->build(new \GuzzleHttp\Client())->getRequestTimeout());
    }


    public function testDisabledTimeoutIsReportedAsUnknown(): void
    {
        // Otherwise it would act as a ceiling of zero and reject every request.
        $this->assertNull($this->sut()->build(null, ['timeout' => 0])->getRequestTimeout());
    }


    public function testReportsThatPreBuiltClientSkipsDefaults(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with($this->stringContains('pre-built HTTP client'));

        $this->sutWithLogger()->build(new \GuzzleHttp\Client());
    }


    /**
     * An empty configuration is what a deployment that sets no HTTP options produces, which makes it exactly
     * the case that must not end up with an unguarded client.
     */
    public function testGuardsClientBuiltWithoutAnyConfig(): void
    {
        $decorator = $this->sut()->build();

        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('host 127.0.0.1 is not a public address');

        $decorator->request(HttpMethodsEnum::GET, 'https://127.0.0.1/jwks');
    }


    public function testGuardsClientBuiltWithConfig(): void
    {
        $decorator = $this->sut()->build(null, ['timeout' => 10]);

        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('is not a public address');

        $decorator->request(HttpMethodsEnum::GET, 'https://[::1]/jwks');
    }


    /**
     * Guzzle derives its handler from client options it refuses to combine with a handler given to it, so
     * the guard has to go onto the stack it built rather than be handed to it as one.
     */
    public function testLeavesGuzzleToDeriveItsOwnHandler(): void
    {
        if (ClientInterface::MAJOR_VERSION < 8) {
            $this->markTestSkipped('Connection cap client options arrived in Guzzle 8.');
        }

        $decorator = $this->sut()->build(null, ['max_host_connections' => 4]);

        $this->expectException(DestinationPolicyException::class);

        $decorator->request(HttpMethodsEnum::GET, 'https://127.0.0.1/jwks');
    }


    public function testGuardsACallerSuppliedHandlerStack(): void
    {
        $handlerStack = HandlerStack::create(new MockHandler([new Response(200, [], 'ok')]));
        $decorator = $this->sut()->build(null, ['handler' => $handlerStack]);

        $this->expectException(DestinationPolicyException::class);

        $decorator->request(HttpMethodsEnum::GET, 'https://127.0.0.1/jwks');
    }


    /**
     * A bare callable is the whole stack as far as Guzzle is concerned, so the guard has to wrap it rather
     * than be pushed onto something that is not there.
     */
    public function testGuardsACallerSuppliedBareHandler(): void
    {
        $decorator = $this->sut()->build(null, ['handler' => new MockHandler([new Response(200, [], 'ok')])]);

        $this->expectException(DestinationPolicyException::class);

        $decorator->request(HttpMethodsEnum::GET, 'https://127.0.0.1/jwks');
    }


    public function testAppliesASuppliedDestinationPolicy(): void
    {
        $decorator = $this->sut()->build(
            null,
            ['handler' => new MockHandler([new Response(200, [], 'ok')])],
            destinationPolicy: new DestinationPolicy(allowedCidrs: ['127.0.0.0/8']),
        );

        $response = $decorator->request(HttpMethodsEnum::GET, 'https://127.0.0.1/jwks');

        $this->assertSame(200, $response->getStatusCode());
    }


    /**
     * Building twice from one handler stack has to leave one guard on it, otherwise every destination would
     * be checked, and every host resolved, once per build.
     */
    public function testBuildingTwiceLeavesOneGuardOnAHandlerStack(): void
    {
        $addressResolverMock = $this->createMock(AddressResolver::class);
        $addressResolverMock->expects($this->once())
            ->method('resolve')
            ->willReturn(['93.184.216.34']);

        $destinationPolicy = new DestinationPolicy(addressResolver: $addressResolverMock);
        $handlerStack = HandlerStack::create(new MockHandler([new Response(200, [], 'ok')]));

        $this->sut()->build(null, ['handler' => $handlerStack], destinationPolicy: $destinationPolicy);
        $decorator = $this->sut()->build(
            null,
            ['handler' => $handlerStack],
            destinationPolicy: $destinationPolicy,
        );

        $decorator->request(HttpMethodsEnum::GET, 'https://example.org/jwks');
    }


    /**
     * A stack shared between policies has to keep a guard for each of them: replacing one with the other
     * would quietly hand the first client the second policy's, possibly wider, permissions.
     */
    public function testTwoPoliciesOnOneHandlerStackBothApply(): void
    {
        $handlerStack = HandlerStack::create(new MockHandler([new Response(200, [], 'ok')]));
        $permissivePolicy = new DestinationPolicy(allowedCidrs: ['127.0.0.0/8']);

        $this->sut()->build(null, ['handler' => $handlerStack], destinationPolicy: $permissivePolicy);
        $decorator = $this->sut()->build(null, ['handler' => $handlerStack]);

        // Allowed by the first policy, refused by the second, and the second was not able to displace it.
        $this->expectException(DestinationPolicyException::class);

        $decorator->request(HttpMethodsEnum::GET, 'https://127.0.0.1/jwks');
    }


    /**
     * A handler that is not usable is a configuration error, and it has to keep surfacing as one rather than
     * be quietly swapped for the default transport, which would put real requests on the network.
     */
    public function testDoesNotReplaceAnUnusableHandlerWithTheDefaultOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->sut()->build(null, ['handler' => 'not-a-handler']);
    }


    public function testWarnsThatPreBuiltClientIsNotGuarded(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('destination policy is not applied to a pre-built HTTP client'));

        $this->sutWithLogger()->build(new \GuzzleHttp\Client());
    }
}
