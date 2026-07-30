<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\TokenStatusList;

use Jose\Component\Signature\JWS;
use Jose\Component\Signature\Signature;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Codebooks\StatusTypeEnum;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Exceptions\StatusListException;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\TokenStatusList\Factories\StatusListFactory;
use SimpleSAML\OpenID\TokenStatusList\StatusList;
use SimpleSAML\OpenID\TokenStatusList\StatusListToken;

#[CoversClass(StatusListToken::class)]
#[UsesClass(StatusList::class)]
#[UsesClass(StatusListFactory::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Base64Url::class)]
#[UsesClass(Helpers\Json::class)]
#[UsesClass(Helpers\Type::class)]
#[UsesClass(SignatureAlgorithmEnum::class)]
final class StatusListTokenTest extends TestCase
{
    /** The example Status List Token from the specification, with the 1-bit example list. */
    protected const EXAMPLE_LST = 'eNrbuRgAAhcBXQ';


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

        // Real helpers, so the type strictness the specification calls for is exercised rather than stubbed away.
        $this->helpers = new Helpers();

        $this->sampleHeader = [
            'alg' => 'ES256',
            'kid' => '12',
            'typ' => 'statuslist+jwt',
        ];

        $this->samplePayload = [
            'exp' => time() + 3600,
            'iat' => time() - 60,
            'status_list' => [
                'bits' => 1,
                'lst' => self::EXAMPLE_LST,
            ],
            'sub' => 'https://example.com/statuslists/1',
            'ttl' => 43200,
        ];
    }


    /**
     * @param ?array<string,mixed> $payload
     * @param ?array<string,mixed> $header
     */
    protected function sut(
        ?array $payload = null,
        ?array $header = null,
    ): StatusListToken {
        $payload ??= $this->samplePayload;
        $header ??= $this->sampleHeader;

        $this->jwsMock->method('getPayload')->willReturn(json_encode($payload));
        $this->signatureMock->method('getProtectedHeader')->willReturn($header);

        return new StatusListToken(
            $this->jwsDecoratorMock,
            $this->createStub(\SimpleSAML\OpenID\Jws\JwsVerifierDecorator::class),
            $this->createStub(\SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory::class),
            $this->createStub(\SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator::class),
            $this->createStub(\SimpleSAML\OpenID\Decorators\DateIntervalDecorator::class),
            $this->helpers,
            $this->createStub(\SimpleSAML\OpenID\Factories\ClaimFactory::class),
            new StatusListFactory($this->helpers),
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(StatusListToken::class, $this->sut());
    }


    public function testCanGetClaims(): void
    {
        $sut = $this->sut();

        $this->assertSame('statuslist+jwt', $sut->getType());
        $this->assertSame('ES256', $sut->getAlgorithm());
        $this->assertSame('12', $sut->getKeyId());
        $this->assertSame('https://example.com/statuslists/1', $sut->getSubject());
        $this->assertSame($this->samplePayload['iat'], $sut->getIssuedAt());
        $this->assertSame($this->samplePayload['exp'], $sut->getExpirationTime());
        $this->assertSame(43200, $sut->getTimeToLive());
        $this->assertSame(1, $sut->getBits());
        $this->assertSame(self::EXAMPLE_LST, $sut->getEncodedStatusList());
        $this->assertSame(
            ['bits' => 1, 'lst' => self::EXAMPLE_LST],
            $sut->getStatusListClaimData(),
        );
    }


    public function testCanGetStatusList(): void
    {
        $statusList = $this->sut()->getStatusList(1024);

        $this->assertSame(1, $statusList->getBits());
        $this->assertSame(16, $statusList->getCapacity());
        $this->assertSame(StatusTypeEnum::Invalid, $statusList->getStatusType(0));
        $this->assertSame(StatusTypeEnum::Valid, $statusList->getStatusType(1));
    }


    /**
     * The size bound is the caller's, and it is enforced when the list is decoded.
     */
    public function testGetStatusListHonoursTheSizeBound(): void
    {
        $this->expectException(StatusListException::class);

        $this->sut()->getStatusList(1);
    }


    /**
     * Both claims are only RECOMMENDED, so a token without them is valid.
     */
    public function testAcceptsAbsentExpirationTimeAndTimeToLive(): void
    {
        $payload = $this->samplePayload;
        unset($payload['exp'], $payload['ttl']);

        $sut = $this->sut($payload);

        $this->assertNull($sut->getExpirationTime());
        $this->assertNull($sut->getTimeToLive());
    }


    /**
     * The specification requires a positive number, not a whole one.
     */
    public function testAcceptsNonIntegerTimeToLive(): void
    {
        $payload = $this->samplePayload;
        $payload['ttl'] = 0.5;

        $this->assertEqualsWithDelta(0.5, $this->sut($payload)->getTimeToLive(), PHP_FLOAT_EPSILON);
    }


    /**
     * A whole-second timestamp expressed as a JSON float is still a NumericDate.
     */
    public function testAcceptsNumericDateAsFloat(): void
    {
        $payload = $this->samplePayload;
        $payload['iat'] = (float)(time() - 60);

        $this->assertSame(time() - 60, $this->sut($payload)->getIssuedAt());
    }


    /**
     * RFC 7519 lets a NumericDate carry a fraction of a second, so one is accepted rather than rejected -- but
     * the getters return whole seconds, so it is truncated towards zero on the way out. That keeps the subsecond
     * part out of every decision, bounded under a second against a validation leeway of a minute by default, and
     * makes `exp` expire a token marginally sooner rather than later. The claim exactly as signed remains
     * readable through getPayloadClaim().
     */
    public function testTruncatesAFractionalNumericDateToWholeSeconds(): void
    {
        $issuedAt = time() - 60;

        $payload = $this->samplePayload;
        $payload['iat'] = $issuedAt + 0.9;

        $sut = $this->sut($payload);

        $this->assertSame($issuedAt, $sut->getIssuedAt());
        $this->assertSame($issuedAt + 0.9, $sut->getPayloadClaim('iat'));
    }


    public function testAcceptsAdditionalClaims(): void
    {
        $payload = $this->samplePayload;
        $payload['iss'] = 'https://example.com';

        $this->assertSame('https://example.com', $this->sut($payload)->getIssuer());
    }


    /**
     * `crit` means the producer requires the listed parameters to be understood, and this token type defines no
     * extensions for it to name, so the token is rejected rather than processed under rules never applied.
     */
    public function testRejectsCriticalHeaderParametersByName(): void
    {
        $header = $this->sampleHeader;
        $header['crit'] = ['http://example.com/UNDEFINED'];

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage("critical, and none are supported: 'http://example.com/UNDEFINED'");

        $this->sut(null, $header);
    }


    /**
     * @return \Iterator<string, array{array<string, mixed>}>
     */
    public static function invalidPayloadProvider(): \Iterator
    {
        $statusList = ['bits' => 1, 'lst' => self::EXAMPLE_LST];
        $base = [
            'iat' => 1686920170,
            'status_list' => $statusList,
            'sub' => 'https://example.com/statuslists/1',
        ];
        yield 'missing sub' => [array_diff_key($base, ['sub' => null])];
        yield 'non-URI sub' => [['sub' => 'statuslists/1'] + $base];
        yield 'missing iat' => [array_diff_key($base, ['iat' => null])];
        yield 'iat as a numeric string' => [['iat' => '1686920170'] + $base];
        yield 'iat as a boolean' => [['iat' => true] + $base];
        yield 'exp as a numeric string' => [['exp' => '2291720170'] + $base];
        yield 'missing status_list' => [array_diff_key($base, ['status_list' => null])];
        yield 'status_list not an object' => [['status_list' => 'nope'] + $base];
        yield 'missing bits' => [['status_list' => ['lst' => self::EXAMPLE_LST]] + $base];
        yield 'bits as a numeric string' => [
            ['status_list' => ['bits' => '1', 'lst' => self::EXAMPLE_LST]] + $base,
        ];
        yield 'unsupported bits' => [
            ['status_list' => ['bits' => 3, 'lst' => self::EXAMPLE_LST]] + $base,
        ];
        yield 'missing lst' => [['status_list' => ['bits' => 1]] + $base];
        yield 'zero ttl' => [['ttl' => 0] + $base];
        yield 'negative ttl' => [['ttl' => -1] + $base];
        yield 'ttl as a numeric string' => [['ttl' => '43200'] + $base];
        yield 'expired' => [['exp' => time() - 3600] + $base];
        // Casting a float this large to an integer yields 0 rather than saturating, so an unchecked value would
        // read as the epoch and pass for a token issued long ago.
        yield 'iat beyond the representable range' => [['iat' => 1e100] + $base];
        yield 'negative iat beyond the representable range' => [['iat' => -1e100] + $base];
        yield 'exp beyond the representable range' => [['exp' => 1e100] + $base];
        // Claims the specification does not ask a Status List Token to carry, but which have a defined shape
        // once present. A token that is not a valid JWT is not one to read a status out of. Note that the
        // library casts scalars to strings throughout, so `iss: 1` is a "1" rather than a rejected token.
        yield 'iss as an array' => [['iss' => []] + $base];
        yield 'aud as a number' => [['aud' => 1] + $base];
        yield 'jti as an array' => [['jti' => ['a']] + $base];
        // Optional by being absent, not by being present and null: the inherited getters read a claim with `??`
        // and so cannot tell the two apart on their own.
        yield 'iss that is null' => [['iss' => null] + $base];
        yield 'aud that is null' => [['aud' => null] + $base];
        yield 'jti that is null' => [['jti' => null] + $base];
    }


    /**
     * @param array<string,mixed> $payload
     */
    #[DataProvider('invalidPayloadProvider')]
    public function testThrowsForInvalidPayload(array $payload): void
    {
        $this->expectException(JwsException::class);

        $this->sut($payload);
    }


    /**
     * @return \Iterator<string, array{array<string, mixed>}>
     */
    public static function invalidHeaderProvider(): \Iterator
    {
        yield 'missing typ' => [['alg' => 'ES256']];
        yield 'wrong typ' => [['alg' => 'ES256', 'typ' => 'JWT']];
        yield 'missing alg' => [['typ' => 'statuslist+jwt']];
        yield 'alg none' => [['alg' => 'none', 'typ' => 'statuslist+jwt']];
        yield 'unknown alg' => [['alg' => 'XX999', 'typ' => 'statuslist+jwt']];
        // RFC 7515 lets `crit` name only extensions, and this token type defines none, so anything listed there
        // is something this code does not implement and was told it must.
        yield 'crit naming an extension' => [
            ['alg' => 'ES256', 'crit' => ['http://example.com/UNDEFINED'], 'typ' => 'statuslist+jwt'],
        ];
        yield 'crit that is empty' => [['alg' => 'ES256', 'crit' => [], 'typ' => 'statuslist+jwt']];
        yield 'crit that is not an array' => [['alg' => 'ES256', 'crit' => 'exp', 'typ' => 'statuslist+jwt']];
        // Present and null is a malformed declaration, not an omitted one, as RFC 7515 requires a `crit` that is
        // there to be a non-empty array.
        yield 'crit that is null' => [['alg' => 'ES256', 'crit' => null, 'typ' => 'statuslist+jwt']];
        // RFC 7515 has `kid` be a string. It is not required here, the specification mandating no key
        // resolution method, but a malformed one still makes this an invalid JWS.
        yield 'kid as an array' => [['alg' => 'ES256', 'kid' => ['12'], 'typ' => 'statuslist+jwt']];
        yield 'kid that is null' => [['alg' => 'ES256', 'kid' => null, 'typ' => 'statuslist+jwt']];
    }


    /**
     * @param array<string,mixed> $header
     */
    #[DataProvider('invalidHeaderProvider')]
    public function testThrowsForInvalidHeader(array $header): void
    {
        $this->expectException(JwsException::class);

        $this->sut(null, $header);
    }
}
