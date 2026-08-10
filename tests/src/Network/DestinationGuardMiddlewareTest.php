<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Network;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\AddressPinningModeEnum;
use SimpleSAML\OpenID\Exceptions\DestinationPolicyException;
use SimpleSAML\OpenID\Network\AddressPinner;
use SimpleSAML\OpenID\Network\AddressResolver;
use SimpleSAML\OpenID\Network\AddressValidator;
use SimpleSAML\OpenID\Network\DestinationGuardMiddleware;
use SimpleSAML\OpenID\Network\DestinationPolicy;
use SimpleSAML\OpenID\Network\ValidatedDestination;

#[CoversClass(DestinationGuardMiddleware::class)]
#[UsesClass(DestinationPolicy::class)]
#[UsesClass(AddressValidator::class)]
#[UsesClass(AddressResolver::class)]
#[UsesClass(AddressPinner::class)]
#[UsesClass(ValidatedDestination::class)]
final class DestinationGuardMiddlewareTest extends TestCase
{
    /**
     * @var array<string, list<string>>
     */
    protected const ADDRESSES = [
        'first.example.org' => ['93.184.216.34'],
        'second.example.org' => ['93.184.216.35', '2001:4860:4860::8888'],
        'inward.example.org' => ['10.0.0.5'],
    ];

    protected const REDIRECT_OPTIONS = [
        'max' => 3,
        'protocols' => ['https'],
        'strict' => true,
        'referer' => false,
    ];


    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\SimpleSAML\OpenID\Network\AddressResolver
     */
    protected MockObject $addressResolverMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Psr\Log\LoggerInterface
     */
    protected MockObject $loggerMock;

    /**
     * Hosts the policy was asked to resolve, in order, so that per-hop validation can be shown rather than
     * inferred from where the middleware sits.
     *
     * @var list<string>
     */
    protected array $resolvedHosts = [];

    /**
     * Request options as they reached the handler, per request.
     *
     * @var list<array{string, array<string,mixed>}>
     */
    protected array $handledRequests = [];


    protected function setUp(): void
    {
        $this->resolvedHosts = [];
        $this->handledRequests = [];

        $this->addressResolverMock = $this->createMock(AddressResolver::class);
        $this->addressResolverMock->method('resolve')->willReturnCallback(
            function (string $host): array {
                $this->resolvedHosts[] = $host;

                return self::ADDRESSES[$host] ?? [];
            },
        );

        $this->loggerMock = $this->createMock(LoggerInterface::class);
    }


    /**
     * @param list<string> $allowedHosts
     */
    protected function policy(
        AddressPinningModeEnum $addressPinningMode = AddressPinningModeEnum::Preferred,
        array $allowedHosts = [],
    ): DestinationPolicy {
        return new DestinationPolicy(
            allowedHosts: $allowedHosts,
            addressPinningMode: $addressPinningMode,
            logger: $this->loggerMock,
            addressResolver: $this->addressResolverMock,
        );
    }


    protected function sut(
        ?DestinationPolicy $destinationPolicy = null,
        ?AddressPinner $addressPinner = null,
    ): DestinationGuardMiddleware {
        return new DestinationGuardMiddleware(
            $destinationPolicy ?? $this->policy(),
            // Told that it is running on cURL, as the library tells it for a client it built itself.
            $addressPinner ?? new AddressPinner(handlerIsCurl: true),
            $this->loggerMock,
        );
    }


    /**
     * A client with the guard in the same place the library puts it: pushed onto the stack, and therefore
     * below the redirect middleware.
     *
     * @param list<\GuzzleHttp\Psr7\Response> $responses
     */
    protected function client(DestinationGuardMiddleware $middleware, array $responses): Client
    {
        $handlerStack = HandlerStack::create(new MockHandler($responses));
        $handlerStack->push($middleware, 'openid_destination_guard');
        // Pushed last, so it sits below the guard and sees the options the guard passes down.
        $handlerStack->push($this->requestSpy(), 'spy');

        return new Client([
            'handler' => $handlerStack,
            RequestOptions::ALLOW_REDIRECTS => self::REDIRECT_OPTIONS,
        ]);
    }


    protected function requestSpy(): callable
    {
        return fn(callable $next): callable => function (RequestInterface $request, array $options) use ($next) {
            $this->handledRequests[] = [(string)$request->getUri(), $options];

            return $next($request, $options);
        };
    }


