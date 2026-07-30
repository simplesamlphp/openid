<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\TokenStatusList\Factories;

use DateInterval;
use DateTimeImmutable;
use Jose\Component\KeyManagement\JWKFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\StatusListTokenException;
use SimpleSAML\OpenID\Factories\AlgorithmManagerDecoratorFactory;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerDecoratorFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsDecoratorBuilderFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerBag;
use SimpleSAML\OpenID\Serializers\JwsSerializerEnum;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\SupportedAlgorithms;
use SimpleSAML\OpenID\SupportedSerializers;
use SimpleSAML\OpenID\TokenStatusList\Factories\StatusListFactory;
use SimpleSAML\OpenID\TokenStatusList\Factories\StatusListTokenFactory;
use SimpleSAML\OpenID\TokenStatusList\StatusList;
use SimpleSAML\OpenID\TokenStatusList\StatusListToken;

#[CoversClass(StatusListTokenFactory::class)]
#[UsesClass(ParsedJwsFactory::class)]
#[UsesClass(ParsedJws::class)]
#[UsesClass(StatusListToken::class)]
#[UsesClass(StatusList::class)]
#[UsesClass(StatusListFactory::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Base64Url::class)]
#[UsesClass(Helpers\Json::class)]
#[UsesClass(Helpers\Type::class)]
#[UsesClass(JwsDecorator::class)]
#[UsesClass(JwkDecorator::class)]
#[UsesClass(SignatureAlgorithmEnum::class)]
#[UsesClass(SignatureAlgorithmBag::class)]
#[UsesClass(AlgorithmManagerDecorator::class)]
#[UsesClass(AlgorithmManagerDecoratorFactory::class)]
#[UsesClass(DateIntervalDecorator::class)]
#[UsesClass(ClaimFactory::class)]
#[UsesClass(JwsSerializerManagerDecoratorFactory::class)]
#[UsesClass(JwsSerializerManagerDecorator::class)]
#[UsesClass(JwsSerializerBag::class)]
#[UsesClass(JwsSerializerEnum::class)]
#[UsesClass(JwsDecoratorBuilderFactory::class)]
#[UsesClass(JwsDecoratorBuilder::class)]
#[UsesClass(JwsVerifierDecoratorFactory::class)]
#[UsesClass(JwsVerifierDecorator::class)]
#[UsesClass(SupportedAlgorithms::class)]
#[UsesClass(SupportedSerializers::class)]
final class StatusListTokenFactoryTest extends TestCase
{
    protected const URI = 'https://example.com/statuslists/1';


    protected Helpers $helpers;

    protected StatusListFactory $statusListFactory;

    protected JwkDecorator $signingKey;


    protected function setUp(): void
    {
        $this->helpers = new Helpers();
        $this->statusListFactory = new StatusListFactory($this->helpers);
        $this->signingKey = new JwkDecorator(JWKFactory::createECKey('P-256'));
    }


