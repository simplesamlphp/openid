<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\OAuth2;

use DateInterval;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\DpopProofException;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jwks\JwksDecorator;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\OAuth2\DpopProof;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(DpopProof::class)]
#[UsesClass(DateIntervalDecorator::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Json::class)]
#[UsesClass(Helpers\Jwk::class)]
#[UsesClass(Helpers\MediaType::class)]
#[UsesClass(Helpers\Type::class)]
#[UsesClass(Helpers\Url::class)]
#[UsesClass(ParsedJws::class)]
#[UsesClass(SignatureAlgorithmEnum::class)]
final class DpopProofTest extends TestCase
{
    /**
     * The clock the proofs are held against, unless a test says otherwise. A whole second, so that a fraction in
     * a claim is the only fraction in a comparison.
     */
    protected const NOW = 1_700_000_000.0;

    /**
     * The public key of the DPoP proof in RFC 9449 section 4.2, Figure 4.
     */
    protected const JWK = [
        'kty' => 'EC',
        'x' => 'l8tFrhx-34tV3hRICRDY9zCkDlpBhF42UQUfWVAWBFs',
        'y' => '9VE4jf_Ok_o64zbTTlcuNJajHmt6v9TDVrU0CdvGRDA',
        'crv' => 'P-256',
    ];

    /**
     * The "jkt" RFC 9449 section 6.1 gives for that key.
     */
    protected const JWK_THUMBPRINT = '0ZcOCORZNYy-DWpqq30jZyJGHTN0d2HglBV3uiguA4I';

    /**
     * The access token of RFC 9449 section 7.1, Figure 12, and its "ath" from Figure 13.
     */
    protected const ACCESS_TOKEN = 'Kz~8mXK1EalYznwH-LC-1fBAo.4Ljp~zsPE_NeO.gxU';

    protected const ACCESS_TOKEN_HASH = 'fUHyO2r2Z3DZ53EsNrWBb0xWXoaNy59IiKCAqksmQEo';


    protected MockObject $signatureMock;

    protected MockObject $jwsMock;

    protected MockObject $jwsDecoratorMock;

    protected Helpers $helpers;

    /** @var array<string,mixed> */
    protected array $sampleHeader;

    /** @var array<string,mixed> */
    protected array $samplePayload;


    protected function setUp(): void
    {
        $this->signatureMock = $this->createMock(Signature::class);

        $this->jwsMock = $this->createMock(JWS::class);
        $this->jwsMock->method('getSignature')->willReturn($this->signatureMock);

        $this->jwsDecoratorMock = $this->createMock(JwsDecorator::class);
        $this->jwsDecoratorMock->method('jws')->willReturn($this->jwsMock);

        // Real helpers, so the type strictness and the key checks are exercised rather than stubbed away.
        $this->helpers = new Helpers();

        // The proof of RFC 9449 section 4.2, Figure 4, created five seconds before NOW.
        $this->sampleHeader = [
            'typ' => 'dpop+jwt',
            'alg' => 'ES256',
            'jwk' => self::JWK,
        ];

        $this->samplePayload = [
            'jti' => '-BwC3ESc6acc2lTc',
            'htm' => 'POST',
            'htu' => 'https://server.example.com/token',
            'iat' => (int)self::NOW - 5,
        ];
    }


    /**
     * An odd modulus of exactly the given number of bits, base64url encoded, with no prime factor below 752 and
     * not a perfect power, so that the checks on a modulus let it through to the one a test is about.
     */
    protected static function factorFreeModulus(int $bits): string
    {
        $smallOddPrimes = gmp_init(1);

        for ($candidate = 3; $candidate < 752; $candidate += 2) {
            if (gmp_prob_prime($candidate) > 0) {
                $smallOddPrimes = gmp_mul($smallOddPrimes, $candidate);
            }
        }

        $modulus = gmp_add(gmp_pow(2, $bits - 1), 1);

        while (gmp_cmp(gmp_gcd($modulus, $smallOddPrimes), 1) !== 0 || gmp_perfect_power($modulus)) {
            $modulus = gmp_add($modulus, 2);
        }

        return rtrim(strtr(base64_encode(gmp_export($modulus)), '+/', '-_'), '=');
    }


