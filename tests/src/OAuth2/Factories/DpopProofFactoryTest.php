<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\OAuth2\Factories;

use DateInterval;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\AlgorithmManagerDecorator;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmBag;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\DpopProofException;
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
use SimpleSAML\OpenID\OAuth2\DpopProof;
use SimpleSAML\OpenID\OAuth2\Factories\DpopProofFactory;
use SimpleSAML\OpenID\Serializers\JwsSerializerBag;
use SimpleSAML\OpenID\Serializers\JwsSerializerEnum;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;
use SimpleSAML\OpenID\SupportedAlgorithms;
use SimpleSAML\OpenID\SupportedSerializers;

#[CoversClass(DpopProofFactory::class)]
#[UsesClass(ParsedJwsFactory::class)]
#[UsesClass(ParsedJws::class)]
#[UsesClass(DpopProof::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Base64Url::class)]
#[UsesClass(Helpers\Json::class)]
#[UsesClass(Helpers\Jwk::class)]
#[UsesClass(Helpers\MediaType::class)]
#[UsesClass(Helpers\Type::class)]
#[UsesClass(Helpers\Url::class)]
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
final class DpopProofFactoryTest extends TestCase
{
    protected Helpers $helpers;

    /** @var array<string,mixed> */
    protected array $samplePayload;


    protected function setUp(): void
    {
        $this->helpers = new Helpers();

        $this->samplePayload = [
            'jti' => 'e1j3V_bKic8-LAEB',
            'htm' => 'GET',
            'htu' => 'https://resource.example.org/protectedresource',
            'iat' => time(),
            'ath' => 'fUHyO2r2Z3DZ53EsNrWBb0xWXoaNy59IiKCAqksmQEo',
        ];
    }


    protected function jwsDecoratorBuilder(SignatureAlgorithmEnum ...$algorithms): JwsDecoratorBuilder
    {
        return (new JwsDecoratorBuilderFactory())->build(
            (new JwsSerializerManagerDecoratorFactory())->build(new SupportedSerializers()),
            (new AlgorithmManagerDecoratorFactory())->build(
                new SupportedAlgorithms(new SignatureAlgorithmBag(...$algorithms)),
            ),
            $this->helpers,
        );
    }