    /**
     * @return list<string>
     */
    protected function pinsFromHandledRequests(): array
    {
        // Named through defined(), so that these assertions still run where the cURL extension is absent,
        // which is a runtime the Preferred mode is meant to keep working.
        $resolveOption = defined('CURLOPT_RESOLVE') ? CURLOPT_RESOLVE : null;

        return array_map(
            function (array $handledRequest) use ($resolveOption): string {
                $curlOptions = $handledRequest[1][RequestOptions::CURL] ?? [];

                return (is_array($curlOptions) && !is_null($resolveOption)) ?
                implode(' ', (array)($curlOptions[$resolveOption] ?? [])) :
                '';
            },
            $this->handledRequests,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(DestinationGuardMiddleware::class, $this->sut());
    }


    public function testAllowsAPublicDestination(): void
    {
        $client = $this->client($this->sut(), [new Response(200, [], 'ok')]);

        $this->assertSame(200, $client->request('GET', 'https://first.example.org/start')->getStatusCode());
        $this->assertSame(['first.example.org'], $this->resolvedHosts);
    }


    public function testRefusesADestinationResolvingInward(): void
    {
        $client = $this->client($this->sut(), [new Response(200, [], 'ok')]);

        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('resolves to 10.0.0.5');

        $client->request('GET', 'https://inward.example.org/start');
    }


    /**
     * The guard has to run for each hop rather than for the original request alone, which is what a chain
     * shows and stack order on its own does not.
     */
    public function testRunsOnEveryRedirectHop(): void
    {
        $client = $this->client($this->sut(), [
            new Response(302, ['Location' => 'https://second.example.org/next']),
            new Response(200, [], 'ok'),
        ]);

        $response = $client->request('GET', 'https://first.example.org/start');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['first.example.org', 'second.example.org'], $this->resolvedHosts);
    }


    /**
     * A first hop that passes and then redirects inward is the whole attack.
     */
    public function testRefusesARedirectHopThatGoesInward(): void
    {
        $client = $this->client($this->sut(), [
            new Response(302, ['Location' => 'https://inward.example.org/next']),
            new Response(200, [], 'should not be reached'),
        ]);

        try {
            $client->request('GET', 'https://first.example.org/start');
            $this->fail('Expected the inward redirect hop to be refused.');
        } catch (DestinationPolicyException $destinationPolicyException) {
            $this->assertStringContainsString('inward.example.org', $destinationPolicyException->getMessage());
            $this->assertSame(['first.example.org', 'inward.example.org'], $this->resolvedHosts);
            $this->assertCount(1, $this->handledRequests);
        }
    }