    /**
     * A proof held against a fixed clock, or against the real one when $now is null.
     *
     * @param ?array<string,mixed> $payload
     * @param ?array<string,mixed> $header
     */
    protected function sut(
        ?array $payload = null,
        ?array $header = null,
        int $leewaySeconds = 60,
        ?float $now = self::NOW,
        ?JwsVerifierDecorator $jwsVerifierDecorator = null,
        ?JwksDecoratorFactory $jwksDecoratorFactory = null,
    ): DpopProof {
        $payload ??= $this->samplePayload;
        $header ??= $this->sampleHeader;

        $this->jwsMock->method('getPayload')->willReturn(json_encode($payload));
        $this->signatureMock->method('getProtectedHeader')->willReturn($header);

        $arguments = [
            $this->jwsDecoratorMock,
            $jwsVerifierDecorator ?? $this->createStub(JwsVerifierDecorator::class),
            $jwksDecoratorFactory ?? $this->createStub(JwksDecoratorFactory::class),
            $this->createStub(JwsSerializerManagerDecorator::class),
            new DateIntervalDecorator(new DateInterval(sprintf('PT%dS', $leewaySeconds))),
            $this->helpers,
            $this->createStub(ClaimFactory::class),
        ];

        if (is_null($now)) {
            return new DpopProof(...$arguments);
        }

        return new class ($now, ...$arguments) extends DpopProof {
            public function __construct(
                private readonly float $fixedNow,
                JwsDecorator $jwsDecorator,
                JwsVerifierDecorator $jwsVerifierDecorator,
                JwksDecoratorFactory $jwksDecoratorFactory,
                JwsSerializerManagerDecorator $jwsSerializerManagerDecorator,
                DateIntervalDecorator $timestampValidationLeeway,
                Helpers $helpers,
                ClaimFactory $claimFactory,
            ) {
                parent::__construct(
                    $jwsDecorator,
                    $jwsVerifierDecorator,
                    $jwksDecoratorFactory,
                    $jwsSerializerManagerDecorator,
                    $timestampValidationLeeway,
                    $helpers,
                    $claimFactory,
                );
            }


            protected function currentTime(): float
            {
                return $this->fixedNow;
            }
        };
    }


    /**
     * The constructor's failure for the given payload and header, so a test can name the check it expects to
     * fail without the others failing alongside it unseen.
     *
     * @param ?array<string,mixed> $payload
     * @param ?array<string,mixed> $header
     */
    protected function constructionFailure(
        ?array $payload = null,
        ?array $header = null,
        int $leewaySeconds = 60,
    ): string {
        try {
            $this->sut($payload, $header, $leewaySeconds);
        } catch (JwsException $jwsException) {
            return $jwsException->getMessage();
        }

        $this->fail('The proof was accepted.');
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(DpopProof::class, $this->sut());
    }


    public function testCanGetTheRequiredHeaderAndClaims(): void
    {
        $sut = $this->sut();

        $this->assertSame('dpop+jwt', $sut->getType());
        $this->assertSame('ES256', $sut->getAlgorithm());
        $this->assertSame(self::JWK, $sut->getJsonWebKey());
        $this->assertSame('-BwC3ESc6acc2lTc', $sut->getJwtId());
        $this->assertSame('POST', $sut->getHttpMethod());
        $this->assertSame('https://server.example.com/token', $sut->getHttpUri());
        $this->assertSame((int)self::NOW - 5, $sut->getIssuedAt());
        $this->assertSame((int)self::NOW - 5, $sut->getIssuedAtNumericDate());
    }


    public function testTheOptionalClaimsAreNullWhenAbsent(): void
    {
        $sut = $this->sut();

        $this->assertNull($sut->getAccessTokenHash());
        $this->assertNull($sut->getNonce());
        $this->assertNull($sut->getNotBefore());
        $this->assertNull($sut->getExpirationTime());
    }


    public function testCanGetTheOptionalClaims(): void
    {
        $this->samplePayload['ath'] = self::ACCESS_TOKEN_HASH;
        $this->samplePayload['nonce'] = 'eyJ7S_zG.eyJH0-Z.HX4w-7v';
        $this->samplePayload['nbf'] = (int)self::NOW - 10;
        $this->samplePayload['exp'] = (int)self::NOW + 30;

        $sut = $this->sut();

        $this->assertSame(self::ACCESS_TOKEN_HASH, $sut->getAccessTokenHash());
        $this->assertSame('eyJ7S_zG.eyJH0-Z.HX4w-7v', $sut->getNonce());
        $this->assertSame((int)self::NOW - 10, $sut->getNotBefore());
        $this->assertSame((int)self::NOW + 30, $sut->getExpirationTime());
    }


