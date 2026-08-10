<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Network;

use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\AddressPinningModeEnum;
use SimpleSAML\OpenID\Exceptions\DestinationPolicyException;
use SimpleSAML\OpenID\Network\AddressPinner;
use SimpleSAML\OpenID\Network\AddressResolver;
use SimpleSAML\OpenID\Network\AddressValidator;
use SimpleSAML\OpenID\Network\DestinationGuardMiddleware;
use SimpleSAML\OpenID\Network\DestinationPolicy;
use SimpleSAML\OpenID\Network\ValidatedDestination;

#[CoversClass(DestinationPolicy::class)]
#[UsesClass(AddressValidator::class)]
#[UsesClass(AddressResolver::class)]
#[UsesClass(AddressPinner::class)]
#[UsesClass(DestinationGuardMiddleware::class)]
#[UsesClass(ValidatedDestination::class)]
final class DestinationPolicyTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\SimpleSAML\OpenID\Network\AddressResolver
     */
    protected MockObject $addressResolverMock;


    protected function setUp(): void
    {
        $this->addressResolverMock = $this->createMock(AddressResolver::class);
    }


    /**
     * @param list<string> $allowedSchemes
     * @param list<string> $allowedHosts
     * @param list<string> $allowedCidrs
     */
    protected function sut(
        array $allowedSchemes = DestinationPolicy::DEFAULT_ALLOWED_SCHEMES,
        array $allowedHosts = [],
        array $allowedCidrs = [],
        AddressPinningModeEnum $addressPinningMode = AddressPinningModeEnum::Preferred,
    ): DestinationPolicy {
        return new DestinationPolicy(
            $allowedSchemes,
            $allowedHosts,
            $allowedCidrs,
            $addressPinningMode,
            $this->createStub(LoggerInterface::class),
            new AddressValidator(),
            $this->addressResolverMock,
        );
    }


    /**
     * @param list<string> $addresses
     */
    protected function stubResolution(array $addresses): void
    {
        $this->addressResolverMock->method('resolve')->willReturn($addresses);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(DestinationPolicy::class, $this->sut());
        $this->assertInstanceOf(DestinationPolicy::class, new DestinationPolicy());
    }


    public function testAllowsAPublicDestination(): void
    {
        $this->stubResolution(['93.184.216.34', '2001:4860:4860::8888']);

        $destination = $this->sut()->validateUri('https://example.org/jwks');

        $this->assertSame('example.org', $destination->host);
        $this->assertSame(443, $destination->port);
        $this->assertSame(['93.184.216.34', '2001:4860:4860::8888'], $destination->addresses);
        $this->assertFalse($destination->isHostAllowListed);
        $this->assertFalse($destination->isHostLiteralAddress);
    }


    public function testKeepsAnExplicitPort(): void
    {
        $this->stubResolution(['93.184.216.34']);

        $this->assertSame(8443, $this->sut()->validateUri('https://example.org:8443/jwks')->port);
    }


    /**
     * The compared form drops a trailing root label, but the request still writes it, and a client keys its
     * resolver cache on what the request wrote, so both spellings have to be carried.
     */
    public function testCarriesTheHostAsTheRequestWritesIt(): void
    {
        $this->stubResolution(['93.184.216.34']);

        $destination = $this->sut()->validateUri('https://example.org./jwks');

        $this->assertSame('example.org', $destination->host);
        $this->assertSame('example.org.', $destination->requestHost);
        $this->assertSame(['example.org.', 'example.org'], $destination->hostSpellings());
    }


    public function testRefusesADestinationResolvingInward(): void
    {
        $this->stubResolution(['127.0.0.1']);

        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('resolves to 127.0.0.1, which is not a public address');

        $this->sut()->validateUri('https://example.org/jwks');
    }


    /**
     * Which address a connection picks out of several is not something to leave to chance, so one that is not
     * allowed refuses the destination whatever else it resolved to.
     */
    public function testRefusesWhenAnyResolvedAddressIsNotAllowed(): void
    {
        $this->stubResolution(['93.184.216.34', '10.0.0.5']);

        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('resolves to 10.0.0.5');

        $this->sut()->validateUri('https://example.org/jwks');
    }


    public function testRefusesAnUnresolvableHost(): void
    {
        $this->stubResolution([]);

        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('could not be resolved to any address');

        $this->sut()->validateUri('https://example.invalid/jwks');
    }


    public function testRefusesALiteralNonPublicHostWithoutResolving(): void
    {
        $this->addressResolverMock->expects($this->never())->method('resolve');

        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('host 127.0.0.1 is not a public address');

        $this->sut()->validateUri('https://127.0.0.1/jwks');
    }


    public function testRefusesALiteralIpv6LoopbackHost(): void
    {
        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('host ::1 is not a public address');

        $this->sut()->validateUri('https://[::1]/jwks');
    }


    public function testRefusesAV4MappedLoopbackHost(): void
    {
        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('is not a public address');

        $this->sut()->validateUri('https://[::ffff:127.0.0.1]/jwks');
    }


    public function testAllowsALiteralPublicHost(): void
    {
        $destination = $this->sut()->validateUri('https://93.184.216.34/jwks');

        $this->assertTrue($destination->isHostLiteralAddress);
        $this->assertSame(['93.184.216.34'], $destination->addresses);
        $this->assertFalse($destination->isPinnable());
    }


    public function testRefusesASchemeThatIsNotAllowed(): void
    {
        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('scheme "http" is not among the allowed schemes (https)');

        $this->sut()->validateUri('http://example.org/jwks');
    }


    public function testAllowsAConfiguredScheme(): void
    {
        $this->stubResolution(['93.184.216.34']);

        $destination = $this->sut(allowedSchemes: ['https', 'HTTP'])->validateUri('http://example.org/jwks');

        $this->assertSame(80, $destination->port);
    }


    public function testRefusesCredentialsInTheUri(): void
    {
        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('carries credentials');

        $this->sut()->validateUri('https://user:secret@example.org/jwks');
    }


    public function testRefusesAMalformedUri(): void
    {
        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('not a usable URI');

        $this->sut()->validateUri('https://[not-an-address]/jwks');
    }


    public function testRefusesAnEmptyHost(): void
    {
        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('has no host');

        $this->sut()->validateUri($this->uriStub(''));
    }


    /**
     * Brackets mean an address literal, so anything else inside them is refused rather than handed to DNS.
     */
    public function testRefusesABracketedHostThatIsNotAnAddress(): void
    {
        $this->addressResolverMock->expects($this->never())->method('resolve');

        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('is not a valid address literal');

        $this->sut()->validateUri($this->uriStub('[example.org]'));
    }


    public function testAllowsAnExplicitlyAllowedHostWithoutResolvingIt(): void
    {
        $this->addressResolverMock->expects($this->never())->method('resolve');

        $destination = $this->sut(allowedHosts: ['rp.internal.example'])
            ->validateUri('https://rp.internal.example/jwks');

        $this->assertTrue($destination->isHostAllowListed);
        $this->assertSame([], $destination->addresses);
        $this->assertFalse($destination->isPinnable());
    }


    public function testComparesAllowedHostsWithoutCaseBracketsOrTrailingDot(): void
    {
        $sut = $this->sut(allowedHosts: ['RP.Internal.Example.', '[::1]']);

        $this->assertTrue($sut->isUriAllowed('https://rp.internal.example/jwks'));
        $this->assertTrue($sut->isUriAllowed('https://rp.internal.example./jwks'));
        $this->assertTrue($sut->isUriAllowed('https://[::1]/jwks'));
    }


    public function testAllowsAnExplicitlyAllowedRange(): void
    {
        $this->stubResolution(['10.0.0.5']);

        $destination = $this->sut(allowedCidrs: ['10.0.0.0/24'])->validateUri('https://rp.internal.example/x');

        $this->assertSame(['10.0.0.5'], $destination->addresses);
        $this->assertTrue($destination->isPinnable());
    }


    public function testAnAllowedRangeDoesNotWidenToTheWholePrivateSpace(): void
    {
        $sut = $this->sut(allowedCidrs: ['10.0.0.5/32']);

        $this->assertTrue($sut->isAddressAllowed('10.0.0.5'));
        $this->assertFalse($sut->isAddressAllowed('10.0.0.6'));
        $this->assertFalse($sut->isAddressAllowed('192.168.0.5'));
    }


    public function testIsAddressAllowed(): void
    {
        $sut = $this->sut();

        $this->assertTrue($sut->isAddressAllowed('93.184.216.34'));
        $this->assertFalse($sut->isAddressAllowed('127.0.0.1'));
        $this->assertFalse($sut->isAddressAllowed('nonsense'));
    }


    public function testIsUriAllowedDoesNotRaise(): void
    {
        $this->stubResolution(['93.184.216.34']);
        $sut = $this->sut();

        $this->assertTrue($sut->isUriAllowed('https://example.org/jwks'));
        $this->assertFalse($sut->isUriAllowed('http://example.org/jwks'));
    }


    public function testAssertUriIsAllowedPassesQuietly(): void
    {
        $this->stubResolution(['93.184.216.34']);

        $this->expectNotToPerformAssertions();

        $this->sut()->assertUriIsAllowed(new Uri('https://example.org/jwks'));
    }


    public function testRefusesConfigurationWithoutAnyScheme(): void
    {
        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('at least one allowed URI scheme');

        $this->sut(allowedSchemes: []);
    }


    public function testRefusesAnEmptyScheme(): void
    {
        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('at least one allowed URI scheme');

        $this->sut(allowedSchemes: ['https', ' ']);
    }


    public function testRefusesAnEmptyAllowedHost(): void
    {
        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('empty allowed host');

        $this->sut(allowedHosts: ['']);
    }


    /**
     * A range that can never match would read as a working exemption while quietly allowing nothing.
     */
    public function testRefusesAnUnusableAllowedRange(): void
    {
        $this->expectException(DestinationPolicyException::class);
        $this->expectExceptionMessage('which is not a valid CIDR');

        $this->sut(allowedCidrs: ['10.0.0.0']);
    }


    public function testExposesItsConfiguration(): void
    {
        $sut = $this->sut(
            allowedSchemes: ['HTTPS', 'https'],
            allowedHosts: ['RP.Internal.Example'],
            allowedCidrs: [' 10.0.0.0/8 '],
            addressPinningMode: AddressPinningModeEnum::Required,
        );

        $this->assertSame(['https'], $sut->getAllowedSchemes());
        $this->assertSame(['rp.internal.example'], $sut->getAllowedHosts());
        $this->assertSame(['10.0.0.0/8'], $sut->getAllowedCidrs());
        $this->assertSame(AddressPinningModeEnum::Required, $sut->getAddressPinningMode());
    }


    public function testMiddlewareIsBuiltOnce(): void
    {
        $sut = $this->sut();

        $this->assertInstanceOf(DestinationGuardMiddleware::class, $sut->middleware());
        $this->assertSame($sut->middleware(), $sut->middleware());
    }


    /**
     * Pinning belongs to the transport rather than to the policy, so a caller guarding a particular client
     * gets a middleware of its own instead of changing what every other client is told.
     */
    public function testMiddlewareForAKnownTransportIsSeparate(): void
    {
        $sut = $this->sut();
        $middleware = $sut->middleware(new AddressPinner(handlerIsCurl: true));

        $this->assertInstanceOf(DestinationGuardMiddleware::class, $middleware);
        $this->assertNotSame($sut->middleware(), $middleware);
    }


    /**
     * A URI whose host can not be produced by the Guzzle implementation, for the checks that only a foreign
     * implementation can reach.
     */
    protected function uriStub(string $host): UriInterface
    {
        $uriMock = $this->createMock(UriInterface::class);
        $uriMock->method('getScheme')->willReturn('https');
        $uriMock->method('getUserInfo')->willReturn('');
        $uriMock->method('getHost')->willReturn($host);
        $uriMock->method('getPort')->willReturn(null);

        return $uriMock;
    }
}
