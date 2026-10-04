<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Jws;

use DateInterval;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\EntityStatementException;
use SimpleSAML\OpenID\Exceptions\InvalidValueException;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Exceptions\JwsParseException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(ParsedJws::class)]
#[UsesClass(SignatureAlgorithmEnum::class)]
#[UsesClass(DateIntervalDecorator::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Json::class)]
#[UsesClass(Helpers\Type::class)]
final class ParsedJwsTest extends TestCase
{
    /**
     * A whole second to fix the clock at, for the tests which hold timestamps against it.
     */
    protected const NOW = 1_700_000_000;


    protected MockObject $jwsDecoratorMock;

    protected MockObject $jwsVerifierDecoratorMock;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory
     */
    protected \PHPUnit\Framework\MockObject\Stub $jwksDecoratorFactoryMock;

    protected MockObject $jwsSerializerManagerDecoratorMock;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\SimpleSAML\OpenID\Decorators\DateIntervalDecorator
     */
    protected \PHPUnit\Framework\MockObject\Stub $timestampValidationLeewayMock;

    protected MockObject $helpersMock;

    protected MockObject $jwsMock;

    protected MockObject $signatureMock;

    protected MockObject $jsonHelperMock;

    protected MockObject $arrHelperMock;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\SimpleSAML\OpenID\Factories\ClaimFactory
     */
    protected \PHPUnit\Framework\MockObject\Stub $claimFactoryMock;

    protected array $sampleHeader = [
        'alg' => 'RS256',
        'typ' => 'entity-statement+jwt',
        'kid' => 'LfgZECDYkSTHmbllBD5_Tkwvy3CtOpNYQ7-DfQawTww',
    ];

    protected array $expiredPayload = [
        'iat' => 1731175727,
        'nbf' => 1731175727,
        'exp' => 1731175727,
        'iss' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/',
        'sub' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/',
        'jwks' => [
            'keys' => [
                [
                    'alg' => 'RS256',
                    'use' => 'sig',
                    'kty' => 'RSA',
                    // phpcs:ignore
                    'n' => 'pJgG9F_lwc2cFEC1l6q0fjJYxKPbtVGqJpDggDpDR8MgfbH0jUZP_RvhJGpl_09Bp-PfibLiwxchHZlrCx-fHQyGMaBRivUfq_p12ECEXMaFUcasCP6cyNrDfa5Uchumau4WeC21nYI1NMawiMiWFcHpLCQ7Ul8NMaCM_dkeruhm_xG0ZCqfwu30jOyCsnZdE0izJwPTfBRLpLyivu8eHpwjoIzmwqo8H-ZsbqR0vdRu20-MNS78ppTxwK3QmJhU6VO2r730F6WH9xJd_XUDuVeM4_6Z6WVDXw3kQF-jlpfcssPP303nbqVmfFZSUgS8buToErpMqevMIKREShsjMQ',
                    'e' => 'AQAB',
                    'kid' => 'F4VFObNusj3PHmrHxpqh4GNiuFHlfh-2s6xMJ95fLYA',
                ],
            ],
        ],
        'metadata' => [
            'federation_entity' => [
                'organization_name' => 'Org ALeaf',
            ],
            'openid_relying_party' => [
                'redirect_uris' => [
                    'https://74-dap.localhost.markoivancic.from.hr/oidc/oidc-php-app-demo/callback.php',
                ],
                'response_types' => [
                    'code',
                ],
                'scope' => 'openid profile',
                'token_endpoint_auth_method' => 'self_signed_tls_client_auth',
                'contacts' => [
                    'rp_admins@rp.example.org',
                ],
                'jwks_uri' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/jwks',
                'signed_jwks_uri' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ALeaf/signed-jwks',
            ],
        ],
        'authority_hints' => [
            'https://08-dap.localhost.markoivancic.from.hr/openid/entities/AIntermediate/',
        ],
        'trust_marks' => [
            [
                'id' => 'https://08-dap.localhost.markoivancic.from.hr/openid/entities/ABTrustAnchor/trust-mark/member',
                // phpcs:ignore
                'trust_mark' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6InRydXN0LW1hcmsrand0Iiwia2lkIjoiZnNRNDVGMEQ5MTZSZEtFZVRqdGE4RFlXaW9kanRob3VIclZXZ09YQnJrayJ9.eyJpYXQiOjE3MzQwMTcyMTcsIm5iZiI6MTczNDAxNzIxNywiZXhwIjoxNzM0MDIwODE3LCJpZCI6Imh0dHBzOlwvXC8wOC1kYXAubG9jYWxob3N0Lm1hcmtvaXZhbmNpYy5mcm9tLmhyXC9vcGVuaWRcL2VudGl0aWVzXC9BQlRydXN0QW5jaG9yXC90cnVzdC1tYXJrXC9tZW1iZXIiLCJpc3MiOiJodHRwczpcL1wvMDgtZGFwLmxvY2FsaG9zdC5tYXJrb2l2YW5jaWMuZnJvbS5oclwvb3BlbmlkXC9lbnRpdGllc1wvQUJUcnVzdEFuY2hvclwvIiwic3ViIjoiaHR0cHM6XC9cLzA4LWRhcC5sb2NhbGhvc3QubWFya29pdmFuY2ljLmZyb20uaHJcL29wZW5pZFwvZW50aXRpZXNcL0FMZWFmXC8ifQ.hbpq2-oPbn56WwDGLLcYaM7t8wZbipa_0FMlFT7nmRi6OZRibid5TGIBYs3Zk9nmNVZhzOCYO3inOIws6yJhpg6ogD32KpXet4oz8xeYftyw-xddb_sMf3gBPK5GChnqNsj71QJHZDYIUL3nILTySpnR2u7UK6gtmoosjxNINawM-teg0tIsOGaHuqDlAu9wSBI3PFxvXJJvi4mmMF3TosudexrpIHIBnNY_bvaSKJdzlmuSssWVAmIKp7O1IZLhn6eOzrhuktlGH5iltd77CnFxhdMyFjZrUOcT2MXIhZqWqpy-Uj-H2Bia63CwvmZ5DQa-WVYUSbxCEqJeeRqI0Q',
            ],
        ],
    ];

