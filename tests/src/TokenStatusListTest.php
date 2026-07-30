<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID;

use DateInterval;
use DateTimeImmutable;
use Jose\Component\KeyManagement\JWKFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Codebooks\StatusTypeEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Decorators\HttpClientDecorator;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Exceptions\StatusListTokenException;
use SimpleSAML\OpenID\Factories\AlgorithmManagerDecoratorFactory;
use SimpleSAML\OpenID\Factories\CacheDecoratorFactory;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Factories\DateIntervalDecoratorFactory;
use SimpleSAML\OpenID\Factories\HttpClientDecoratorFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerDecoratorFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jwks\JwksDecorator;
use SimpleSAML\OpenID\Jws\Factories\JwsDecoratorBuilderFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerBag;
use SimpleSAML\OpenID\Serializers\JwsSerializerEnum;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\SupportedAlgorithms;
use SimpleSAML\OpenID\SupportedSerializers;
use SimpleSAML\OpenID\TokenStatusList;
use SimpleSAML\OpenID\TokenStatusList\Factories\StatusListFactory;
use SimpleSAML\OpenID\TokenStatusList\Factories\StatusListTokenFactory;
use SimpleSAML\OpenID\TokenStatusList\Factories\StatusReferenceFactory;
use SimpleSAML\OpenID\TokenStatusList\StatusClaim;
use SimpleSAML\OpenID\TokenStatusList\StatusList;
use SimpleSAML\OpenID\TokenStatusList\StatusListToken;
use SimpleSAML\OpenID\TokenStatusList\StatusListTokenFetcher;
use SimpleSAML\OpenID\TokenStatusList\StatusReference;
use SimpleSAML\OpenID\TokenStatusList\StatusResolver;
use SimpleSAML\OpenID\TokenStatusList\StatusResult;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

#[CoversClass(TokenStatusList::class)]
#[UsesClass(DateIntervalDecorator::class)]
#[UsesClass(DateIntervalDecoratorFactory::class)]
#[UsesClass(CacheDecoratorFactory::class)]
#[UsesClass(HttpClientDecoratorFactory::class)]
#[UsesClass(HttpClientDecorator::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Base64Url::class)]
#[UsesClass(Helpers\Json::class)]
#[UsesClass(Helpers\Type::class)]
#[UsesClass(ArtifactFetcher::class)]
#[UsesClass(ClaimFactory::class)]
#[UsesClass(JwsSerializerManagerDecoratorFactory::class)]
#[UsesClass(JwsSerializerManagerDecorator::class)]
#[UsesClass(AlgorithmManagerDecoratorFactory::class)]
#[UsesClass(AlgorithmManagerDecorator::class)]
#[UsesClass(JwsDecoratorBuilderFactory::class)]
#[UsesClass(JwsDecoratorBuilder::class)]
#[UsesClass(JwsDecorator::class)]
#[UsesClass(JwsVerifierDecoratorFactory::class)]
#[UsesClass(JwsVerifierDecorator::class)]
#[UsesClass(JwksDecoratorFactory::class)]
#[UsesClass(JwksDecorator::class)]
#[UsesClass(ParsedJws::class)]
#[UsesClass(JwkDecorator::class)]
#[UsesClass(SignatureAlgorithmEnum::class)]
#[UsesClass(SignatureAlgorithmBag::class)]
#[UsesClass(SupportedAlgorithms::class)]
#[UsesClass(SupportedSerializers::class)]
#[UsesClass(JwsSerializerBag::class)]
#[UsesClass(JwsSerializerEnum::class)]
#[UsesClass(StatusList::class)]
#[UsesClass(StatusListFactory::class)]
#[UsesClass(StatusListToken::class)]
#[UsesClass(StatusListTokenFactory::class)]
#[UsesClass(StatusListTokenFetcher::class)]
#[UsesClass(StatusReference::class)]
#[UsesClass(StatusReferenceFactory::class)]
#[UsesClass(StatusClaim::class)]
#[UsesClass(StatusResolver::class)]
#[UsesClass(StatusResult::class)]
final class TokenStatusListTest extends TestCase
{
    protected const URI = 'https://example.com/statuslists/1';