    public function testPinsEveryHopToTheAddressesItValidated(): void
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('Address pinning needs the cURL extension.');
        }

        $client = $this->client($this->sut(), [
            new Response(302, ['Location' => 'https://second.example.org/next']),
            new Response(200, [], 'ok'),
        ]);

        $client->request('GET', 'https://first.example.org/start');

        $this->assertSame(
            [
                'first.example.org:443:93.184.216.34',
                'second.example.org:443:93.184.216.35,[2001:4860:4860::8888]',
            ],
            $this->pinsFromHandledRequests(),
        );
    }


    public function testDoesNotPinWhenPinningIsDisabled(): void
    {
        $client = $this->client(
            $this->sut($this->policy(AddressPinningModeEnum::Disabled)),
            [new Response(200, [], 'ok')],
        );

        $client->request('GET', 'https://first.example.org/start');

        $this->assertSame([''], $this->pinsFromHandledRequests());
    }


    public function testDoesNotPinADestinationWithNothingToPin(): void
    {
        $client = $this->client(
            $this->sut($this->policy(allowedHosts: ['rp.internal.example'])),
            [new Response(200, [], 'ok'), new Response(200, [], 'ok')],
        );

        $client->request('GET', 'https://rp.internal.example/x');
        $client->request('GET', 'https://93.184.216.34/x');

        $this->assertSame(['', ''], $this->pinsFromHandledRequests());
    }


    /**
     * Where the handler can not be told which address to use, the weaker guarantee is reported rather than
     * left to be inferred - once, so that it does not become one line per request.
     */
    public function testReportsUnavailablePinningOnceAndCarriesOn(): void
    {
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('without pinning the validated address'));

        $client = $this->client(
            $this->sut(addressPinner: $this->unsupportedPinner()),
            [new Response(200, [], 'ok'), new Response(200, [], 'ok')],
        );

        $client->request('GET', 'https://first.example.org/start');
        $client->request('GET', 'https://second.example.org/start');

        $this->assertSame(['', ''], $this->pinsFromHandledRequests());
    }


    public function testRefusesWhenPinningIsRequiredButUnavailable(): void
    {
        $client = $this->client(
            $this->sut($this->policy(AddressPinningModeEnum::Required), $this->unsupportedPinner()),
            [new Response(200, [], 'ok')],
        );

        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('can not be pinned');

        $client->request('GET', 'https://first.example.org/start');
    }


    /**
     * Redirect following handed to libcurl happens underneath this middleware, so the hops it takes would
     * never be seen here. Guzzle 8 rejects the option itself; Guzzle 7 passes it through.
     */
    public function testRefusesACurlOptionThatFollowsRedirectsBelowTheGuard(): void
    {
        if (!defined('CURLOPT_FOLLOWLOCATION')) {
            $this->markTestSkipped('Needs the cURL extension to name the option.');
        }

        $client = $this->client($this->sut(), [new Response(200, [], 'ok')]);

        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('CURLOPT_FOLLOWLOCATION');

        $client->request('GET', 'https://first.example.org/start', [
            RequestOptions::CURL => [CURLOPT_FOLLOWLOCATION => true],
        ]);
    }


    public function testRefusesACurlOptionThatReplacesTheValidatedUri(): void
    {
        if (!defined('CURLOPT_URL')) {
            $this->markTestSkipped('Needs the cURL extension to name the option.');
        }

        $client = $this->client($this->sut(), [new Response(200, [], 'ok')]);

        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('CURLOPT_URL');

        $client->request('GET', 'https://first.example.org/start', [
            RequestOptions::CURL => [CURLOPT_URL => 'https://inward.example.org/'],
        ]);
    }


    /**
     * An option explicitly turned off is not an override, and neither is an unrelated one.
     */
    public function testAllowsCurlOptionsThatDoNotOverrideTheDestination(): void
    {
        if (!defined('CURLOPT_FOLLOWLOCATION')) {
            $this->markTestSkipped('Needs the cURL extension to name the option.');
        }

        $client = $this->client($this->sut(), [new Response(200, [], 'ok')]);

        $response = $client->request('GET', 'https://first.example.org/start', [
            RequestOptions::CURL => [CURLOPT_FOLLOWLOCATION => false, CURLOPT_TCP_NODELAY => true],
        ]);

        $this->assertSame(200, $response->getStatusCode());
    }


    /**
     * A destination whose port cannot be worked out is a pin that could not be made, not one that was not
     * needed: the host is still a name that the connection would resolve for itself.
     */
    public function testRefusesWhenADestinationCannotBePinnedAtAll(): void
    {
        $destinationPolicy = new DestinationPolicy(
            allowedSchemes: ['x-openid-test'],
            addressPinningMode: AddressPinningModeEnum::Required,
            logger: $this->loggerMock,
            addressResolver: $this->addressResolverMock,
        );

        $client = $this->client($this->sut($destinationPolicy), [new Response(200, [], 'ok')]);

        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('can not be pinned');

        $client->request('GET', 'x-openid-test://first.example.org/start');
    }


    /**
     * Required pinning still has nothing to insist on where there is no second resolution to close.
     */
    public function testRequiredPinningAllowsADestinationWithNothingToPin(): void
    {
        $client = $this->client(
            $this->sut(
                $this->policy(AddressPinningModeEnum::Required, ['rp.internal.example']),
                $this->unsupportedPinner(),
            ),
            [new Response(200, [], 'ok')],
        );

        $this->assertSame(200, $client->request('GET', 'https://rp.internal.example/x')->getStatusCode());
    }


    /**
     * @return \PHPUnit\Framework\MockObject\MockObject&\SimpleSAML\OpenID\Network\AddressPinner
     */
    protected function unsupportedPinner(): MockObject
    {
        $addressPinnerMock = $this->createMock(AddressPinner::class);
        $addressPinnerMock->method('isSupported')->willReturn(false);
        $addressPinnerMock->expects($this->never())->method('pin');

        return $addressPinnerMock;
    }
}
