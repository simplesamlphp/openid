<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Did;

use DateInterval;
use Iterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SimpleSAML\OpenID\Codebooks\AddressPinningModeEnum;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Decorators\CacheDecorator;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Did\DidDocument;
use SimpleSAML\OpenID\Did\DidUrl;
use SimpleSAML\OpenID\Did\DidWebResolver;
use SimpleSAML\OpenID\Did\Factories\DidDocumentFactory;
use SimpleSAML\OpenID\Exceptions\DestinationPolicyException;
use SimpleSAML\OpenID\Exceptions\DidException;
use SimpleSAML\OpenID\Exceptions\HttpException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Network\AddressResolver;
use SimpleSAML\OpenID\Network\AddressValidator;
use SimpleSAML\OpenID\Network\DestinationPolicy;

#[CoversClass(DidWebResolver::class)]
#[UsesClass(DidUrl::class)]
#[UsesClass(DestinationPolicy::class)]
#[UsesClass(AddressResolver::class)]
#[UsesClass(AddressValidator::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Json::class)]
final class DidWebResolverTest extends TestCase
{
    private const DID = 'did:web:example.org';

    private const DOCUMENT_URL = 'https://example.org/.well-known/did.json';

    private const DOCUMENT_JSON = '{"id":"did:web:example.org"}';

    private const DOCUMENT_DATA = ['id' => 'did:web:example.org'];


    private MockObject $httpClientDecoratorMock;

    private MockObject $didDocumentFactoryMock;

    private MockObject $maxCacheDurationDecoratorMock;

    private MockObject $cacheDecoratorMock;

    private MockObject $loggerMock;

    private MockObject $responseMock;

    private MockObject $didDocumentMock;

    private Helpers $helpers;

    /**
     * Stands in for the cache the decorator mock is backed by, so that a test can watch what a resolution
     * left behind rather than only that it asked.
     *
     * @var array<string, mixed>
     */
    private array $cacheStore = [];