    protected array $validPayload;


    protected function setUp(): void
    {
        $this->jwsDecoratorMock = $this->createMock(JwsDecorator::class);
        $this->jwsVerifierDecoratorMock = $this->createMock(JwsVerifierDecorator::class);
        $this->jwksDecoratorFactoryMock = $this->createStub(JwksDecoratorFactory::class);
        $this->jwsSerializerManagerDecoratorMock = $this->createMock(JwsSerializerManagerDecorator::class);
        $this->timestampValidationLeewayMock = $this->createStub(DateIntervalDecorator::class);
        $this->helpersMock = $this->createMock(Helpers::class);

        $this->jwsMock = $this->createMock(JWS::class);
        $this->jwsDecoratorMock->method('jws')->willReturn($this->jwsMock);

        $this->signatureMock = $this->createMock(Signature::class);
        $this->jwsMock->method('getSignature')->willReturn($this->signatureMock);

        $this->jsonHelperMock = $this->createMock(Helpers\Json::class);
        $this->helpersMock->method('json')->willReturn($this->jsonHelperMock);
        $typeHelperMock = $this->createMock(Helpers\Type::class);
        $this->helpersMock->method('type')->willReturn($typeHelperMock);
        $this->arrHelperMock = $this->createMock(Helpers\Arr::class);
        $this->helpersMock->method('arr')->willReturn($this->arrHelperMock);

        $typeHelperMock->method('ensureNonEmptyString')->willReturnArgument(0);
        $typeHelperMock->method('enforceNonEmptyString')->willReturnArgument(0);
        $typeHelperMock->method('ensureArrayWithValuesAsStrings')->willReturnArgument(0);
        $typeHelperMock->method('ensureInt')->willReturnArgument(0);
        $typeHelperMock->method('enforceNumericDate')->willReturnArgument(0);

        $this->claimFactoryMock = $this->createStub(ClaimFactory::class);

        $this->validPayload = $this->expiredPayload;
        $this->validPayload['exp'] = time() + 3600;
    }