    /**
     * @return \Iterator<string, array{string}>
     */
    public static function acceptedTypeProvider(): \Iterator
    {
        yield 'as registered' => ['dpop+jwt'];
        yield 'upper case' => ['DPOP+JWT'];
        yield 'full media type' => ['application/dpop+jwt'];
        yield 'full media type, mixed case' => ['Application/DPoP+JWT'];
    }


    #[DataProvider('acceptedTypeProvider')]
    public function testAcceptsEitherSpellingOfTheType(string $typ): void
    {
        $this->sampleHeader['typ'] = $typ;

        $this->assertSame($typ, $this->sut()->getType());
    }


    /**
     * @return \Iterator<string, array{array<string,mixed>, string}>
     */
    public static function invalidHeaderProvider(): \Iterator
    {
        yield 'no type' => [['typ' => null], 'No Type header claim found.'];
        yield 'type of a plain JWT' => [['typ' => 'JWT'], 'Invalid Type header claim (JWT)'];
        yield 'type of an access token' => [['typ' => 'at+jwt'], 'Invalid Type header claim (at+jwt)'];
        yield 'type with a parameter' => [['typ' => 'dpop+jwt;v=1'], 'Invalid Type header claim'];
        yield 'type that is not a string' => [['typ' => 1], 'Value is not a string'];
        yield 'no algorithm' => [['alg' => null], 'No Algorithm header claim found.'];
        yield 'algorithm none' => [['alg' => 'none'], 'Invalid Algorithm header claim (none).'];
        yield 'symmetric algorithm' => [['alg' => 'HS256'], 'Invalid Algorithm header claim.'];
        yield 'algorithm the key does not fit' => [['alg' => 'ES384'], 'not a key algorithm ES384'];
        yield 'algorithm of another key type' => [['alg' => 'RS256'], 'not a key algorithm RS256'];
        yield 'critical header parameter' => [['crit' => ['exp']], 'marks header parameters as critical'];
        yield 'unencoded payload option' => [['b64' => false], 'carries the b64 header parameter'];
        yield 'no key' => [['jwk' => null], 'No JWK header claim found.'];
        yield 'key that is not an object' => [['jwk' => 'eyJrdHkiOiJFQyJ9'], 'JWK is not a JSON object (jwk)'];
        yield 'private key' => [
            ['jwk' => self::JWK + ['d' => 'jpsQnnGQmL-YBIffH1136cspYG6-0iY7X1fCE9-E9LI']],
            'not part of a public EC key (jwk)',
        ];
        yield 'symmetric key' => [['jwk' => ['kty' => 'oct', 'k' => 'c2VjcmV0']], 'not of an asymmetric key type'];
        yield 'short RSA key' => [
            [
                'alg' => 'RS256',
                'jwk' => ['kty' => 'RSA', 'n' => self::factorFreeModulus(1024), 'e' => 'AQAB'],
            ],
            'RSA key of 1024 bits is shorter than the 2048 bits RS256 requires',
        ];
        yield 'RSA key with an exponent of 1, whose signatures anyone can compute' => [
            [
                'alg' => 'RS256',
                'jwk' => ['kty' => 'RSA', 'n' => self::factorFreeModulus(2048), 'e' => 'AQ'],
            ],
            'member e is not an odd RSA exponent of at least 3',
        ];
        yield 'key with a coordinate a lenient decoder would pad' => [
            ['jwk' => ['x' => rtrim(strtr(base64_encode(str_repeat("\x01", 31)), '+/', '-_'), '=')] + self::JWK],
            'member x is not the 32 octets a P-256 key takes (jwk)',
        ];
    }