    protected function setUp(): void
    {
        $this->httpClientDecoratorMock = $this->createMock(HttpClientDecorator::class);
        $this->didDocumentFactoryMock = $this->createMock(DidDocumentFactory::class);
        $this->maxCacheDurationDecoratorMock = $this->createMock(DateIntervalDecorator::class);
        $this->maxCacheDurationDecoratorMock->method('getInSeconds')->willReturn(3600);
        $this->cacheDecoratorMock = $this->createMock(CacheDecorator::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->responseMock = $this->createMock(ResponseInterface::class);
        $this->didDocumentMock = $this->createMock(DidDocument::class);
        $this->helpers = new Helpers();

        $this->cacheStore = [];

        $this->cacheDecoratorMock->method('get')->willReturnCallback(
            fn(mixed $default, string $keyElement, string ...$keyElements): mixed =>
                $this->cacheStore[$this->cacheKey($keyElement, ...$keyElements)] ?? $default,
        );

        $this->cacheDecoratorMock->method('set')->willReturnCallback(
            function (mixed $value, int|DateInterval $ttl, string $keyElement, string ...$keyElements): void {
                $this->cacheStore[$this->cacheKey($keyElement, ...$keyElements)] = $value;
            },
        );
    }


    private function cacheKey(string $keyElement, string ...$keyElements): string
    {
        return implode('|', [$keyElement, ...$keyElements]);
    }


    private function sut(
        ?HttpClientDecorator $httpClientDecorator = null,
        ?DidDocumentFactory $didDocumentFactory = null,
        ?Helpers $helpers = null,
        ?DateIntervalDecorator $maxCacheDurationDecorator = null,
        ?CacheDecorator $cacheDecorator = null,
        ?LoggerInterface $logger = null,
        ?int $maxDocumentSizeBytes = null,
        int $maxJsonDepth = DidWebResolver::DEFAULT_MAX_JSON_DEPTH,
        int $negativeCacheDurationSeconds = DidWebResolver::DEFAULT_NEGATIVE_CACHE_DURATION_SECONDS,
        bool $requireControllerToMatchSubject = true,
    ): DidWebResolver {
        return new DidWebResolver(
            $httpClientDecorator ?? $this->httpClientDecoratorMock,
            $didDocumentFactory ?? $this->didDocumentFactoryMock,
            $helpers ?? $this->helpers,
            $maxCacheDurationDecorator ?? $this->maxCacheDurationDecoratorMock,
            $cacheDecorator,
            $logger,
            $maxDocumentSizeBytes,
            $maxJsonDepth,
            $negativeCacheDurationSeconds,
            $requireControllerToMatchSubject,
        );
    }


    /**
     * A resolver whose cache is the array this test can inspect.
     */
    private function sutWithCache(
        int $negativeCacheDurationSeconds = DidWebResolver::DEFAULT_NEGATIVE_CACHE_DURATION_SECONDS,
    ): DidWebResolver {
        return $this->sut(
            cacheDecorator: $this->cacheDecoratorMock,
            negativeCacheDurationSeconds: $negativeCacheDurationSeconds,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(DidWebResolver::class, $this->sut());
    }


    #[DataProvider('documentUrlDataProvider')]
    public function testBuildsDocumentUrl(string $did, string $expectedUrl): void
    {
        $this->assertSame($expectedUrl, $this->sut()->buildDocumentUrl($did));
    }


    public static function documentUrlDataProvider(): Iterator
    {
        yield 'root' => ['did:web:example.org', 'https://example.org/.well-known/did.json'];
        yield 'path' => ['did:web:example.org:oidc', 'https://example.org/oidc/did.json'];
        yield 'nested path' => ['did:web:example.org:oidc:issuer', 'https://example.org/oidc/issuer/did.json'];
        yield 'encoded port' => ['did:web:example.org%3A8443', 'https://example.org:8443/.well-known/did.json'];
        yield 'lowercase encoded port' => [
            'did:web:example.org%3a8443',
            'https://example.org:8443/.well-known/did.json',
        ];
        yield 'encoded port and path' => ['did:web:example.org%3A8443:oidc', 'https://example.org:8443/oidc/did.json'];
        yield 'subdomain' => ['did:web:sub.example.co.uk', 'https://sub.example.co.uk/.well-known/did.json'];
        // An internationalized top level domain in A-label form carries hyphens and digits, and is an
        // ordinary DNS host: xn--p1ai is .рф.
        yield 'A-label top level domain' => ['did:web:issuer.xn--p1ai', 'https://issuer.xn--p1ai/.well-known/did.json'];
        yield 'A-label second level domain' => [
            'did:web:xn--80ak6aa92e.com',
            'https://xn--80ak6aa92e.com/.well-known/did.json',
        ];
        yield 'punctuation in a segment' => ['did:web:example.org:a_b-c.d', 'https://example.org/a_b-c.d/did.json'];
        yield 'hyphenated host' => [
            'did:web:my-issuer.example.org',
            'https://my-issuer.example.org/.well-known/did.json',
        ];
    }


    #[DataProvider('invalidDidDataProvider')]
    public function testRejectsInvalidDid(string $did, string $expectedExceptionMessage): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->sut()->buildDocumentUrl($did);
    }


    public static function invalidDidDataProvider(): Iterator
    {
        $percentEncodedHost = 'did:web host must not be percent encoded, since the port separator is the only ' .
        'triplet the method decodes.';
        $percentEncodedSegment = 'did:web path segment must not be percent encoded, since the port separator is ' .
        'the only triplet the method decodes.';
        $invalidHost = 'did:web host is not a syntactically valid domain name.';
        $invalidPort = 'did:web port is not a valid port number.';

        yield 'empty' => ['', 'DID URL must not be empty.'];
        yield 'not a DID' => ['https://example.org', 'DID URL does not contain a syntactically valid DID.'];
        yield 'another method' => [
            'did:key:z6MkhaXgBZDvotDkL5257faiztiGiC2QtKLGpbnnEGta2doK',
            'DID method "key" is not one this resolver handles.',
        ];
        yield 'with a fragment' => [
            'did:web:example.org#0',
            'A DID document can only be resolved for a bare DID.',
        ];
        yield 'with a query' => [
            'did:web:example.org?service=files',
            'A DID document can only be resolved for a bare DID.',
        ];
        yield 'with a path' => [
            'did:web:example.org/did.json',
            'A DID document can only be resolved for a bare DID.',
        ];
        // The one triplet the method decodes is the port separator, so every other one is refused rather than
        // being allowed to move the document somewhere else on the host.
        yield 'encoded slash in the host' => ['did:web:example.org%2Fevil.test', $percentEncodedHost];
        yield 'encoded at sign in the host' => ['did:web:user%40example.org', $percentEncodedHost];
        yield 'double encoded port separator' => ['did:web:example.org%253A8443', $percentEncodedHost];
        yield 'malformed triplet in the host' => ['did:web:example.org%3', 'DID URL does not contain a ' .
            'syntactically valid DID.', ];
        yield 'encoded slash in a path segment' => ['did:web:example.org:a%2Fb', $percentEncodedSegment];
        yield 'encoded question mark in a path segment' => ['did:web:example.org:a%3Fq', $percentEncodedSegment];
        yield 'encoded hash in a path segment' => ['did:web:example.org:a%23f', $percentEncodedSegment];
        yield 'misplaced port separator' => ['did:web:example.org:a%3Ab', $percentEncodedSegment];
        yield 'repeated port separator' => [
            'did:web:example.org%3A8443%3A9',
            'did:web host and port must be separated by a single encoded colon.',
        ];
        yield 'IPv4 literal host' => ['did:web:192.168.1.1', 'did:web does not permit an IP address as the host.'];
        yield 'IPv4 literal host with a port' => [
            'did:web:127.0.0.1%3A8080',
            'did:web does not permit an IP address as the host.',
        ];
        yield 'single label host' => ['did:web:localhost', $invalidHost];
        yield 'trailing dot host' => ['did:web:example.org.', $invalidHost];
        // A top level label of digits is how an IP address in an unusual base ends, never a domain name.
        yield 'numeric top level label' => ['did:web:example.123', 'did:web does not permit an IP address as ' .
            'the host.', ];
        yield 'hex spelled IPv4' => ['did:web:0x7f.0x0.0x0.0x1', 'did:web does not permit an IP address as ' .
            'the host.', ];
        yield 'out of range dotted quad' => ['did:web:999.999.999.999', 'did:web does not permit an IP address ' .
            'as the host.', ];
        yield 'missing host' => ['did:web:%3A8443', $invalidHost];
        yield 'overlong host' => ['did:web:' . str_repeat('aa.', 85) . 'com', 'did:web host is longer than a ' .
            'domain name may be.', ];
        yield 'zero port' => ['did:web:example.org%3A0', $invalidPort];
        yield 'out of range port' => ['did:web:example.org%3A99999', $invalidPort];
        yield 'non numeric port' => ['did:web:example.org%3Aabc', $invalidPort];
        yield 'empty path segment' => [
            'did:web:example.org::oidc',
            'did:web identifier contains an empty path segment.',
        ];
        yield 'parent path segment' => [
            'did:web:example.org:..:etc',
            'did:web path segments must not be relative references.',
        ];
        yield 'current path segment' => [
            'did:web:example.org:.',
            'did:web path segments must not be relative references.',
        ];
        // PCRE lets $ match before a trailing newline unless the D modifier says otherwise, so without it
        // this parsed as the DID while the raw value kept the newline and carried it onward.
        yield 'trailing line feed' => [
            "did:web:example.org\n",
            'DID URL does not contain a syntactically valid DID.',
        ];
        yield 'trailing carriage return' => [
            "did:web:example.org\r",
            'DID URL does not contain a syntactically valid DID.',
        ];
    }


    #[DataProvider('supportsDataProvider')]
    public function testSupports(string $did, bool $expected): void
    {
        $this->assertSame($expected, $this->sut()->supports($did));
    }


    public static function supportsDataProvider(): Iterator
    {
        yield 'bare did:web' => ['did:web:example.org', true];
        yield 'did:web URL' => ['did:web:example.org:oidc#key-1', true];
        yield 'did:key' => ['did:key:z6MkhaXgBZDvotDkL5257faiztiGiC2QtKLGpbnnEGta2doK', false];
        yield 'did:jwk' => ['did:jwk:eyJrdHkiOiJFQyJ9', false];
        yield 'not a DID' => ['https://example.org', false];
        yield 'empty' => ['', false];
    }


    public function testBuildsDestinationPolicy(): void
    {
        $destinationPolicy = DidWebResolver::buildDestinationPolicy();

        $this->assertSame(['https'], $destinationPolicy->getAllowedSchemes());
        $this->assertSame([], $destinationPolicy->getAllowedHosts());
        $this->assertSame([], $destinationPolicy->getAllowedCidrs());
        $this->assertSame(AddressPinningModeEnum::Required, $destinationPolicy->getAddressPinningMode());
    }


    public function testDestinationPolicyRefusesPreferredPinning(): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('can not run under preferred address pinning');

        DidWebResolver::buildDestinationPolicy(addressPinningMode: AddressPinningModeEnum::Preferred);
    }