    protected function sut(
        ?JwsDecorator $jwsDecorator = null,
        ?JwsVerifierDecorator $jwsVerifierDecorator = null,
        ?JwksDecoratorFactory $jwksDecoratorFactory = null,
        ?JwsSerializerManagerDecorator $jwsSerializerManagerDecorator = null,
        ?DateIntervalDecorator $timestampValidationLeewayMock = null,
        ?Helpers $helpers = null,
        ?ClaimFactory $claimFactory = null,
    ): ParsedJws {
        $jwsDecorator ??= $this->jwsDecoratorMock;
        $jwsVerifierDecorator ??= $this->jwsVerifierDecoratorMock;
        $jwksDecoratorFactory ??= $this->jwksDecoratorFactoryMock;
        $jwsSerializerManagerDecorator ??= $this->jwsSerializerManagerDecoratorMock;
        $timestampValidationLeewayMock ??= $this->timestampValidationLeewayMock;
        $helpers ??= $this->helpersMock;
        $claimFactory ??= $this->claimFactoryMock;

        return new ParsedJws(
            $jwsDecorator,
            $jwsVerifierDecorator,
            $jwksDecoratorFactory,
            $jwsSerializerManagerDecorator,
            $timestampValidationLeewayMock,
            $helpers,
            $claimFactory,
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(ParsedJws::class, $this->sut());
    }


    public function testCanValidateByCallbacks(): void
    {
        $sut = new class (
            $this->jwsDecoratorMock,
            $this->jwsVerifierDecoratorMock,
            $this->jwksDecoratorFactoryMock,
            $this->jwsSerializerManagerDecoratorMock,
            $this->timestampValidationLeewayMock,
            $this->helpersMock,
            $this->claimFactoryMock,
        ) extends ParsedJws {
            protected function validate(): void
            {
                $this->validateByCallbacks($this->simulateOk(...));
            }


            protected function simulateOk(): void
            {
            }
        };

        $this->assertInstanceOf(ParsedJws::class, $sut);
    }


    public function testThrowsOnValidateByCallbacksError(): void
    {
        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('not valid');

        new class (
            $this->jwsDecoratorMock,
            $this->jwsVerifierDecoratorMock,
            $this->jwksDecoratorFactoryMock,
            $this->jwsSerializerManagerDecoratorMock,
            $this->timestampValidationLeewayMock,
            $this->helpersMock,
            $this->claimFactoryMock,
        ) extends ParsedJws {
            protected function validate(): void
            {
                $this->validateByCallbacks($this->simulateError(...));
            }


            protected function simulateError(): never
            {
                throw new \Exception('Error');
            }
        };
    }


    public function testCanGetHeader(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $this->assertSame($this->sampleHeader, $this->sut()->getHeader());
    }


    public function testCanGetHeaderClaims(): void
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $this->assertSame($this->sampleHeader['kid'], $this->sut()->getHeaderClaim('kid'));
        $this->assertSame($this->sampleHeader['kid'], $this->sut()->getKeyId());
        $this->assertSame($this->sampleHeader['typ'], $this->sut()->getType());
        $this->assertSame($this->sampleHeader['alg'], $this->sut()->getAlgorithm());
    }


    public function testThrowsOnGetHeaderError(): void
    {
        $this->jwsMock->method('getSignature')->willThrowException(new \Exception('Error'));

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('header');

        $this->sut()->getHeader();
    }


    public function testCanGetPayload(): void
    {
        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('payload-json');
        $this->jsonHelperMock->expects($this->once())->method('decode')->willReturn($this->validPayload);

        $sut = $this->sut();

        $this->assertSame($this->validPayload, $sut->getPayload());
        // Second call so that we verify that decoding happens only once.
        $this->assertSame($this->validPayload, $sut->getPayload());
    }


    public function testCanGetEmptyPayload(): void
    {
        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('');
        $this->jsonHelperMock->expects($this->never())->method('decode');

        $this->sut()->getPayload();
    }


    public function testThrowsOnPayloadDecodingError(): void
    {
        $this->jwsMock->expects($this->atLeastOnce())->method('getPayload')->willReturn('payload-json');
        $this->jsonHelperMock->expects($this->atLeastOnce())->method('decode')
            ->willThrowException(new \JsonException('Error'));

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('decode');

        $this->sut()->getPayload();
    }


    public function testCanGetPayloadClaims(): void
    {
        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('payload-json');
        $this->jsonHelperMock->expects($this->once())->method('decode')->willReturn($this->validPayload);

        $sut = $this->sut();

        $this->assertSame($this->validPayload['iss'], $sut->getPayloadClaim('iss'));
        $this->assertSame($this->validPayload['iss'], $sut->getIssuer());
        $this->assertSame($this->validPayload['sub'], $sut->getSubject());
        $this->assertSame($this->validPayload['exp'], $sut->getExpirationTime());
        $this->assertSame($this->validPayload['iat'], $sut->getIssuedAt());
        $this->assertSame($this->validPayload['nbf'], $sut->getNotBefore());
    }