    /**
     * @return \Iterator<string, array{string, mixed}>
     */
    public static function refusedWhateverItsValueHeaderProvider(): \Iterator
    {
        yield 'crit as a list' => ['crit', ['exp']];
        yield 'crit as an empty list' => ['crit', []];
        yield 'crit as null' => ['crit', null];
        yield 'crit as a string' => ['crit', 'exp'];
        yield 'b64 false' => ['b64', false];
        yield 'b64 true' => ['b64', true];
        yield 'b64 null' => ['b64', null];
    }


    /**
     * RFC 7515 section 4.1.11 and RFC 7797 section 6: what is refused is the parameter's presence, so a value a
     * truthiness check would wave through is refused all the same.
     */
    #[DataProvider('refusedWhateverItsValueHeaderProvider')]
    public function testRefusesAHeaderParameterWhateverItsValue(string $parameter, mixed $value): void
    {
        $this->sampleHeader[$parameter] = $value;

        $this->assertStringContainsString(
            $parameter === 'crit' ? 'marks header parameters as critical' : 'carries the b64 header parameter',
            $this->constructionFailure(),
        );
    }


    /**
     * @param array<string,mixed> $header
     */
    #[DataProvider('invalidHeaderProvider')]
    public function testRefusesAnInvalidHeader(array $header, string $message): void
    {
        $header = array_filter(
            array_merge($this->sampleHeader, $header),
            static fn(mixed $value): bool => !is_null($value),
        );

        $this->assertStringContainsString($message, $this->constructionFailure(null, $header));
    }


    /**
     * @return \Iterator<string, array{string}>
     */
    public static function optionalClaimProvider(): \Iterator
    {
        yield 'nbf' => ['nbf'];
        yield 'exp' => ['exp'];
        yield 'ath' => ['ath'];
        yield 'nonce' => ['nonce'];
    }


    public function testReportsEveryNullOptionalClaimAtOnce(): void
    {
        foreach (['nbf', 'exp', 'ath', 'nonce'] as $claim) {
            $this->samplePayload[$claim] = null;
        }

        $message = $this->constructionFailure();

        foreach (['nbf', 'exp', 'ath', 'nonce'] as $claim) {
            $this->assertStringContainsString(sprintf('Claim %s is present and null', $claim), $message);
        }
    }


    #[DataProvider('optionalClaimProvider')]
    public function testAnOptionalClaimIsOptionalByBeingAbsentNotNull(string $claim): void
    {
        $this->samplePayload[$claim] = null;

        $this->assertStringContainsString(
            sprintf('Claim %s is present and null', $claim),
            $this->constructionFailure(),
        );
    }


    /**
     * @return \Iterator<string, array{array<string,mixed>, string}>
     */
    public static function invalidPayloadProvider(): \Iterator
    {
        yield 'no JWT ID' => [['jti' => null], 'No JWT ID claim found.'];
        yield 'JWT ID that is not a string' => [['jti' => 12345], 'Value is not a string'];
        yield 'empty JWT ID' => [['jti' => ''], 'jti'];
        yield 'no HTTP method' => [['htm' => null], 'No HTTP Method claim found.'];
        yield 'HTTP method that is not a string' => [['htm' => ['POST']], 'Value is not a string'];
        yield 'empty HTTP method' => [['htm' => ''], 'htm'];
        yield 'no HTTP URI' => [['htu' => null], 'No HTTP URI claim found.'];
        yield 'HTTP URI that is not a string' => [['htu' => 42], 'Value is not a string'];
        yield 'relative HTTP URI' => [['htu' => '/token'], 'HTTP URI claim is not an http or https URI.'];
        yield 'HTTP URI of another scheme' => [
            ['htu' => 'wss://server.example.com/token'],
            'HTTP URI claim is not an http or https URI.',
        ];
        yield 'HTTP URI with userinfo' => [
            ['htu' => 'https://user@server.example.com/token'],
            'HTTP URI claim is not an http or https URI.',
        ];
        yield 'no Issued At' => [['iat' => null], 'No Issued At claim found.'];
        yield 'Issued At as a numeric string' => [['iat' => '1699999995'], 'Value is not a number'];
        yield 'Issued At beyond the NumericDate range' => [['iat' => 1e100], 'not a usable NumericDate'];
        yield 'Not Before as a numeric string' => [['nbf' => '1699999995'], 'Value is not a number'];
        yield 'Expiration Time as a numeric string' => [['exp' => '1700000030'], 'Value is not a number'];
        yield 'access token hash that is not a string' => [['ath' => 1], 'Value is not a string'];
        yield 'empty access token hash' => [['ath' => ''], 'ath'];
        yield 'nonce that is not a string' => [['nonce' => 1], 'Value is not a string'];
        yield 'empty nonce' => [['nonce' => ''], 'nonce'];
    }