    public function testDestinationPolicyAllowsDisabledPinning(): void
    {
        $destinationPolicy = DidWebResolver::buildDestinationPolicy(
            addressPinningMode: AddressPinningModeEnum::Disabled,
        );

        $this->assertSame(AddressPinningModeEnum::Disabled, $destinationPolicy->getAddressPinningMode());
    }


    public function testDestinationPolicyTakesItsOwnExemptions(): void
    {
        // Reported, because an exemption here is a destination whoever supplies a DID may send us to.
        $this->loggerMock->expects($this->once())->method('notice');

        $destinationPolicy = DidWebResolver::buildDestinationPolicy(
            $this->loggerMock,
            allowedHosts: ['idp.example.test'],
            allowedCidrs: ['172.18.0.0/16'],
        );

        $this->assertSame(['idp.example.test'], $destinationPolicy->getAllowedHosts());
        $this->assertSame(['172.18.0.0/16'], $destinationPolicy->getAllowedCidrs());
        $this->assertSame(['https'], $destinationPolicy->getAllowedSchemes());
    }


    public function testDestinationPolicyReportsNothingWithoutExemptions(): void
    {
        $this->loggerMock->expects($this->never())->method('notice');

        DidWebResolver::buildDestinationPolicy($this->loggerMock);
    }