    public function testCanGetEmptyPayloadClaims(): void
    {

        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('payload-json');
        $this->jsonHelperMock->expects($this->once())->method('decode')->willReturn([]);

        $sut = $this->sut();
        $this->assertNull($sut->getAudience());
        $this->assertNull($sut->getJwtId());
        $this->assertNull($sut->getExpirationTime());
        $this->assertNull($sut->getIssuedAt());
        $this->assertNull($sut->getIdentifier());
        $this->assertNull($sut->getIssuer());
        $this->assertNull($sut->getNotBefore());
    }


    public function testCanGetNestedPayloadClaims(): void
    {
        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('payload-json');
        $this->jsonHelperMock->expects($this->once())->method('decode')->willReturn($this->validPayload);

        $this->arrHelperMock->expects($this->once())->method('getNestedValue');

        $this->sut()->getNestedPayloadClaim('metadata');
    }


    public function testCanGetAudienceArrayFromString(): void
    {
        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('payload-json');
        $this->jsonHelperMock->expects($this->once())->method('decode')
            ->willReturn(['aud' => 'sample']);

        $this->assertSame(['sample'], $this->sut()->getAudience());
    }


    public function testCanGetAudienceArrayFromArray(): void
    {
        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('payload-json');
        $this->jsonHelperMock->expects($this->once())->method('decode')
            ->willReturn(['aud' => ['sample']]);

        $this->assertSame(['sample'], $this->sut()->getAudience());
    }


    public function testThrowsOnInvalidAudienceValue(): void
    {
        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('payload-json');
        $this->jsonHelperMock->expects($this->once())->method('decode')
            ->willReturn(['aud' => 123]);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('audience');

        $this->sut()->getAudience();
    }


    public function testCanSerializeToToken(): void
    {
        $this->jwsSerializerManagerDecoratorMock->expects($this->once())->method('serialize')
            ->willReturn('token');

        $sut = $this->sut();

        $this->assertSame('token', $sut->getToken());
        // Ensure that serialization happens only once.
        $this->assertSame('token', $sut->getToken());
    }


    public function testCanVerifyWithKeySet(): void
    {
        $this->jwsVerifierDecoratorMock->expects($this->once())->method('verifyWithKeySet')
            ->willReturn(true);

        $this->sut()->verifyWithKeySet(['jwks']);
    }


    public function testCanVerifyWithKey(): void
    {
        $this->jwsVerifierDecoratorMock->expects($this->once())->method('verifyWithKeySet')
            ->willReturn(true);

        $this->sut()->verifyWithKey(['key']);
    }


    public function testThrowsOnVerifyWithKeySetError(): void
    {
        $this->jwsVerifierDecoratorMock->expects($this->once())->method('verifyWithKeySet')
            ->willReturn(false);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('signature');

        $this->sut()->verifyWithKeySet(['jwks']);
    }