    /**
     * @param array<string,mixed> $payload
     */
    #[DataProvider('invalidPayloadProvider')]
    public function testRefusesAnInvalidPayload(array $payload, string $message): void
    {
        $payload = array_filter(
            array_merge($this->samplePayload, $payload),
            static fn(mixed $value): bool => !is_null($value),
        );

        $this->assertStringContainsString($message, $this->constructionFailure($payload));
    }


    public function testAcceptsAnHttpUriWithAQueryAndAFragment(): void
    {
        $this->samplePayload['htu'] = 'https://server.example.com/token?a=b#c';

        $this->assertSame('https://server.example.com/token?a=b#c', $this->sut()->getHttpUri());
    }


    public function testReportsEveryFailureAtOnce(): void
    {
        $this->sampleHeader['typ'] = 'JWT';
        unset($this->samplePayload['htm']);
        $this->samplePayload['nbf'] = (int)self::NOW + 3600;
        $this->samplePayload['exp'] = (int)self::NOW - 3600;

        $message = $this->constructionFailure();

        $this->assertStringContainsString('Invalid Type header claim', $message);
        $this->assertStringContainsString('No HTTP Method claim found.', $message);
        $this->assertStringContainsString('Not Before claim', $message);
        $this->assertStringContainsString('Expiration Time claim', $message);
    }


    /**
     * @return \Iterator<string, array{int|float, int, bool}>
     */
    public static function issuedAtProvider(): \Iterator
    {
        // Whole seconds as integers: JSON writes a float without a fraction as one, and it is read back as such.
        $now = (int)self::NOW;

        yield 'now' => [$now, 60, true];
        yield "long ago, which is the caller's window to judge" => [$now - 86400, 60, true];
        yield 'with a fraction' => [self::NOW - 0.25, 60, true];
        yield 'as far ahead as the leeway allows' => [$now + 60, 60, true];
        yield 'half a second further, which a truncation would let through' => [self::NOW + 60.5, 60, false];
        yield 'a second further' => [$now + 61, 60, false];
        yield 'ahead, with no leeway' => [self::NOW + 0.5, 0, false];
        yield 'now, with no leeway' => [$now, 0, true];
    }


    #[DataProvider('issuedAtProvider')]
    public function testHoldsIssuedAtAgainstTheClockWithItsFraction(int|float $iat, int $leeway, bool $accepted): void
    {
        $this->samplePayload['iat'] = $iat;

        if (!$accepted) {
            $this->assertStringContainsString(
                'Issued At claim',
                $this->constructionFailure(null, null, $leeway),
            );

            return;
        }

        $sut = $this->sut(null, null, $leeway);

        $this->assertSame($iat, $sut->getIssuedAtNumericDate());
        $this->assertSame((int)$iat, $sut->getIssuedAt());
    }


    /**
     * @return \Iterator<string, array{int|float, bool}>
     */
    public static function notBeforeProvider(): \Iterator
    {
        yield 'in the past' => [self::NOW - 10, true];
        yield 'as far ahead as the leeway allows' => [self::NOW + 60, true];
        yield 'half a second further' => [self::NOW + 60.5, false];
    }


    #[DataProvider('notBeforeProvider')]
    public function testHoldsNotBeforeAgainstTheClockWithItsFraction(int|float $nbf, bool $accepted): void
    {
        $this->samplePayload['nbf'] = $nbf;

        if (!$accepted) {
            $this->assertStringContainsString('Not Before claim', $this->constructionFailure());

            return;
        }

        $this->assertSame((int)$nbf, $this->sut()->getNotBefore());
    }


    /**
     * @return \Iterator<string, array{int|float, bool}>
     */
    public static function expirationTimeProvider(): \Iterator
    {
        yield 'ahead' => [self::NOW + 30, true];
        yield 'passed by less than the leeway' => [self::NOW - 59.5, true];
        yield 'passed by exactly the leeway' => [self::NOW - 60, false];
        yield 'passed by the leeway and a fraction' => [self::NOW - 60.25, false];
    }