    protected function sut(): TokenStatusList
    {
        // The specification's own examples use ES256, which is not in the library's default algorithm set.
        return new TokenStatusList(
            new SupportedAlgorithms(
                new SignatureAlgorithmBag(
                    SignatureAlgorithmEnum::RS256,
                    SignatureAlgorithmEnum::ES256,
                ),
            ),
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(TokenStatusList::class, $this->sut());
    }


    public function testCanBuildTools(): void
    {
        $sut = $this->sut();

        $this->assertInstanceOf(DateIntervalDecoratorFactory::class, $sut->dateIntervalDecoratorFactory());
        $this->assertInstanceOf(CacheDecoratorFactory::class, $sut->cacheDecoratorFactory());
        $this->assertInstanceOf(HttpClientDecoratorFactory::class, $sut->httpClientDecoratorFactory());
        $this->assertInstanceOf(DateIntervalDecorator::class, $sut->maxCacheDurationDecorator());
        $this->assertInstanceOf(DateIntervalDecorator::class, $sut->timestampValidationLeewayDecorator());
        $this->assertNotInstanceOf(\SimpleSAML\OpenID\Decorators\CacheDecorator::class, $sut->cacheDecorator());
        $this->assertInstanceOf(Helpers::class, $sut->helpers());
        $this->assertInstanceOf(ArtifactFetcher::class, $sut->artifactFetcher());
        $this->assertInstanceOf(ClaimFactory::class, $sut->claimFactory());
        $this->assertInstanceOf(
            JwsSerializerManagerDecoratorFactory::class,
            $sut->jwsSerializerManagerDecoratorFactory(),
        );
        $this->assertInstanceOf(JwsSerializerManagerDecorator::class, $sut->jwsSerializerManagerDecorator());
        $this->assertInstanceOf(AlgorithmManagerDecoratorFactory::class, $sut->algorithmManagerDecoratorFactory());
        $this->assertInstanceOf(AlgorithmManagerDecorator::class, $sut->algorithmManagerDecorator());
        $this->assertInstanceOf(JwsDecoratorBuilderFactory::class, $sut->jwsDecoratorBuilderFactory());
        $this->assertInstanceOf(JwsDecoratorBuilder::class, $sut->jwsDecoratorBuilder());
        $this->assertInstanceOf(JwsVerifierDecoratorFactory::class, $sut->jwsVerifierDecoratorFactory());
        $this->assertInstanceOf(JwsVerifierDecorator::class, $sut->jwsVerifierDecorator());
        $this->assertInstanceOf(JwksDecoratorFactory::class, $sut->jwksDecoratorFactory());
        $this->assertInstanceOf(StatusListFactory::class, $sut->statusListFactory());
        $this->assertInstanceOf(StatusReferenceFactory::class, $sut->statusReferenceFactory());
        $this->assertInstanceOf(StatusListTokenFactory::class, $sut->statusListTokenFactory());
        $this->assertInstanceOf(StatusListTokenFetcher::class, $sut->statusListTokenFetcher());
        $this->assertInstanceOf(StatusResolver::class, $sut->statusResolver());
    }


    public function testToolsAreMemoized(): void
    {
        $sut = $this->sut();

        $this->assertSame($sut->statusListFactory(), $sut->statusListFactory());
        $this->assertSame($sut->statusListTokenFactory(), $sut->statusListTokenFactory());
        $this->assertSame($sut->statusResolver(), $sut->statusResolver());
    }


    /**
     * Issue a Status List Token, serialize it, parse it back, verify its signature and read a status out of it,
     * the way an issuer and a relying party each would.
     */
    public function testCanIssueAndResolveStatus(): void
    {
        $sut = $this->sut();
        $key = JWKFactory::createECKey('P-256');
        $signingKey = new JwkDecorator($key);

        $statusList = $sut->statusListFactory()->fromEntries(
            [
                1 => StatusTypeEnum::Invalid,
                2 => StatusTypeEnum::Suspended,
            ],
            2,
            1024,
        );

        $issuedToken = $sut->statusListTokenFactory()->forStatusList(
            $statusList,
            self::URI,
            $signingKey,
            SignatureAlgorithmEnum::ES256,
            new DateTimeImmutable(),
            (new DateTimeImmutable())->add(new DateInterval('P7D')),
            new DateInterval('PT12H'),
        );

        $token = $issuedToken->getToken();

        // Relying party side, starting from the compact serialization it fetched.
        $parsedToken = $sut->statusListTokenFactory()->fromToken($token);

        $this->assertSame('statuslist+jwt', $parsedToken->getType());
        $this->assertSame(self::URI, $parsedToken->getSubject());
        $this->assertSame(43200, $parsedToken->getTimeToLive());

        $publicKeySet = ['keys' => [$key->toPublic()->jsonSerialize()]];

        $expectedStatuses = [
            0 => StatusTypeEnum::Valid,
            1 => StatusTypeEnum::Invalid,
            2 => StatusTypeEnum::Suspended,
        ];

        foreach ($expectedStatuses as $idx => $expected) {
            $statusResult = $sut->statusResolver()->resolveWithToken(
                $sut->statusReferenceFactory()->build(self::URI, $idx),
                $parsedToken,
                $publicKeySet,
                256,
            );

            $this->assertSame($expected, $statusResult->getStatusType());
            $this->assertSame($expected === StatusTypeEnum::Valid, $statusResult->isValid());
        }
    }


    /**
     * A token whose subject is not the URI the credential pointed at is rejected, even though it is properly
     * signed by the same issuer.
     */
    public function testRejectsTokenWithMismatchedSubject(): void
    {
        $sut = $this->sut();
        $key = JWKFactory::createECKey('P-256');

        $issuedToken = $sut->statusListTokenFactory()->forStatusList(
            $sut->statusListFactory()->forCapacity(1024, 1),
            'https://example.com/statuslists/2',
            new JwkDecorator($key),
            SignatureAlgorithmEnum::ES256,
        );

        $this->expectException(StatusListTokenException::class);
        $this->expectExceptionMessage('subject does not match');

        $sut->statusResolver()->resolveWithToken(
            $sut->statusReferenceFactory()->build(self::URI, 0),
            $sut->statusListTokenFactory()->fromToken($issuedToken->getToken()),
            ['keys' => [$key->toPublic()->jsonSerialize()]],
            128,
        );
    }


    /**
     * A token signed by a key other than the one the relying party trusts is rejected.
     */
    public function testRejectsTokenWithWrongSignature(): void
    {
        $sut = $this->sut();

        $issuedToken = $sut->statusListTokenFactory()->forStatusList(
            $sut->statusListFactory()->forCapacity(1024, 1),
            self::URI,
            new JwkDecorator(JWKFactory::createECKey('P-256')),
            SignatureAlgorithmEnum::ES256,
        );

        $this->expectException(JwsException::class);

        $sut->statusResolver()->resolveWithToken(
            $sut->statusReferenceFactory()->build(self::URI, 0),
            $sut->statusListTokenFactory()->fromToken($issuedToken->getToken()),
            ['keys' => [JWKFactory::createECKey('P-256')->toPublic()->jsonSerialize()]],
            128,
        );
    }


    /**
     * An index beyond the end of the list yields no statement about the Referenced Token.
     */
    public function testRejectsOutOfRangeIndex(): void
    {
        $sut = $this->sut();
        $key = JWKFactory::createECKey('P-256');

        $issuedToken = $sut->statusListTokenFactory()->forStatusList(
            $sut->statusListFactory()->forCapacity(1024, 1),
            self::URI,
            new JwkDecorator($key),
            SignatureAlgorithmEnum::ES256,
        );

        $this->expectExceptionMessage('out of bounds');

        $sut->statusResolver()->resolveWithToken(
            $sut->statusReferenceFactory()->build(self::URI, 1024),
            $sut->statusListTokenFactory()->fromToken($issuedToken->getToken()),
            ['keys' => [$key->toPublic()->jsonSerialize()]],
            128,
        );
    }


    /**
     * The claim an issuer puts into a Referenced Token round-trips back into the reference a relying party
     * resolves with.
     */
    public function testStatusClaimRoundTripsThroughAReferencedTokenPayload(): void
    {
        $sut = $this->sut();

        $statusClaim = $sut->statusReferenceFactory()->buildClaim(self::URI, 42);

        $referencedTokenPayload = ['iss' => 'https://example.com'] + $statusClaim->jsonSerialize();

        $statusReference = $sut->statusReferenceFactory()->fromReferencedTokenPayload($referencedTokenPayload);

        $this->assertInstanceOf(StatusReference::class, $statusReference);
        $this->assertSame(self::URI, $statusReference->getUri());
        $this->assertSame(42, $statusReference->getIdx());
    }
}