    public function testThrowsIfExpired(): void
    {
        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('payload-json');
        $this->jsonHelperMock->expects($this->once())->method('decode')->willReturn($this->expiredPayload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Expiration');

        $this->sut()->getExpirationTime();
    }


    /**
     * A token which parsed and then fails a check is not reported as one which could not be parsed, so a caller
     * can tell the two apart.
     */
    public function testAFailedCheckIsNotAParseFailure(): void
    {
        $this->jwsMock->method('getPayload')->willReturn('payload-json');
        $this->jsonHelperMock->method('decode')->willReturn($this->expiredPayload);

        try {
            $this->sut();
            $this->fail('An expired token was accepted.');
        } catch (JwsException $jwsException) {
            $this->assertNotInstanceOf(JwsParseException::class, $jwsException);
        }
    }


    /**
     * RFC 7519 section 4.1.4 has the current time strictly before the expiration time, so the second the
     * deadline (plus leeway, zero here) is reached the token is expired.
     */
    public function testThrowsWhenTheExpirationTimeIsReached(): void
    {
        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('payload-json');
        $this->jsonHelperMock->expects($this->once())->method('decode')->willReturn(['exp' => time()]);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Expiration');

        $this->sut()->getExpirationTime();
    }


    public function testThrowsIfIssuedAtInTheFuture(): void
    {
        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('payload-json');
        $payload = $this->validPayload;
        $payload['iat'] = time() + 60;
        $this->jsonHelperMock->expects($this->once())->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Issued At');

        $this->sut()->getIssuedAt();
    }


    public function testThrowsIfNotBeforeInTheFuture(): void
    {
        $this->jwsMock->expects($this->once())->method('getPayload')->willReturn('payload-json');
        $payload = $this->validPayload;
        $payload['nbf'] = time() + 60;
        $this->jsonHelperMock->expects($this->once())->method('decode')->willReturn($payload);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Not Before');

        $this->sut()->getNotBefore();
    }


    /**
     * @return \Iterator<string, array{string, string, string}>
     */
    public static function valueWhichIsNotANumericDateProvider(): \Iterator
    {
        $values = [
            'a numeric string' => ['"1700000000"', 'Value is not a number'],
            'a numeric string with a fraction' => ['"1700000000.5"', 'Value is not a number'],
            'a boolean' => ['true', 'Value is not a number'],
            'a list' => ['[1700000000]', 'Value is not a number'],
            // A number too large for a double is read as infinity.
            'infinite' => ['1e400', 'Value is not a number'],
            // Beyond 2 ** 53 either way, the bound Type::enforceNumericDate() sets.
            'an integer beyond the representable range' => ['99999999999999999999', 'not a usable NumericDate'],
            'beyond the representable range' => ['1e100', 'not a usable NumericDate'],
            'negative beyond the representable range' => ['-1e100', 'not a usable NumericDate'],
        ];

        foreach (['exp', 'nbf', 'iat'] as $claimKey) {
            foreach ($values as $label => [$json, $message]) {
                yield sprintf('%s, %s', $claimKey, $label) => [$claimKey, $json, $message];
            }
        }
    }


    /**
     * RFC 7519 sections 4.1.4 to 4.1.6: "Its value MUST be a number containing a NumericDate value." A numeric
     * string is refused rather than cast into one, and so is a number beyond 2 ** 53 either way.
     */
    #[DataProvider('valueWhichIsNotANumericDateProvider')]
    public function testTimestampsRefuseAValueWhichIsNotANumericDate(
        string $claimKey,
        string $json,
        string $message,
    ): void {
        $this->expectException(JwsException::class);
        $this->expectExceptionMessageMatches(
            sprintf('/%s[^;]* Context: %s /', preg_quote($message, '/'), $claimKey),
        );

        $this->sutAt(self::NOW, sprintf('{"%s":%s}', $claimKey, $json));
    }


    /**
     * @return \Iterator<string, array{float, int, int|float, bool}>
     */
    public static function expirationTimeProvider(): \Iterator
    {
        yield 'ahead' => [self::NOW, 0, self::NOW + 30, true];
        yield 'reached' => [self::NOW, 0, self::NOW, false];
        yield 'half a second ahead, which a truncation would have reached' => [self::NOW, 0, self::NOW + 0.5, true];
        yield "passed within the clock's second" => [self::NOW + 0.75, 0, self::NOW + 0.5, false];
        yield 'passed by less than the leeway' => [self::NOW, 60, self::NOW - 59.5, true];
        yield 'passed by exactly the leeway' => [self::NOW, 60, self::NOW - 60, false];
        yield 'passed by the leeway and a fraction' => [self::NOW, 60, self::NOW - 60.25, false];
    }


    /**
     * The claim and the clock are compared with their fractions, and RFC 7519 section 4.1.4 has the token expire
     * the moment its "exp" is reached, leeway included.
     */
    #[DataProvider('expirationTimeProvider')]
    public function testHoldsTheExpirationTimeAgainstTheClockWithItsFraction(
        float $now,
        int $leewaySeconds,
        int|float $exp,
        bool $accepted,
    ): void {
        if (!$accepted) {
            $this->expectException(JwsException::class);
            $this->expectExceptionMessage('Expiration Time claim');
        }

        $sut = $this->sutAt($now, json_encode(['exp' => $exp], JSON_THROW_ON_ERROR), $leewaySeconds);

        $this->assertSame($exp, $sut->getExpirationTimeNumericDate());
        $this->assertSame((int)$exp, $sut->getExpirationTime());
    }


    /**
     * @return \Iterator<string, array{string, float, int, int|float, bool}>
     */
    public static function notAheadProvider(): \Iterator
    {
        $cases = [
            'in the past' => [self::NOW, 0, self::NOW - 10, true],
            'now' => [self::NOW, 0, self::NOW, true],
            'half a second ahead, which a truncation would let through' => [self::NOW, 0, self::NOW + 0.5, false],
            "ahead by less than the clock's fraction" => [self::NOW + 0.75, 0, self::NOW + 0.5, true],
            'as far ahead as the leeway allows' => [self::NOW, 60, self::NOW + 60, true],
            'half a second further' => [self::NOW, 60, self::NOW + 60.5, false],
            'a second further' => [self::NOW, 60, self::NOW + 61, false],
        ];

        foreach (['nbf', 'iat'] as $claimKey) {
            foreach ($cases as $label => $case) {
                yield sprintf('%s, %s', $claimKey, $label) => [$claimKey, ...$case];
            }
        }
    }


    /**
     * Neither "nbf" nor "iat" may lie further ahead than the leeway, compared with the fractions of the claim and
     * of the clock: truncated, "now + leeway + 0.5" would pass.
     */
    #[DataProvider('notAheadProvider')]
    public function testHoldsNotBeforeAndIssuedAtAgainstTheClockWithTheirFractions(
        string $claimKey,
        float $now,
        int $leewaySeconds,
        int|float $value,
        bool $accepted,
    ): void {
        [$numericDateGetter, $getter, $message] = match ($claimKey) {
            'nbf' => ['getNotBeforeNumericDate', 'getNotBefore', 'Not Before claim'],
            default => ['getIssuedAtNumericDate', 'getIssuedAt', 'Issued At claim'],
        };

        if (!$accepted) {
            $this->expectException(JwsException::class);
            $this->expectExceptionMessage($message);
        }

        $sut = $this->sutAt($now, json_encode([$claimKey => $value], JSON_THROW_ON_ERROR), $leewaySeconds);

        $this->assertSame($value, $sut->$numericDateGetter());
        $this->assertSame((int)$value, $sut->$getter());
    }


    /**
     * The clock keeps its fraction: an "exp" read off it a moment earlier has passed, where a whole-second clock
     * would hold it until its second ends.
     */
    public function testTheClockKeepsItsFraction(): void
    {
        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Expiration Time claim');

        $this->sutWithRealHelpers($this->sampleHeader, ['exp' => microtime(true)]);
    }


    /**
     * A token type used after its expiry (shouldValidateExpirationTime()) skips the clock, and only the clock.
     */
    public function testAnExpirationTimeNotHeldAgainstTheClockIsReturned(): void
    {
        $sut = $this->sutAt(self::NOW, '{"exp":1600000000.5}', 0, false);

        $this->assertEqualsWithDelta(1600000000.5, $sut->getExpirationTimeNumericDate(), PHP_FLOAT_EPSILON);
        $this->assertSame(1600000000, $sut->getExpirationTime());
    }


    public function testAnExpirationTimeNotHeldAgainstTheClockIsStillANumericDate(): void
    {
        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Value is not a number');

        $this->sutAt(self::NOW, '{"exp":"1600000000"}', 0, false);
    }


    /**
     * The base class reads a timestamp that is present and null as one that is absent. A token type which may not
     * carry a null says so with enforceNoNullOptionalClaims().
     */
    public function testReadsATimestampThatIsNullAsAbsent(): void
    {
        $sut = $this->sutAt(self::NOW, '{"exp":null,"nbf":null,"iat":null}');

        $this->assertNull($sut->getExpirationTimeNumericDate());
        $this->assertNull($sut->getNotBeforeNumericDate());
        $this->assertNull($sut->getIssuedAtNumericDate());
    }


    public function testAlgHeaderCanBeNull(): void
    {
        unset($this->sampleHeader['alg']);
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $this->assertNull($this->sut()->getAlgorithm());
    }


    public function testAlgHeaderCanNotBeNone(): void
    {
        $this->sampleHeader['alg'] = 'none';
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('none');

        $this->sut()->getAlgorithm();
    }


    public function testUnknownAlgorithmIsAJwsException(): void
    {
        $this->sampleHeader['alg'] = 'HS1024';
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        try {
            $this->sut()->getAlgorithm();
            $this->fail('An unknown algorithm was accepted.');
        } catch (JwsException $jwsException) {
            $this->assertNotInstanceOf(EntityStatementException::class, $jwsException);
            $this->assertStringContainsString('Invalid Algorithm', $jwsException->getMessage());
        }
    }


    public function testStringGettersReturnTheStringsAsCarried(): void
    {
        $payload = $this->validPayload;
        $payload['jti'] = 'jti-1';
        $payload['id'] = 'id-1';

        $sut = $this->sutWithRealHelpers($this->sampleHeader, $payload);

        $this->assertSame($payload['iss'], $sut->getIssuer());
        $this->assertSame($payload['sub'], $sut->getSubject());
        $this->assertSame('jti-1', $sut->getJwtId());
        $this->assertSame('id-1', $sut->getIdentifier());
        $this->assertSame($this->sampleHeader['kid'], $sut->getKeyId());
        $this->assertSame($this->sampleHeader['typ'], $sut->getType());
        $this->assertSame($this->sampleHeader['alg'], $sut->getAlgorithm());
    }


    /**
     * @return iterable<string, array{string, bool, string, mixed}>
     */
    public static function valueWhichIsNotAStringProvider(): iterable
    {
        $getters = [
            'getIssuer' => [false, 'iss'],
            'getSubject' => [false, 'sub'],
            'getJwtId' => [false, 'jti'],
            'getIdentifier' => [false, 'id'],
            'getKeyId' => [true, 'kid'],
            'getType' => [true, 'typ'],
            'getAlgorithm' => [true, 'alg'],
        ];
        $values = ['an integer' => 42, 'a float' => 4.2, 'true' => true, 'a list' => ['RS256']];

        foreach ($getters as $getter => [$inHeader, $claimKey]) {
            foreach ($values as $label => $value) {
                yield sprintf('%s, %s', $getter, $label) => [$getter, $inHeader, $claimKey, $value];
            }
        }
    }


    /**
     * RFC 7519 section 2 has a StringOrURI be "A JSON string value", and RFC 7515 sections 4.1.4 and 4.1.9 have
     * "kid" and "typ" be strings. A number or a boolean is refused rather than cast into one.
     */
    #[DataProvider('valueWhichIsNotAStringProvider')]
    public function testStringGettersRefuseAValueWhichIsNotAString(
        string $getter,
        bool $inHeader,
        string $claimKey,
        mixed $value,
    ): void {
        $header = $this->sampleHeader;
        $payload = $this->validPayload;

        if ($inHeader) {
            $header[$claimKey] = $value;
        } else {
            $payload[$claimKey] = $value;
        }

        $sut = $this->sutWithRealHelpers($header, $payload);

        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Context: ' . $claimKey);

        $sut->$getter();
    }


    /**
     * @param array<string,mixed> $header
     * @param array<string,mixed> $payload
     */
    protected function sutWithRealHelpers(array $header, array $payload): ParsedJws
    {
        $this->signatureMock->method('getProtectedHeader')->willReturn($header);
        $this->jwsMock->method('getPayload')->willReturn(json_encode($payload, JSON_THROW_ON_ERROR));

        return $this->sut(helpers: new Helpers());
    }


    /**
     * A token with real helpers, read from the given payload JSON and held against a clock fixed at $now.
     */
    protected function sutAt(
        float $now,
        string $payloadJson,
        int $leewaySeconds = 0,
        bool $validateExpirationTime = true,
    ): ParsedJws {
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);
        $this->jwsMock->method('getPayload')->willReturn($payloadJson);

        return new class (
            $now,
            $validateExpirationTime,
            $this->jwsDecoratorMock,
            $this->jwsVerifierDecoratorMock,
            $this->jwksDecoratorFactoryMock,
            $this->jwsSerializerManagerDecoratorMock,
            new DateIntervalDecorator(new DateInterval(sprintf('PT%dS', $leewaySeconds))),
            new Helpers(),
            $this->claimFactoryMock,
        ) extends ParsedJws {
            public function __construct(
                private readonly float $fixedNow,
                private readonly bool $validateExpirationTime,
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


            protected function shouldValidateExpirationTime(): bool
            {
                return $this->validateExpirationTime;
            }


            protected function currentTime(): float
            {
                return $this->fixedNow;
            }
        };
    }
}