    #[DataProvider('expirationTimeProvider')]
    public function testHoldsExpirationTimeAgainstTheClockWithItsFraction(int|float $exp, bool $accepted): void
    {
        $this->samplePayload['exp'] = $exp;

        if (!$accepted) {
            $this->assertStringContainsString('Expiration Time claim', $this->constructionFailure());

            return;
        }

        $this->assertSame((int)$exp, $this->sut()->getExpirationTime());
    }


    public function testHoldsTheTimestampsAgainstTheRealClock(): void
    {
        $this->samplePayload['iat'] = time();

        $this->assertSame($this->samplePayload['iat'], $this->sut(null, null, 60, null)->getIssuedAt());
    }


    public function testRefusesAProofFromTheFutureByTheRealClock(): void
    {
        $this->samplePayload['iat'] = time() + 3600;

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Issued At claim');

        $this->sut(null, null, 60, null);
    }


    public function testCanComputeTheJwkThumbprint(): void
    {
        $this->assertSame(self::JWK_THUMBPRINT, $this->sut()->getJwkThumbprint());
    }


    public function testTheJwkThumbprintTakesOnlyTheRequiredMembers(): void
    {
        // RFC 7638 section 3.2.2: optional members are left out "so that their absence or presence in the JWK does
        // not alter the resulting value".
        $this->sampleHeader['jwk'] = ['kid' => 'key-1', 'use' => 'sig', 'alg' => 'ES256'] + self::JWK;

        $this->assertSame(self::JWK_THUMBPRINT, $this->sut()->getJwkThumbprint());
    }


    public function testCanComputeTheAccessTokenHash(): void
    {
        $this->assertSame(self::ACCESS_TOKEN_HASH, DpopProof::accessTokenHash(self::ACCESS_TOKEN));
    }


    public function testCanMatchTheAccessToken(): void
    {
        $this->samplePayload['ath'] = self::ACCESS_TOKEN_HASH;

        $sut = $this->sut();

        $this->assertTrue($sut->matchesAccessToken(self::ACCESS_TOKEN));
        $this->assertFalse($sut->matchesAccessToken(self::ACCESS_TOKEN . 'x'));
        $this->assertFalse($sut->matchesAccessToken(''));
    }


    public function testAProofWithoutAccessTokenHashMatchesNoAccessToken(): void
    {
        $this->assertFalse($this->sut()->matchesAccessToken(self::ACCESS_TOKEN));
    }


    public function testAnEmptyAccessTokenMatchesNoProofEvenOneMadeForIt(): void
    {
        // The hash of the empty string: a proof can carry it, and an empty token must still not match.
        $this->samplePayload['ath'] = DpopProof::accessTokenHash('');

        $this->assertFalse($this->sut()->matchesAccessToken(''));
    }


    /**
     * @return \Iterator<string, array{string, string, string, bool}>
     */
    public static function optionsRequestProvider(): \Iterator
    {
        yield 'OPTIONS, both without a path' => [
            'OPTIONS',
            'https://server.example.com',
            'https://server.example.com',
            true,
        ];
        yield 'OPTIONS, both at the root' => [
            'OPTIONS',
            'https://server.example.com/',
            'https://server.example.com/',
            true,
        ];
        yield 'OPTIONS, the server against its root' => [
            'OPTIONS',
            'https://server.example.com',
            'https://server.example.com/',
            false,
        ];
        yield 'OPTIONS, the root against the server' => [
            'OPTIONS',
            'https://server.example.com/',
            'https://server.example.com:443',
            false,
        ];
        yield 'GET, the server and its root are one' => [
            'GET',
            'https://server.example.com',
            'https://server.example.com/',
            true,
        ];
    }


    /**
     * RFC 9110 section 4.2.3: an empty path is "/" only "When not being used as the target of an OPTIONS
     * request".
     */
    #[DataProvider('optionsRequestProvider')]
    public function testAnEmptyPathIsTheRootExceptForOptions(
        string $method,
        string $htu,
        string $uri,
        bool $matches,
    ): void {
        $this->samplePayload['htm'] = $method;
        $this->samplePayload['htu'] = $htu;

        $this->assertSame($matches, $this->sut()->matchesHttpRequest($method, $uri));
    }


