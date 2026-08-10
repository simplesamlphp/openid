<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Network;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Network\AddressPinner;
use SimpleSAML\OpenID\Network\ValidatedDestination;

#[CoversClass(AddressPinner::class)]
#[UsesClass(ValidatedDestination::class)]
final class AddressPinnerTest extends TestCase
{
    /**
     * A name reserved for never resolving, so that a lookup for it fails wherever the tests run.
     */
    protected const UNRESOLVABLE_HOST = 'openid-library-test.invalid';

    /**
     * A port on the loopback interface that refuses connections immediately, so the pinned attempt fails at
     * the connection rather than waiting out a timeout.
     */
    protected const CLOSED_PORT = 1;


    /**
     * Proxy variables the environment may already carry, and their values, so that they can be put back.
     *
     * @var array<string, string|false>
     */
    protected array $inheritedProxyEnvironment = [];


    protected function setUp(): void
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('Address pinning needs the cURL extension.');
        }

        // A proxy in the environment makes pinning unsupported on purpose, so a developer shell or a CI
        // runner that sets one would otherwise decide the outcome of these assertions.
        foreach (['http_proxy', 'https_proxy', 'HTTPS_PROXY', 'all_proxy', 'ALL_PROXY'] as $variable) {
            $this->inheritedProxyEnvironment[$variable] = getenv($variable);
            putenv($variable);
        }
    }


    protected function tearDown(): void
    {
        foreach ($this->inheritedProxyEnvironment as $variable => $value) {
            is_string($value) ? putenv($variable . '=' . $value) : putenv($variable);
        }

        $this->inheritedProxyEnvironment = [];
    }


    /**
     * A pinner told that it is running on a cURL handler, which is what the library establishes for a client
     * it built itself.
     */
    protected function sut(): AddressPinner
    {
        return new AddressPinner(handlerIsCurl: true);
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(AddressPinner::class, $this->sut());
    }


    public function testIsSupportedWithCurl(): void
    {
        $this->assertTrue($this->sut()->isSupported());
    }


    /**
     * Guzzle hands a streaming request to the stream handler where it can, and cURL options mean nothing
     * there, so such a request is reported as not pinnable even though cURL is present.
     */
    public function testIsNotSupportedForAStreamingRequest(): void
    {
        $this->assertFalse($this->sut()->isSupported([RequestOptions::STREAM => true]));
        // Guzzle reads the option as "not empty", so a truthy value that is not literally true still streams.
        $this->assertFalse($this->sut()->isSupported([RequestOptions::STREAM => 1]));
        $this->assertTrue($this->sut()->isSupported([RequestOptions::STREAM => false]));
        $this->assertTrue($this->sut()->isSupported([RequestOptions::STREAM => null]));
    }


    /**
     * Nothing can be established about a transport that came from outside the library, and a pin claimed for
     * a handler that ignores cURL options would be a guarantee that was never made, so assuming nothing is
     * the default.
     */
    public function testIsNotSupportedForATransportNothingIsKnownAbout(): void
    {
        $this->assertFalse((new AddressPinner())->isSupported());
    }


    /**
     * cURL applies these to the network destination itself, over the resolver cache a pin seeds, so the pin
     * would no longer be what the connection follows.
     */
    public function testIsNotSupportedWhenACurlOptionOverridesTheRouting(): void
    {
        $this->assertFalse(
            $this->sut()->isSupported([RequestOptions::CURL => [CURLOPT_CONNECT_TO => ['example.org::10.0.0.5:']]]),
        );
        $this->assertFalse(
            $this->sut()->isSupported([RequestOptions::CURL => [CURLOPT_PROXY => 'http://proxy.example:3128']]),
        );
        // A pin names a host and a port, so a port set underneath the URI leaves it matching nothing.
        $this->assertFalse($this->sut()->isSupported([RequestOptions::CURL => [CURLOPT_PORT => 8443]]));
        $this->assertTrue($this->sut()->isSupported([RequestOptions::CURL => [CURLOPT_TCP_NODELAY => true]]));
    }


    /**
     * A proxy configuration holding nothing but exclusions selects no proxy, and reading it as one would
     * cost a pin that could perfectly well have been made.
     */
    public function testProxyExclusionsAloneAreNotAProxy(): void
    {
        $this->assertTrue($this->sut()->isSupported([RequestOptions::PROXY => ['no' => ['example.org']]]));
        $this->assertTrue($this->sut()->isSupported([RequestOptions::PROXY => []]));
        $this->assertFalse(
            $this->sut()->isSupported(
                [RequestOptions::PROXY => ['https' => 'http://proxy.example:3128', 'no' => ['other.example']]],
            ),
        );
    }


    /**
     * A proxy resolves the destination itself, where a cURL option does not reach.
     */
    public function testIsNotSupportedForAProxiedRequest(): void
    {
        $this->assertFalse($this->sut()->isSupported([RequestOptions::PROXY => 'http://proxy.example:3128']));
        $this->assertFalse(
            $this->sut()->isSupported([RequestOptions::PROXY => ['https' => 'http://proxy.example:3128']]),
        );
        $this->assertTrue($this->sut()->isSupported([RequestOptions::PROXY => '']));
    }


    public function testIsNotSupportedWhenTheEnvironmentSetsAProxy(): void
    {
        // setUp() cleared whatever the environment carried, and tearDown() puts it back.
        putenv('HTTPS_PROXY=http://proxy.example:3128');

        $this->assertFalse($this->sut()->isSupported());

        putenv('HTTPS_PROXY');

        $this->assertTrue($this->sut()->isSupported());
    }


    public function testPinsResolvedAddresses(): void
    {
        $options = $this->sut()->pin(
            [],
            new ValidatedDestination('example.org', 443, ['93.184.216.34', '93.184.216.35']),
        );

        $this->assertSame(
            ['example.org:443:93.184.216.34,93.184.216.35'],
            $options[RequestOptions::CURL][CURLOPT_RESOLVE],
        );
    }


    /**
     * The entry is colon separated, so an IPv6 address in it has to be bracketed to be readable at all.
     */
    public function testBracketsIpv6Addresses(): void
    {
        $options = $this->sut()->pin(
            [],
            new ValidatedDestination('example.org', 443, ['2001:db8::1', '93.184.216.34']),
        );

        $this->assertSame(
            ['example.org:443:[2001:db8::1],93.184.216.34'],
            $options[RequestOptions::CURL][CURLOPT_RESOLVE],
        );
    }


    /**
     * cURL looks its resolver cache up under the host exactly as the request writes it, so a destination
     * written with a trailing root label needs an entry under that spelling too - see
     * testPinCoversAHostWrittenWithATrailingDot() for the behaviour this guards against.
     */
    public function testPinsEverySpellingOfTheHost(): void
    {
        $options = $this->sut()->pin(
            [],
            new ValidatedDestination('example.org', 443, ['93.184.216.34'], requestHost: 'example.org.'),
        );

        $this->assertSame(
            ['example.org.:443:93.184.216.34', 'example.org:443:93.184.216.34'],
            $options[RequestOptions::CURL][CURLOPT_RESOLVE],
        );
    }


    /**
     * A pin the caller set is kept but superseded, since cURL takes the last entry for a host and port and an
     * unvalidated pin must not be the one that decides where the request goes.
     */
    public function testAddsAfterEntriesTheCallerSet(): void
    {
        $options = $this->sut()->pin(
            [
                RequestOptions::CURL => [
                    CURLOPT_RESOLVE => ['example.org:443:127.0.0.1'],
                    CURLOPT_TCP_NODELAY => true,
                ],
            ],
            new ValidatedDestination('example.org', 443, ['93.184.216.34']),
        );

        $this->assertSame(
            ['example.org:443:127.0.0.1', 'example.org:443:93.184.216.34'],
            $options[RequestOptions::CURL][CURLOPT_RESOLVE],
        );
        $this->assertTrue($options[RequestOptions::CURL][CURLOPT_TCP_NODELAY]);
    }


    public function testLeavesOptionsAloneWhenThereIsNothingToPin(): void
    {
        $sut = $this->sut();

        $this->assertSame([], $sut->pin([], new ValidatedDestination('example.org', 443, [])));
        $this->assertSame(
            [],
            $sut->pin([], new ValidatedDestination('example.org', 443, [], isHostAllowListed: true)),
        );
        $this->assertSame(
            [],
            $sut->pin(
                [],
                new ValidatedDestination('93.184.216.34', 443, ['93.184.216.34'], isHostLiteralAddress: true),
            ),
        );
    }


    /**
     * That the pin is what the connection uses, rather than only that it was written into the options.
     *
     * The host is one that can not be resolved, so a request that reaches the connection stage at all can
     * only have got its address from the pin. Nothing is connected to: the pinned address is a loopback port
     * with nothing behind it, so the attempt is refused at once.
     */
    public function testPinDecidesWhereTheConnectionGoes(): void
    {
        $unpinnedErrorNumber = $this->attemptRequestAndGetCurlErrorNumber([]);

        if ($unpinnedErrorNumber !== CURLE_COULDNT_RESOLVE_HOST) {
            $this->markTestSkipped(
                'This environment resolves ' . self::UNRESOLVABLE_HOST . ', so the pin can not be told apart ' .
                'from an ordinary lookup.',
            );
        }

        $pinnedOptions = $this->sut()->pin(
            [],
            new ValidatedDestination(self::UNRESOLVABLE_HOST, self::CLOSED_PORT, ['127.0.0.1']),
        );

        $this->assertSame(
            CURLE_COULDNT_CONNECT,
            $this->attemptRequestAndGetCurlErrorNumber($pinnedOptions),
            'The request should have reached the connection stage, which needs an address the pin supplied.',
        );
    }


    /**
     * The reason every spelling of the host is pinned: cURL finds its cache entry under the host as the URI
     * writes it, so a destination ending in the root label would otherwise go out unpinned while the policy
     * believed it had been pinned.
     */
    public function testPinCoversAHostWrittenWithATrailingDot(): void
    {
        $dottedHost = self::UNRESOLVABLE_HOST . '.';
        $unpinnedErrorNumber = $this->attemptRequestAndGetCurlErrorNumber([], $dottedHost);

        if ($unpinnedErrorNumber !== CURLE_COULDNT_RESOLVE_HOST) {
            $this->markTestSkipped('This environment resolves ' . $dottedHost . '.');
        }

        $pinnedOptions = $this->sut()->pin(
            [],
            new ValidatedDestination(
                self::UNRESOLVABLE_HOST,
                self::CLOSED_PORT,
                ['127.0.0.1'],
                requestHost: $dottedHost,
            ),
        );

        $this->assertSame(
            CURLE_COULDNT_CONNECT,
            $this->attemptRequestAndGetCurlErrorNumber($pinnedOptions, $dottedHost),
            'The request should have reached the connection stage, which needs an address the pin supplied.',
        );
    }


    /**
     * @param array<string,mixed> $options
     * @return ?int The cURL error number the attempt failed with.
     */
    protected function attemptRequestAndGetCurlErrorNumber(array $options, ?string $host = null): ?int
    {
        // A client of its own for each attempt, so that a pin can not carry over through cURL's own cache.
        $client = new Client([
            'handler' => HandlerStack::create(new CurlHandler()),
            RequestOptions::CONNECT_TIMEOUT => 2,
            RequestOptions::TIMEOUT => 3,
        ]);

        try {
            $client->request(
                'GET',
                sprintf('http://%s:%d/', $host ?? self::UNRESOLVABLE_HOST, self::CLOSED_PORT),
                $options,
            );
        } catch (ConnectException $connectException) {
            // Read off the message rather than out of the handler context, which Guzzle 8 no longer carries.
            return preg_match('/cURL error (\d+)/', $connectException->getMessage(), $matches) === 1 ?
            (int)$matches[1] :
            null;
        }

        $this->fail('The request was expected to fail.');
    }
}