    protected function sut(
        string $leeway = 'PT1M',
        SignatureAlgorithmEnum ...$algorithms,
    ): DpopProofFactory {
        $algorithms = $algorithms === [] ? [
            SignatureAlgorithmEnum::ES256,
            SignatureAlgorithmEnum::ES384,
            SignatureAlgorithmEnum::PS256,
            SignatureAlgorithmEnum::RS256,
            SignatureAlgorithmEnum::EdDSA,
        ] : $algorithms;

        $jwsSerializerManagerDecorator = (new JwsSerializerManagerDecoratorFactory())
            ->build(new SupportedSerializers());
        $algorithmManagerDecorator = (new AlgorithmManagerDecoratorFactory())->build(
            new SupportedAlgorithms(new SignatureAlgorithmBag(...$algorithms)),
        );

        return new DpopProofFactory(
            $this->jwsDecoratorBuilder(...$algorithms),
            (new JwsVerifierDecoratorFactory())->build($algorithmManagerDecorator),
            new JwksDecoratorFactory(),
            $jwsSerializerManagerDecorator,
            new DateIntervalDecorator(new DateInterval($leeway)),
            $this->helpers,
            new ClaimFactory($this->helpers),
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(DpopProofFactory::class, $this->sut());
    }


    /**
     * @return \Iterator<
     *     string,
     *     array{\Closure(): \Jose\Component\Core\JWK, \SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum}
     * >
     */
    public static function signingKeyProvider(): \Iterator
    {
        yield 'ES256 with P-256' => [
            static fn(): JWK => JWKFactory::createECKey('P-256', ['kid' => 'k1', 'use' => 'sig']),
            SignatureAlgorithmEnum::ES256,
        ];
        yield 'ES384 with P-384' => [
            static fn(): JWK => JWKFactory::createECKey('P-384'),
            SignatureAlgorithmEnum::ES384,
        ];
        yield 'PS256 with RSA 2048' => [
            static fn(): JWK => JWKFactory::createRSAKey(2048),
            SignatureAlgorithmEnum::PS256,
        ];
        yield 'EdDSA with Ed25519' => [
            static fn(): JWK => JWKFactory::createOKPKey('Ed25519'),
            SignatureAlgorithmEnum::EdDSA,
        ];
    }


    /**
     * @param \Closure(): \Jose\Component\Core\JWK $signingKey
     */
    #[DataProvider('signingKeyProvider')]
    public function testCanBuildFromDataAndVerifyFromToken(
        \Closure $signingKey,
        SignatureAlgorithmEnum $algorithm,
    ): void {
        $key = $signingKey();
        $sut = $this->sut();

        $dpopProof = $sut->fromData(new JwkDecorator($key), $algorithm, $this->samplePayload);

        $this->assertSame('dpop+jwt', $dpopProof->getType());
        $this->assertSame($algorithm->value, $dpopProof->getAlgorithm());
        $this->assertEquals(self::requiredMembers($key), $dpopProof->getJsonWebKey());
        $this->assertSame($key->thumbprint('sha256'), $dpopProof->getJwkThumbprint());
        $dpopProof->verifyWithEmbeddedKey();

        $parsed = $sut->fromToken($dpopProof->getToken());
        $parsed->verifyWithEmbeddedKey();

        $this->assertSame($key->thumbprint('sha256'), $parsed->getJwkThumbprint());
        $this->assertSame('e1j3V_bKic8-LAEB', $parsed->getJwtId());
        $this->assertTrue($parsed->matchesHttpRequest('GET', 'https://resource.example.org/protectedresource'));
        $this->assertTrue($parsed->matchesAccessToken('Kz~8mXK1EalYznwH-LC-1fBAo.4Ljp~zsPE_NeO.gxU'));
    }


    /**
     * "kty" and the key material, as RFC 7638 section 3.2 lists them, taken here independently of the code under
     * test.
     *
     * @return array<string,mixed>
     */
    protected static function requiredMembers(JWK $key): array
    {
        return array_intersect_key($key->toPublic()->all(), array_flip(['kty', 'crv', 'x', 'y', 'n', 'e']));
    }


    public function testTheProofCarriesOnlyThePublicKeyNotTheSigningKeysMetadata(): void
    {
        // A key whose metadata says it signs, as a private key's would: copied into the proof, "key_ops" would
        // forbid verifying with it.
        $key = JWKFactory::createECKey(
            'P-256',
            ['kid' => 'k1', 'use' => 'sig', 'key_ops' => ['sign'], 'alg' => 'ES256'],
        );
        $sut = $this->sut();

        $dpopProof = $sut->fromData(new JwkDecorator($key), SignatureAlgorithmEnum::ES256, $this->samplePayload);

        $this->assertEquals(self::requiredMembers($key), $dpopProof->getJsonWebKey());
        $this->assertArrayNotHasKey('key_ops', $dpopProof->getJsonWebKey());

        $sut->fromToken($dpopProof->getToken())->verifyWithEmbeddedKey();
    }


    public function testTheProofCarriesNoPrivateKeyMaterial(): void
    {
        $key = JWKFactory::createRSAKey(2048);

        $token = $this->sut()->fromData(new JwkDecorator($key), SignatureAlgorithmEnum::RS256, $this->samplePayload)
            ->getToken();

        $header = $this->helpers->json()->decode(
            $this->helpers->base64Url()->decode(explode('.', $token)[0]),
        );

        $this->assertIsArray($header);
        $this->assertEquals(['kty' => 'RSA', 'n' => $key->get('n'), 'e' => $key->get('e')], $header['jwk']);
    }


    public function testTheTypeAndKeyHeadersCanNotBeOverridden(): void
    {
        $key = JWKFactory::createECKey('P-256');
        $otherKey = JWKFactory::createECKey('P-256');

        $dpopProof = $this->sut()->fromData(
            new JwkDecorator($key),
            SignatureAlgorithmEnum::ES256,
            $this->samplePayload,
            ['typ' => 'JWT', 'jwk' => $otherKey->toPublic()->all()],
        );

        $this->assertSame('dpop+jwt', $dpopProof->getType());
        $this->assertEquals(self::requiredMembers($key), $dpopProof->getJsonWebKey());
        $dpopProof->verifyWithEmbeddedKey();
    }


    public function testRefusesASymmetricSigningKeyBeforeSigning(): void
    {
        $this->expectException(DpopProofException::class);
        $this->expectExceptionMessage('The signing key has no public key a DPoP proof can carry.');

        $this->sut()->fromData(
            new JwkDecorator(JWKFactory::createOctKey(256)),
            SignatureAlgorithmEnum::ES256,
            $this->samplePayload,
        );
    }


    public function testRefusesAShortRsaKey(): void
    {
        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('RSA key of 1024 bits is shorter than the 2048 bits RS256 requires');

        $this->sut()->fromData(
            new JwkDecorator(JWKFactory::createRSAKey(1024)),
            SignatureAlgorithmEnum::RS256,
            $this->samplePayload,
        );
    }


    public function testFromDataRefusesAnIncompletePayload(): void
    {
        unset($this->samplePayload['htu']);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('No HTTP URI claim found.');

        $this->sut()->fromData(
            new JwkDecorator(JWKFactory::createECKey('P-256')),
            SignatureAlgorithmEnum::ES256,
            $this->samplePayload,
        );
    }


    public function testRefusesAProofSignedWithAnotherKeyThanTheOneItCarries(): void
    {
        $key = JWKFactory::createECKey('P-256');
        $otherKey = JWKFactory::createECKey('P-256');

        // Signed with one key, while the header names another: what a proof would look like if a key it does not
        // hold were claimed.
        $jwsDecorator = $this->jwsDecoratorBuilder(SignatureAlgorithmEnum::ES256)->fromData(
            new JwkDecorator($key),
            SignatureAlgorithmEnum::ES256,
            $this->samplePayload,
            ['typ' => 'dpop+jwt', 'jwk' => $otherKey->toPublic()->all()],
        );
        $token = (new JwsSerializerManagerDecoratorFactory())->build(new SupportedSerializers())
            ->serialize(JwsSerializerEnum::Compact->value, $jwsDecorator);

        $dpopProof = $this->sut()->fromToken($token);

        $this->assertSame($otherKey->thumbprint('sha256'), $dpopProof->getJwkThumbprint());

        $this->expectException(DpopProofException::class);
        $this->expectExceptionMessage('Could not verify the DPoP proof signature with its own key.');

        $dpopProof->verifyWithEmbeddedKey();
    }


    public function testRefusesAProofWithATamperedPayload(): void
    {
        $token = $this->sut()->fromData(
            new JwkDecorator(JWKFactory::createECKey('P-256')),
            SignatureAlgorithmEnum::ES256,
            $this->samplePayload,
        )->getToken();

        [$header, , $signature] = explode('.', $token);
        $payload = $this->helpers->base64Url()->encode(
            $this->helpers->json()->encode(['htm' => 'POST'] + $this->samplePayload),
        );

        $dpopProof = $this->sut()->fromToken($header . '.' . $payload . '.' . $signature);

        $this->expectException(DpopProofException::class);

        $dpopProof->verifyWithEmbeddedKey();
    }


    public function testVerificationUsesTheFactorysAlgorithms(): void
    {
        $token = $this->sut()->fromData(
            new JwkDecorator(JWKFactory::createECKey('P-384')),
            SignatureAlgorithmEnum::ES384,
            $this->samplePayload,
        )->getToken();

        $dpopProof = $this->sut('PT1M', SignatureAlgorithmEnum::ES256)->fromToken($token);

        $this->expectException(DpopProofException::class);

        $dpopProof->verifyWithEmbeddedKey();
    }


    public function testFromTokenRefusesAJwtOfAnotherType(): void
    {
        $key = JWKFactory::createECKey('P-256');

        $jwsDecorator = $this->jwsDecoratorBuilder(SignatureAlgorithmEnum::ES256)->fromData(
            new JwkDecorator($key),
            SignatureAlgorithmEnum::ES256,
            $this->samplePayload,
            ['typ' => 'JWT', 'jwk' => $key->toPublic()->all()],
        );
        $token = (new JwsSerializerManagerDecoratorFactory())->build(new SupportedSerializers())
            ->serialize(JwsSerializerEnum::Compact->value, $jwsDecorator);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Invalid Type header claim');

        $this->sut()->fromToken($token);
    }


    public function testTheLeewayIsTheFactorys(): void
    {
        $this->samplePayload['iat'] = time() + 30;
        $key = new JwkDecorator(JWKFactory::createECKey('P-256'));

        $this->assertInstanceOf(
            DpopProof::class,
            $this->sut('PT1M')->fromData($key, SignatureAlgorithmEnum::ES256, $this->samplePayload),
        );

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Issued At claim');

        $this->sut('PT10S')->fromData($key, SignatureAlgorithmEnum::ES256, $this->samplePayload);
    }
}