    /**
     * @return \Iterator<string, array{string, string, bool}>
     */
    public static function httpRequestProvider(): \Iterator
    {
        yield 'the same' => ['POST', 'https://server.example.com/token', true];
        yield 'another spelling of the same URI' => ['POST', 'HTTPS://Server.Example.COM:443/token', true];
        yield 'with a query and a fragment' => ['POST', 'https://server.example.com/token?a=b#c', true];
        yield 'with a dot segment' => ['POST', 'https://server.example.com/x/../token', true];
        yield 'with an encoded unreserved character' => ['POST', 'https://server.example.com/%74oken', true];
        yield 'method in lower case' => ['post', 'https://server.example.com/token', false];
        yield 'another method' => ['GET', 'https://server.example.com/token', false];
        yield 'another path' => ['POST', 'https://server.example.com/token2', false];
        yield 'path in another case' => ['POST', 'https://server.example.com/Token', false];
        yield 'with a trailing slash' => ['POST', 'https://server.example.com/token/', false];
        yield 'another host' => ['POST', 'https://other.example.com/token', false];
        yield 'another port' => ['POST', 'https://server.example.com:8443/token', false];
        yield 'another scheme' => ['POST', 'http://server.example.com/token', false];
        yield 'a relative reference' => ['POST', '/token', false];
        yield 'not a URI' => ['POST', 'https://server.example.com/to ken', false];
        yield 'a malformed percent-encoding' => ['POST', 'https://server.example.com/token%zz', false];
        yield 'a raw non-ASCII character' => ['POST', "https://server.example.com/token\u{e4}", false];
    }


    #[DataProvider('httpRequestProvider')]
    public function testCanMatchTheHttpRequest(string $method, string $uri, bool $matches): void
    {
        $this->assertSame($matches, $this->sut()->matchesHttpRequest($method, $uri));
    }


    public function testTheProofUriIsNormalizedToo(): void
    {
        $this->samplePayload['htu'] = 'https://SERVER.example.com:443/a/../token?x=y#z';

        $this->assertTrue($this->sut()->matchesHttpRequest('POST', 'https://server.example.com/token'));
    }


    public function testVerifiesTheSignatureWithTheEmbeddedKey(): void
    {
        $jwksDecorator = $this->createStub(JwksDecorator::class);

        $jwksDecoratorFactory = $this->createMock(JwksDecoratorFactory::class);
        $jwksDecoratorFactory->expects($this->once())
            ->method('fromKeySetData')
            ->with(['keys' => [self::JWK]])
            ->willReturn($jwksDecorator);

        $jwsVerifierDecorator = $this->createMock(JwsVerifierDecorator::class);
        $jwsVerifierDecorator->expects($this->once())
            ->method('verifyWithKeySet')
            ->with($this->jwsDecoratorMock, $jwksDecorator, 0)
            ->willReturn(true);

        $this->sut(null, null, 60, self::NOW, $jwsVerifierDecorator, $jwksDecoratorFactory)
            ->verifyWithEmbeddedKey();
    }


    public function testRefusesASignatureTheEmbeddedKeyDoesNotVerify(): void
    {
        $jwsVerifierDecorator = $this->createStub(JwsVerifierDecorator::class);
        $jwsVerifierDecorator->method('verifyWithKeySet')->willReturn(false);

        $sut = $this->sut(null, null, 60, self::NOW, $jwsVerifierDecorator);

        $this->expectException(DpopProofException::class);
        $this->expectExceptionMessage('Could not verify the DPoP proof signature with its own key.');

        $sut->verifyWithEmbeddedKey();
    }


    public function testAFailureToReadTheKeyIsAFailedVerification(): void
    {
        $jwksDecoratorFactory = $this->createStub(JwksDecoratorFactory::class);
        $jwksDecoratorFactory->method('fromKeySetData')->willThrowException(new RuntimeException('unusable key'));

        $sut = $this->sut(null, null, 60, self::NOW, null, $jwksDecoratorFactory);

        $this->expectException(DpopProofException::class);
        $this->expectExceptionMessage('Could not verify the DPoP proof signature with its own key.');

        $sut->verifyWithEmbeddedKey();
    }
}