    public function testResolvesDocumentFromNetwork(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())
            ->method('request')
            ->with(HttpMethodsEnum::GET, self::DOCUMENT_URL, [], null)
            ->willReturn($this->responseMock);

        $this->httpClientDecoratorMock->expects($this->once())
            ->method('readResponseBodyAsString')
            ->with($this->responseMock, null)
            ->willReturn(self::DOCUMENT_JSON);

        $this->didDocumentFactoryMock->expects($this->once())
            ->method('fromData')
            ->with(self::DID, self::DOCUMENT_DATA, true)
            ->willReturn($this->didDocumentMock);

        $this->assertSame($this->didDocumentMock, $this->sut()->resolveDocument(self::DID));
    }


    public function testResolvesWithoutACache(): void
    {
        $this->httpClientDecoratorMock->method('request')->willReturn($this->responseMock);
        $this->httpClientDecoratorMock->method('readResponseBodyAsString')->willReturn(self::DOCUMENT_JSON);
        $this->didDocumentFactoryMock->method('fromData')->willReturn($this->didDocumentMock);

        // No cache decorator is handed in at all, so every resolution goes out to the network.
        $this->assertSame($this->didDocumentMock, $this->sut()->resolveDocument(self::DID));
    }


    public function testPassesTheControllerPolicyToTheFactory(): void
    {
        $this->httpClientDecoratorMock->method('request')->willReturn($this->responseMock);
        $this->httpClientDecoratorMock->method('readResponseBodyAsString')->willReturn(self::DOCUMENT_JSON);

        $this->didDocumentFactoryMock->expects($this->once())
            ->method('fromData')
            ->with(self::DID, self::DOCUMENT_DATA, false)
            ->willReturn($this->didDocumentMock);

        $this->sut(requireControllerToMatchSubject: false)->resolveDocument(self::DID);
    }


    public function testPassesTheConfiguredMaximumDocumentSize(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())
            ->method('request')
            ->with(HttpMethodsEnum::GET, self::DOCUMENT_URL, [], 4096)
            ->willReturn($this->responseMock);

        $this->httpClientDecoratorMock->expects($this->once())
            ->method('readResponseBodyAsString')
            ->with($this->responseMock, 4096)
            ->willReturn(self::DOCUMENT_JSON);

        $this->didDocumentFactoryMock->method('fromData')->willReturn($this->didDocumentMock);

        $this->sut(maxDocumentSizeBytes: 4096)->resolveDocument(self::DID);
    }


    public function testCachesAndServesTheResolvedDocument(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())
            ->method('request')
            ->willReturn($this->responseMock);

        $this->httpClientDecoratorMock->method('readResponseBodyAsString')->willReturn(self::DOCUMENT_JSON);

        // Twice: a cached document is checked again on the way out, rather than trusted because it was stored.
        $this->didDocumentFactoryMock->expects($this->exactly(2))
            ->method('fromData')
            ->willReturn($this->didDocumentMock);

        $sut = $this->sutWithCache();

        $this->assertSame($this->didDocumentMock, $sut->resolveDocument(self::DID));
        $this->assertSame($this->didDocumentMock, $sut->resolveDocument(self::DID));

        $this->assertSame(
            self::DOCUMENT_JSON,
            $this->cacheStore[$this->cacheKey('openid.did.web', 'document', self::DID)],
        );
    }


    public function testDiscardsACachedDocumentThatIsNoLongerUsable(): void
    {
        $this->cacheStore[$this->cacheKey('openid.did.web', 'document', self::DID)] = 'not json at all';

        $this->httpClientDecoratorMock->expects($this->once())
            ->method('request')
            ->willReturn($this->responseMock);

        $this->httpClientDecoratorMock->method('readResponseBodyAsString')->willReturn(self::DOCUMENT_JSON);
        $this->didDocumentFactoryMock->method('fromData')->willReturn($this->didDocumentMock);

        $this->assertSame($this->didDocumentMock, $this->sutWithCache()->resolveDocument(self::DID));
    }


    public function testRemembersAFailedResolution(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())
            ->method('request')
            ->willThrowException(new HttpException('Connection to 10.1.2.3 refused.'));

        $sut = $this->sutWithCache();

        $this->assertSame(
            'Could not retrieve the DID document.',
            $this->failedResolutionMessage($sut, self::DID),
        );

        $this->assertSame(
            'Could not retrieve the DID document.',
            $this->cacheStore[$this->cacheKey('openid.did.web', 'failure', self::DID)],
        );

        // The second attempt is answered from what was remembered, so it costs no further fetch.
        $this->expectException(DidException::class);
        $this->expectExceptionMessage('Could not retrieve the DID document.');

        $sut->resolveDocument(self::DID);
    }


    public function testDoesNotRememberFailuresWhenNegativeCachingIsDisabled(): void
    {
        $this->httpClientDecoratorMock->expects($this->exactly(2))
            ->method('request')
            ->willThrowException(new HttpException('Connection to 10.1.2.3 refused.'));

        $sut = $this->sutWithCache(negativeCacheDurationSeconds: 0);

        $this->failedResolutionMessage($sut, self::DID);
        $this->failedResolutionMessage($sut, self::DID);

        $this->assertSame([], $this->cacheStore);
    }


    public function testKeepsTheReasonForARetrievalFailureOutOfTheException(): void
    {
        $this->httpClientDecoratorMock->method('request')
            ->willThrowException(new DestinationPolicyException('Destination 10.1.2.3 is not permitted.'));

        // The address goes to the log, where the deployment can see it, and nowhere the caller can reach.
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('10.1.2.3'), $this->anything());

        $this->expectException(DidException::class);
        $this->expectExceptionMessage('Could not retrieve the DID document.');

        $this->sut(logger: $this->loggerMock)->resolveDocument(self::DID);
    }


    public function testRejectsADocumentThatIsNotJson(): void
    {
        $this->httpClientDecoratorMock->method('request')->willReturn($this->responseMock);
        $this->httpClientDecoratorMock->method('readResponseBodyAsString')->willReturn('<html>not json</html>');

        $this->expectException(DidException::class);
        $this->expectExceptionMessage('DID document is not valid JSON.');

        $this->sut()->resolveDocument(self::DID);
    }


    public function testRejectsADocumentNestedDeeperThanAllowed(): void
    {
        $this->httpClientDecoratorMock->method('request')->willReturn($this->responseMock);
        $this->httpClientDecoratorMock->method('readResponseBodyAsString')
            ->willReturn(str_repeat('[', 10) . '1' . str_repeat(']', 10));

        $this->expectException(DidException::class);
        $this->expectExceptionMessage('DID document is not valid JSON.');

        $this->sut(maxJsonDepth: 5)->resolveDocument(self::DID);
    }


    public function testRejectsADocumentThatIsNotAJsonObject(): void
    {
        $this->httpClientDecoratorMock->method('request')->willReturn($this->responseMock);
        $this->httpClientDecoratorMock->method('readResponseBodyAsString')->willReturn('"did:web:example.org"');

        $this->expectException(DidException::class);
        $this->expectExceptionMessage('DID document must be a JSON object.');

        $this->sut()->resolveDocument(self::DID);
    }


    public function testPropagatesAndRemembersADocumentFailure(): void
    {
        $this->httpClientDecoratorMock->expects($this->once())
            ->method('request')
            ->willReturn($this->responseMock);

        $this->httpClientDecoratorMock->method('readResponseBodyAsString')
            ->willReturn('{"id":"did:web:evil.example"}');

        $this->didDocumentFactoryMock->expects($this->once())
            ->method('fromData')
            ->willThrowException(new DidException('DID document id is not the DID the document was resolved for.'));

        $sut = $this->sutWithCache();

        // A document failure describes the document rather than this deployment's network, so it is reported
        // as it stands instead of being reduced to the generic retrieval message.
        $this->assertSame(
            'DID document id is not the DID the document was resolved for.',
            $this->failedResolutionMessage($sut, self::DID),
        );

        $this->expectException(DidException::class);
        $this->expectExceptionMessage('DID document id is not the DID the document was resolved for.');

        $sut->resolveDocument(self::DID);
    }


    public function testBoundsADocumentFailureMessage(): void
    {
        $this->httpClientDecoratorMock->method('request')->willReturn($this->responseMock);
        $this->httpClientDecoratorMock->method('readResponseBodyAsString')->willReturn(self::DOCUMENT_JSON);

        // The parser builds this message by interpolating values out of the document, so its length is
        // whoever published the document's choice, not ours. It travels to the caller and into the cache.
        $this->didDocumentFactoryMock->method('fromData')
            ->willThrowException(new DidException('Unsupported JWK key type: ' . str_repeat('A', 100000)));

        $sut = $this->sutWithCache();
        $message = $this->failedResolutionMessage($sut, self::DID);

        $this->assertSame(256, strlen($message));
        $this->assertStringStartsWith('Unsupported JWK key type: ', $message);
        $this->assertSame(
            $message,
            $this->cacheStore[$this->cacheKey('openid.did.web', 'failure', self::DID)],
        );
    }


    public function testKeepsATruncatedFailureMessageValidUtf8(): void
    {
        $this->httpClientDecoratorMock->method('request')->willReturn($this->responseMock);
        $this->httpClientDecoratorMock->method('readResponseBodyAsString')->willReturn(self::DOCUMENT_JSON);

        // Three bytes per character, so a byte-wise cut at 256 lands inside one. A message that is not
        // valid UTF-8 cannot be json_encoded, which would break whatever tries to report it.
        $this->didDocumentFactoryMock->method('fromData')
            ->willThrowException(new DidException('Unsupported JWK key type: ' . str_repeat('€', 500)));

        $message = $this->failedResolutionMessage($this->sutWithCache(), self::DID);

        $this->assertLessThanOrEqual(256, strlen($message));
        $this->assertTrue(mb_check_encoding($message, 'UTF-8'));
        $this->assertIsString(json_encode($message, JSON_THROW_ON_ERROR));
    }


    public function testRepairsAFailureMessageThatWasAlreadyMalformed(): void
    {
        $this->httpClientDecoratorMock->method('request')->willReturn($this->responseMock);
        $this->httpClientDecoratorMock->method('readResponseBodyAsString')->willReturn(self::DOCUMENT_JSON);

        // Short enough that truncation never runs, so cutting on a character boundary cannot help. A parser
        // that reports one offending byte of a multibyte character produces exactly this.
        $this->didDocumentFactoryMock->method('fromData')
            ->willThrowException(new DidException("Invalid character in base58 string: \xE2"));

        $message = $this->failedResolutionMessage($this->sutWithCache(), self::DID);

        $this->assertTrue(mb_check_encoding($message, 'UTF-8'));
        $this->assertIsString(json_encode($message, JSON_THROW_ON_ERROR));
    }


    public function testLeavesAFailureMessageThatFitsAlone(): void
    {
        $this->httpClientDecoratorMock->method('request')->willReturn($this->responseMock);
        $this->httpClientDecoratorMock->method('readResponseBodyAsString')->willReturn(self::DOCUMENT_JSON);
        $this->didDocumentFactoryMock->method('fromData')
            ->willThrowException(new DidException('Unsupported JWK key type: oct.'));

        $this->assertSame(
            'Unsupported JWK key type: oct.',
            $this->failedResolutionMessage($this->sutWithCache(), self::DID),
        );
    }


    public function testBoundsWhatADiscardedCachedDocumentPutsInTheLog(): void
    {
        $this->cacheStore[$this->cacheKey('openid.did.web', 'document', self::DID)] = self::DOCUMENT_JSON;

        $this->didDocumentFactoryMock->method('fromData')->willReturnOnConsecutiveCalls(
            $this->throwException(new DidException('Unsupported JWK key type: ' . str_repeat('A', 100000))),
            $this->didDocumentMock,
        );

        $this->httpClientDecoratorMock->method('request')->willReturn($this->responseMock);
        $this->httpClientDecoratorMock->method('readResponseBodyAsString')->willReturn(self::DOCUMENT_JSON);

        $this->loggerMock->expects($this->once())->method('warning')
            ->with($this->callback(fn(string $message): bool => strlen($message) < 512));

        $this->sut(
            cacheDecorator: $this->cacheDecoratorMock,
            logger: $this->loggerMock,
        )->resolveDocument(self::DID);
    }


    public function testAppliesADeadlineToTheFetch(): void
    {
        $deadlineTimestamp = microtime(true) + 5.0;

        $this->httpClientDecoratorMock->expects($this->once())
            ->method('timeoutCeilingOptions')
            ->with($deadlineTimestamp)
            ->willReturn(['timeout' => 1.5]);

        $this->httpClientDecoratorMock->expects($this->once())
            ->method('request')
            ->with(HttpMethodsEnum::GET, self::DOCUMENT_URL, ['timeout' => 1.5], null)
            ->willReturn($this->responseMock);

        $this->httpClientDecoratorMock->method('readResponseBodyAsString')->willReturn(self::DOCUMENT_JSON);
        $this->didDocumentFactoryMock->method('fromData')->willReturn($this->didDocumentMock);

        $this->assertSame($this->didDocumentMock, $this->sut()->resolveDocument(self::DID, $deadlineTimestamp));
    }


    public function testRefusesToFetchOnceTheDeadlineHasPassed(): void
    {
        $this->httpClientDecoratorMock->expects($this->never())->method('request');

        $this->expectException(DidException::class);
        $this->expectExceptionMessage('Could not retrieve the DID document.');

        $this->sut()->resolveDocument(self::DID, microtime(true) - 1.0);
    }


    public function testDoesNotRememberAFailureCausedByAnExhaustedDeadline(): void
    {
        $this->httpClientDecoratorMock->expects($this->never())->method('request');

        $sut = $this->sutWithCache();

        $this->failedResolutionMessage($sut, self::DID, microtime(true) - 1.0);

        // Our own budget running out says nothing about the DID, so it is not held against it.
        $this->assertSame([], $this->cacheStore);
    }


    public function testDoesNotReachTheNetworkOrTheCacheForAMalformedDid(): void
    {
        $this->httpClientDecoratorMock->expects($this->never())->method('request');

        $cacheDecoratorMock = $this->createMock(CacheDecorator::class);
        $cacheDecoratorMock->expects($this->never())->method('get');
        $cacheDecoratorMock->expects($this->never())->method('set');

        $this->expectException(DidException::class);

        $this->sut(cacheDecorator: $cacheDecoratorMock)->resolveDocument('did:web:example.org%2Fevil.test');
    }


    public function testSurvivesAnUnusableCache(): void
    {
        $cacheDecoratorMock = $this->createMock(CacheDecorator::class);
        $cacheDecoratorMock->method('get')->willThrowException(new RuntimeException('Cache unavailable.'));
        $cacheDecoratorMock->method('set')->willThrowException(new RuntimeException('Cache unavailable.'));

        $this->httpClientDecoratorMock->method('request')->willReturn($this->responseMock);
        $this->httpClientDecoratorMock->method('readResponseBodyAsString')->willReturn(self::DOCUMENT_JSON);
        $this->didDocumentFactoryMock->method('fromData')->willReturn($this->didDocumentMock);

        $this->assertSame(
            $this->didDocumentMock,
            $this->sut(cacheDecorator: $cacheDecoratorMock)->resolveDocument(self::DID),
        );
    }


    /**
     * Resolve, expecting it to fail, and hand back the message it failed with.
     */
    private function failedResolutionMessage(
        DidWebResolver $didWebResolver,
        string $did,
        ?float $deadlineTimestamp = null,
    ): string {
        try {
            $didWebResolver->resolveDocument($did, $deadlineTimestamp);
        } catch (DidException $didException) {
            return $didException->getMessage();
        }

        $this->fail('Expected resolution of ' . $did . ' to fail.');
    }
}
