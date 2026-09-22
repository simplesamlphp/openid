<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\OAuth2\Factories;

use DateInterval;
use Jose\Component\KeyManagement\JWKFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Factories\AlgorithmManagerDecoratorFactory;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Factories\JwsSerializerManagerDecoratorFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwk\JwkDecorator;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jwks\JwksDecorator;
use SimpleSAML\OpenID\Jws\Factories\JwsDecoratorBuilderFactory;
use SimpleSAML\OpenID\Jws\Factories\JwsVerifierDecoratorFactory;
use SimpleSAML\OpenID\Jws\Factories\ParsedJwsFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsDecoratorBuilder;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\OAuth2\Factories\JwtAccessTokenFactory;
use SimpleSAML\OpenID\OAuth2\JwtAccessToken;
use SimpleSAML\OpenID\Serializers\JwsSerializerBag;
use SimpleSAML\OpenID\Serializers\JwsSerializerEnum;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\SupportedAlgorithms;
use SimpleSAML\OpenID\SupportedSerializers;

#[CoversClass(JwtAccessTokenFactory::class)]
#[UsesClass(ParsedJwsFactory::class)]
#[UsesClass(ParsedJws::class)]
#[UsesClass(JwtAccessToken::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Json::class)]
#[UsesClass(Helpers\MediaType::class)]
#[UsesClass(Helpers\Type::class)]
#[UsesClass(JwsDecorator::class)]
#[UsesClass(JwkDecorator::class)]
#[UsesClass(JwksDecorator::class)]
#[UsesClass(JwksDecoratorFactory::class)]
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
final class JwtAccessTokenFactoryTest extends TestCase
{
    protected Helpers $helpers;

    protected JwkDecorator $signingKey;

    /** @var array<string,mixed> */
    protected array $samplePayload;


    protected function setUp(): void
    {
        $this->helpers = new Helpers();
        $this->signingKey = new JwkDecorator(JWKFactory::createECKey('P-256', ['kid' => 'RjEwOwOA']));

        $this->samplePayload = [
            'iss' => 'https://authorization-server.example.com/',
            'sub' => '5ba552d67',
            'aud' => 'https://rs.example.com/',
            'exp' => time() + 3600,
            'iat' => time(),
            'jti' => 'dbe39bf3a3ba4238a513f51d6e1691c4',
            'client_id' => 's6BhdRkqt3',
            'scope' => 'openid profile reademail',
        ];
    }


    protected function sut(): JwtAccessTokenFactory
    {
        $supportedAlgorithms = new SupportedAlgorithms(
            new SignatureAlgorithmBag(SignatureAlgorithmEnum::ES256),
        );
        $jwsSerializerManagerDecorator = (new JwsSerializerManagerDecoratorFactory())
            ->build(new SupportedSerializers());
        $algorithmManagerDecorator = (new AlgorithmManagerDecoratorFactory())->build($supportedAlgorithms);

        return new JwtAccessTokenFactory(
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
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(JwtAccessTokenFactory::class, $this->sut());
    }


    public function testCanBuildFromData(): void
    {
        $jwtAccessToken = $this->sut()->fromData(
            $this->signingKey,
            SignatureAlgorithmEnum::ES256,
            $this->samplePayload,
            ['kid' => 'RjEwOwOA'],
        );

        $this->assertSame('at+jwt', $jwtAccessToken->getType());
        $this->assertSame('ES256', $jwtAccessToken->getAlgorithm());
        $this->assertSame('RjEwOwOA', $jwtAccessToken->getKeyId());
        $this->assertSame('5ba552d67', $jwtAccessToken->getSubject());
        $this->assertSame('s6BhdRkqt3', $jwtAccessToken->getClientId());
        $this->assertSame(['openid', 'profile', 'reademail'], $jwtAccessToken->getScopes());
    }


    public function testTypeHeaderCanNotBeOverridden(): void
    {
        $jwtAccessToken = $this->sut()->fromData(
            $this->signingKey,
            SignatureAlgorithmEnum::ES256,
            $this->samplePayload,
            ['typ' => 'JWT'],
        );

        $this->assertSame('at+jwt', $jwtAccessToken->getType());
    }


    public function testFromDataRefusesAPayloadTheProfileDoesNotAllow(): void
    {
        unset($this->samplePayload['client_id']);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('No Client ID claim found.');

        $this->sut()->fromData(
            $this->signingKey,
            SignatureAlgorithmEnum::ES256,
            $this->samplePayload,
            [],
        );
    }


    public function testCanBuildFromTokenAndVerifyIt(): void
    {
        $sut = $this->sut();

        $token = $sut->fromData(
            $this->signingKey,
            SignatureAlgorithmEnum::ES256,
            $this->samplePayload,
            [],
        )->getToken();

        $jwtAccessToken = $sut->fromToken($token);
        $jwtAccessToken->verifyWithKey($this->signingKey->jwk()->toPublic()->jsonSerialize());
        $jwtAccessToken->verifyWithKeySet(['keys' => [$this->signingKey->jwk()->toPublic()->jsonSerialize()]]);

        $this->assertInstanceOf(JwtAccessToken::class, $jwtAccessToken);
        $this->assertSame('at+jwt', $jwtAccessToken->getType());
        $this->assertSame($this->samplePayload['jti'], $jwtAccessToken->getJwtId());
        $this->assertSame([$this->samplePayload['aud']], $jwtAccessToken->getAudience());
    }


    public function testSignatureVerificationRefusesAnotherKey(): void
    {
        $sut = $this->sut();

        $token = $sut->fromData(
            $this->signingKey,
            SignatureAlgorithmEnum::ES256,
            $this->samplePayload,
            [],
        )->getToken();

        $otherKey = JWKFactory::createECKey('P-256');

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Could not verify JWS signature.');

        $sut->fromToken($token)->verifyWithKey($otherKey->toPublic()->jsonSerialize());
    }


    public function testFromTokenRefusesAJwtOfAnotherType(): void
    {
        // Signed the same way, but as a plain JWT: what an ID Token would look like to a resource server.
        $token = (new JwsDecoratorBuilderFactory())->build(
            (new JwsSerializerManagerDecoratorFactory())->build(new SupportedSerializers()),
            (new AlgorithmManagerDecoratorFactory())->build(
                new SupportedAlgorithms(new SignatureAlgorithmBag(SignatureAlgorithmEnum::ES256)),
            ),
            $this->helpers,
        )->fromData(
            $this->signingKey,
            SignatureAlgorithmEnum::ES256,
            $this->samplePayload,
            ['typ' => 'JWT'],
        );

        $serialized = (new JwsSerializerManagerDecoratorFactory())->build(new SupportedSerializers())
            ->serialize(JwsSerializerEnum::Compact->value, $token);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Invalid Type header claim');

        $this->sut()->fromToken($serialized);
    }
}