    protected function sut(): StatusListTokenFactory
    {
        $supportedAlgorithms = new SupportedAlgorithms(
            new SignatureAlgorithmBag(SignatureAlgorithmEnum::ES256),
        );
        $jwsSerializerManagerDecorator = (new JwsSerializerManagerDecoratorFactory())
            ->build(new SupportedSerializers());
        $algorithmManagerDecorator = (new AlgorithmManagerDecoratorFactory())->build($supportedAlgorithms);

        return new StatusListTokenFactory(
            (new JwsDecoratorBuilderFactory())->build(
                $jwsSerializerManagerDecorator,
                $algorithmManagerDecorator,
                $this->helpers,
            ),
            (new JwsVerifierDecoratorFactory())->build($algorithmManagerDecorator),
            new JwksDecoratorFactory(),
            $jwsSerializerManagerDecorator,
            new DateIntervalDecorator(new DateInterval('PT1M')),
            $this->helpers,
            new ClaimFactory($this->helpers),
            $this->statusListFactory,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(StatusListTokenFactory::class, $this->sut());
    }


    public function testCanBuildForStatusList(): void
    {
        $issuedAt = new DateTimeImmutable('@1686920170');
        $expiresAt = new DateTimeImmutable('@2291720170');

        $statusListToken = $this->sut()->forStatusList(
            $this->statusListFactory->build(1, "\xB9\xA3"),
            self::URI,
            $this->signingKey,
            SignatureAlgorithmEnum::ES256,
            $issuedAt,
            $expiresAt,
            new DateInterval('PT12H'),
        );

        $this->assertSame('statuslist+jwt', $statusListToken->getType());
        $this->assertSame(self::URI, $statusListToken->getSubject());
        $this->assertSame(1686920170, $statusListToken->getIssuedAt());
        $this->assertSame(2291720170, $statusListToken->getExpirationTime());
        $this->assertSame(43200, $statusListToken->getTimeToLive());
        $this->assertSame(
            ['bits' => 1, 'lst' => 'eNrbuRgAAhcBXQ'],
            $statusListToken->getStatusListClaimData(),
        );
        $this->assertNull($statusListToken->getIssuer());
    }


    /**
     * Expiration time, ttl and issuer are all optional, and issued at defaults to now.
     */
    public function testCanBuildForStatusListWithOnlyRequiredClaims(): void
    {
        $statusListToken = $this->sut()->forStatusList(
            $this->statusListFactory->forCapacity(1024, 1),
            self::URI,
            $this->signingKey,
            SignatureAlgorithmEnum::ES256,
        );

        $this->assertNull($statusListToken->getExpirationTime());
        $this->assertNull($statusListToken->getTimeToLive());
        $this->assertNull($statusListToken->getIssuer());
        $this->assertEqualsWithDelta(time(), $statusListToken->getIssuedAt(), 5);
    }


    public function testCanBuildForStatusListWithIssuer(): void
    {
        $statusListToken = $this->sut()->forStatusList(
            $this->statusListFactory->forCapacity(1024, 1),
            self::URI,
            $this->signingKey,
            SignatureAlgorithmEnum::ES256,
            null,
            null,
            null,
            'https://example.com',
        );

        $this->assertSame('https://example.com', $statusListToken->getIssuer());
    }


    /**
     * The key identifier is a header claim the caller supplies, since the specification mandates no method for
     * binding a token to a key.
     */
    public function testCanBuildForStatusListWithAdditionalClaims(): void
    {
        $statusListToken = $this->sut()->forStatusList(
            $this->statusListFactory->forCapacity(1024, 1),
            self::URI,
            $this->signingKey,
            SignatureAlgorithmEnum::ES256,
            null,
            null,
            null,
            null,
            ['custom' => 'payload-claim'],
            ['kid' => 'did:jwk:example#0'],
        );

        $this->assertSame('did:jwk:example#0', $statusListToken->getKeyId());
        $this->assertSame('payload-claim', $statusListToken->getPayloadClaim('custom'));
    }


    /**
     * The type header is set by the factory, so a caller can not accidentally issue a token some other type.
     */
    public function testTypeHeaderCanNotBeOverridden(): void
    {
        $statusListToken = $this->sut()->fromData(
            $this->signingKey,
            SignatureAlgorithmEnum::ES256,
            [
                'sub' => self::URI,
                'iat' => time(),
                'status_list' => ['bits' => 1, 'lst' => 'eNrbuRgAAhcBXQ'],
            ],
            ['typ' => 'JWT'],
        );

        $this->assertSame('statuslist+jwt', $statusListToken->getType());
    }


    public function testCanBuildFromToken(): void
    {
        $sut = $this->sut();

        $token = $sut->forStatusList(
            $this->statusListFactory->build(1, "\xB9\xA3"),
            self::URI,
            $this->signingKey,
            SignatureAlgorithmEnum::ES256,
        )->getToken();

        $parsed = $sut->fromToken($token);

        $this->assertInstanceOf(StatusListToken::class, $parsed);
        $this->assertSame(self::URI, $parsed->getSubject());
        $this->assertSame("\xB9\xA3", $parsed->getStatusList(1024)->getBytes());
    }


    /**
     * The specification serves the JWT representation as a JWS Compact Serialization, so a JSON serialized JWS
     * is not a Status List Token -- not even where a deployment has enabled those serializers for other token
     * types, which would otherwise let one through carrying signatures and unprotected headers that nothing
     * here accounts for.
     */
    public function testFromTokenRejectsAJsonSerializedJws(): void
    {
        $token = $this->sut()->forStatusList(
            $this->statusListFactory->build(1, "\xB9\xA3"),
            self::URI,
            $this->signingKey,
            SignatureAlgorithmEnum::ES256,
        )->getToken();

        [$header, $payload, $signature] = explode('.', $token);

        $jsonSerialized = json_encode([
            'protected' => $header,
            'payload' => $payload,
            'signature' => $signature,
        ]);
        $this->assertIsString($jsonSerialized);

        $this->expectException(StatusListTokenException::class);
        $this->expectExceptionMessage('not a JWS Compact Serialization');

        $this->sut()->fromToken($jsonSerialized);
    }


    /**
     * @return \Iterator<string, array{string}>
     */
    public static function nonCompactTokenProvider(): \Iterator
    {
        yield 'empty' => [''];
        yield 'two segments' => ['aGVhZGVy.cGF5bG9hZA'];
        yield 'four segments' => ['aGVhZGVy.cGF5bG9hZA.c2ln.ZXh0cmE'];
        yield 'an empty segment' => ['aGVhZGVy..c2ln'];
        yield 'padded base64' => ['aGVhZGVy.cGF5bG9hZA==.c2ln'];
        yield 'trailing newline' => ["aGVhZGVy.cGF5bG9hZA.c2ln\n"];
        yield 'surrounding whitespace' => [' aGVhZGVy.cGF5bG9hZA.c2ln '];
    }


    #[DataProvider('nonCompactTokenProvider')]
    public function testFromTokenRejectsMalformedSerializations(string $token): void
    {
        $this->expectException(StatusListTokenException::class);
        $this->expectExceptionMessage('not a JWS Compact Serialization');

        $this->sut()->fromToken($token);
    }
}
