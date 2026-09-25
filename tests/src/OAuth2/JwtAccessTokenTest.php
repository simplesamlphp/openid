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
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Exceptions\JwtAccessTokenException;
use SimpleSAML\OpenID\Factories\ClaimFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jwks\Factories\JwksDecoratorFactory;
use SimpleSAML\OpenID\Jws\JwsDecorator;
use SimpleSAML\OpenID\Jws\JwsVerifierDecorator;
use SimpleSAML\OpenID\Jws\ParsedJws;
use SimpleSAML\OpenID\OAuth2\JwtAccessToken;
use SimpleSAML\OpenID\Serializers\JwsSerializerManagerDecorator;

#[CoversClass(JwtAccessToken::class)]
#[UsesClass(DateIntervalDecorator::class)]
#[UsesClass(Helpers::class)]
#[UsesClass(Helpers\Json::class)]
#[UsesClass(Helpers\MediaType::class)]
#[UsesClass(Helpers\Type::class)]
#[UsesClass(ParsedJws::class)]
#[UsesClass(SignatureAlgorithmEnum::class)]
final class JwtAccessTokenTest extends TestCase
{
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

        // Real helpers, so the type strictness the profile calls for is exercised rather than stubbed away.
        $this->helpers = new Helpers();

        // The header and claims of RFC 9068 section 3, Figure 2, with the timestamps moved to the present.
        $this->sampleHeader = [
            'typ' => 'at+JWT',
            'alg' => 'RS256',
            'kid' => 'RjEwOwOA',
        ];

        $this->samplePayload = [
            'iss' => 'https://authorization-server.example.com/',
            'sub' => '5ba552d67',
            'aud' => 'https://rs.example.com/',
            'exp' => time() + 3600,
            'iat' => time() - 60,
            'jti' => 'dbe39bf3a3ba4238a513f51d6e1691c4',
            'client_id' => 's6BhdRkqt3',
            'scope' => 'openid profile reademail',
        ];
    }


    /**
     * @param ?array<string,mixed> $payload
     * @param ?array<string,mixed> $header
     */
    protected function sut(
        ?array $payload = null,
        ?array $header = null,
        ?DateIntervalDecorator $timestampValidationLeeway = null,
    ): JwtAccessToken {
        $payload ??= $this->samplePayload;
        $header ??= $this->sampleHeader;

        $this->jwsMock->method('getPayload')->willReturn(json_encode($payload));
        $this->signatureMock->method('getProtectedHeader')->willReturn($header);

        return new JwtAccessToken(
            $this->jwsDecoratorMock,
            $this->createStub(JwsVerifierDecorator::class),
            $this->createStub(JwksDecoratorFactory::class),
            $this->createStub(JwsSerializerManagerDecorator::class),
            // The stub's leeway is zero seconds.
            $timestampValidationLeeway ?? $this->createStub(DateIntervalDecorator::class),
            $this->helpers,
            $this->createStub(ClaimFactory::class),
        );
    }


    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf(JwtAccessToken::class, $this->sut());
    }


    public function testCanGetTheRequiredClaims(): void
    {
        $sut = $this->sut();

        $this->assertSame('at+JWT', $sut->getType());
        $this->assertSame('RS256', $sut->getAlgorithm());
        $this->assertSame('RjEwOwOA', $sut->getKeyId());
        $this->assertSame('https://authorization-server.example.com/', $sut->getIssuer());
        $this->assertSame('5ba552d67', $sut->getSubject());
        $this->assertSame(['https://rs.example.com/'], $sut->getAudience());
        $this->assertSame('s6BhdRkqt3', $sut->getClientId());
        $this->assertSame($this->samplePayload['exp'], $sut->getExpirationTime());
        $this->assertSame($this->samplePayload['iat'], $sut->getIssuedAt());
        $this->assertSame('dbe39bf3a3ba4238a513f51d6e1691c4', $sut->getJwtId());
    }


    public function testTheOptionalClaimsAreNullWhenAbsent(): void
    {
        unset($this->samplePayload['scope'], $this->sampleHeader['kid']);

        $sut = $this->sut();

        $this->assertNull($sut->getKeyId());
        $this->assertNull($sut->getScope());
        $this->assertNull($sut->getScopes());
        $this->assertNull($sut->getAuthTime());
        $this->assertNull($sut->getAuthenticationContextClassReference());
        $this->assertNull($sut->getAuthenticationMethodsReferences());
        $this->assertNull($sut->getGroups());
        $this->assertNull($sut->getRoles());
        $this->assertNull($sut->getEntitlements());
    }


    public function testCanGetTheOptionalClaims(): void
    {
        $this->samplePayload['auth_time'] = $this->samplePayload['iat'] - 10;
        $this->samplePayload['acr'] = 'urn:mace:incommon:iap:silver';
        $this->samplePayload['amr'] = ['pwd', 'otp'];
        $this->samplePayload['groups'] = [['value' => 'e9e30dba', 'display' => 'Tour Guides']];
        $this->samplePayload['roles'] = ['Student'];
        $this->samplePayload['entitlements'] = ['urn:mace:example.org:entitlement:library'];

        $sut = $this->sut();

        $this->assertSame('openid profile reademail', $sut->getScope());
        $this->assertSame(['openid', 'profile', 'reademail'], $sut->getScopes());
        $this->assertSame($this->samplePayload['auth_time'], $sut->getAuthTime());
        $this->assertSame('urn:mace:incommon:iap:silver', $sut->getAuthenticationContextClassReference());
        $this->assertSame(['pwd', 'otp'], $sut->getAuthenticationMethodsReferences());
        $this->assertSame([['value' => 'e9e30dba', 'display' => 'Tour Guides']], $sut->getGroups());
        $this->assertSame(['Student'], $sut->getRoles());
        $this->assertSame(['urn:mace:example.org:entitlement:library'], $sut->getEntitlements());
    }


    /**
     * RFC 9068 section 4 accepts "at+jwt" or "application/at+jwt", and RFC 7515 section 4.1.9 makes the
     * comparison a media type one, so case does not matter and the "application/" prefix is implied.
     *
     * @return \Iterator<string, array{string}>
     */
    public static function acceptedTypeProvider(): \Iterator
    {
        yield 'short form' => ['at+jwt'];
        yield 'the example in the specification' => ['at+JWT'];
        yield 'upper case' => ['AT+JWT'];
        yield 'full media type' => ['application/at+jwt'];
        yield 'full media type, mixed case' => ['Application/AT+JWT'];
    }


    #[DataProvider('acceptedTypeProvider')]
    public function testAcceptsEitherSpellingOfTheType(string $typ): void
    {
        $this->sampleHeader['typ'] = $typ;

        $this->assertSame($typ, $this->sut()->getType());
    }


    /**
     * @return \Iterator<string, array{string}>
     */
    public static function rejectedTypeProvider(): \Iterator
    {
        yield 'a plain JWT' => ['JWT'];
        yield 'an ID Token can not pass as an access token' => ['application/jwt'];
        yield 'another type' => ['logout+jwt'];
        yield 'a different top-level type' => ['text/at+jwt'];
        yield 'a parameter makes it another media type' => ['at+jwt;v=1'];
        yield 'a prefix that is not a type' => ['x-at+jwt'];
        yield 'surrounding whitespace' => [' at+jwt '];
    }


    #[DataProvider('rejectedTypeProvider')]
    public function testRejectsAnyOtherType(string $typ): void
    {
        $this->sampleHeader['typ'] = $typ;

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Invalid Type header claim');

        $this->sut();
    }


    /**
     * @return \Iterator<string, array{mixed}>
     */
    public static function malformedTypeProvider(): \Iterator
    {
        yield 'not a string' => [['at+jwt']];
        yield 'empty' => [''];
        yield 'present and null' => [null];
    }


    #[DataProvider('malformedTypeProvider')]
    public function testRejectsAMalformedType(mixed $typ): void
    {
        $this->sampleHeader['typ'] = $typ;

        $this->expectException(JwsException::class);

        $this->sut();
    }


    public function testRejectsAMissingType(): void
    {
        unset($this->sampleHeader['typ']);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('No Type header claim found.');

        $this->sut();
    }


    public function testRejectsAMissingAlgorithm(): void
    {
        unset($this->sampleHeader['alg']);

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('No Algorithm header claim found.');

        $this->sut();
    }


    public function testRejectsANonStringAlgorithm(): void
    {
        $this->sampleHeader['alg'] = 123;

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Value is not a string, aborting. Context: alg');

        $this->sut();
    }


    /**
     * @return \Iterator<string, array{mixed}>
     */
    public static function unencodedPayloadOptionProvider(): \Iterator
    {
        yield 'false' => [false];
        yield 'true' => [true];
        yield 'null' => [null];
    }


    #[DataProvider('unencodedPayloadOptionProvider')]
    public function testRejectsTheUnencodedPayloadOption(mixed $b64): void
    {
        $this->sampleHeader['b64'] = $b64;

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('b64 header parameter');

        $this->sut();
    }


    /**
     * @return \Iterator<string, array{string}>
     */
    public static function optionalPayloadClaimProvider(): \Iterator
    {
        yield 'nbf' => ['nbf'];
        yield 'scope' => ['scope'];
        yield 'auth_time' => ['auth_time'];
        yield 'acr' => ['acr'];
        yield 'amr' => ['amr'];
        yield 'groups' => ['groups'];
        yield 'roles' => ['roles'];
        yield 'entitlements' => ['entitlements'];
    }


    #[DataProvider('optionalPayloadClaimProvider')]
    public function testRejectsAnOptionalClaimPresentAndNull(string $claim): void
    {
        $this->samplePayload[$claim] = null;

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage(sprintf('Claim %s is present and null', $claim));

        $this->sut();
    }


    public function testRejectsAKeyIdPresentAndNull(): void
    {
        $this->sampleHeader['kid'] = null;

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Claim kid is present and null');

        $this->sut();
    }


    public function testRejectsANonStringKeyId(): void
    {
        $this->sampleHeader['kid'] = 12;

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Value is not a string, aborting. Context: kid');

        $this->sut();
    }


    /**
     * @return \Iterator<string, array{mixed}>
     */
    public static function criticalHeaderProvider(): \Iterator
    {
        yield 'an extension' => [['unknown-extension']];
        yield 'a registered parameter' => [['alg']];
        yield 'the empty list' => [[]];
        yield 'not a list' => ['unknown-extension'];
        yield 'null' => [null];
    }


    #[DataProvider('criticalHeaderProvider')]
    public function testRejectsACriticalHeaderParameter(mixed $crit): void
    {
        $this->sampleHeader['crit'] = $crit;

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('critical');

        $this->sut();
    }


    public function testRejectsTheNoneAlgorithm(): void
    {
        $this->sampleHeader['alg'] = 'none';

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('none');

        $this->sut();
    }


    /**
     * The claims RFC 9068 section 2.2 marks REQUIRED, with the message each one's absence produces.
     *
     * @return \Iterator<string, array{string,string}>
     */
    public static function requiredClaimProvider(): \Iterator
    {
        yield 'iss' => ['iss', 'No Issuer claim found.'];
        yield 'sub' => ['sub', 'No Subject claim found.'];
        yield 'aud' => ['aud', 'No Audience claim found.'];
        yield 'client_id' => ['client_id', 'No Client ID claim found.'];
        yield 'exp' => ['exp', 'No Expiration Time claim found.'];
        yield 'iat' => ['iat', 'No Issued At claim found.'];
        yield 'jti' => ['jti', 'No JWT ID claim found.'];
    }


    #[DataProvider('requiredClaimProvider')]
    public function testRejectsAMissingRequiredClaim(string $claim, string $message): void
    {
        unset($this->samplePayload[$claim]);

        // The constructor reports every failed check in one JwsException; the profile's own exception class is
        // named in it, since it is what the getter concerned threw.
        $this->expectException(JwsException::class);
        $this->expectExceptionMessage(JwtAccessTokenException::class . ': ' . $message);

        $this->sut();
    }


    public function testTheGettersThrowTheProfileExceptionThemselves(): void
    {
        // Construction is where the aggregate is thrown; a getter called on its own throws its own exception.
        // Reached through a subclass whose validation is switched off, since a valid instance has nothing to fail.
        // The base class still validates the timestamps it finds, so those are given; the subject is not.
        $this->jwsMock->method('getPayload')->willReturn(json_encode([
            'iss' => 'https://as.example.com/',
            'exp' => time() + 3600,
            'iat' => time() - 60,
        ]));
        $this->signatureMock->method('getProtectedHeader')->willReturn($this->sampleHeader);

        $sut = new class (
            $this->jwsDecoratorMock,
            $this->createStub(JwsVerifierDecorator::class),
            $this->createStub(JwksDecoratorFactory::class),
            $this->createStub(JwsSerializerManagerDecorator::class),
            $this->createStub(DateIntervalDecorator::class),
            $this->helpers,
            $this->createStub(ClaimFactory::class),
        ) extends JwtAccessToken {
            protected function validate(): void
            {
            }
        };

        $this->expectException(JwtAccessTokenException::class);
        $this->expectExceptionMessage('No Subject claim found.');

        $sut->getSubject();
    }


    /**
     * @return \Iterator<string, array{string,mixed}>
     */
    public static function malformedClaimProvider(): \Iterator
    {
        yield 'iss empty' => ['iss', ''];
        yield 'iss a number' => ['iss', 42];
        yield 'sub empty' => ['sub', ''];
        yield 'sub not a string' => ['sub', ['5ba552d67']];
        yield 'sub a boolean' => ['sub', true];
        yield 'sub a number' => ['sub', 5];
        yield 'aud empty string' => ['aud', ''];
        yield 'aud empty list' => ['aud', []];
        yield 'aud with an empty value' => ['aud', ['https://rs.example.com/', '']];
        yield 'aud with a number in the list' => ['aud', ['https://rs.example.com/', 42]];
        yield 'aud an object' => ['aud', ['rs' => 'https://rs.example.com/']];
        yield 'aud not a string or list' => ['aud', 42];
        yield 'client_id empty' => ['client_id', ''];
        yield 'client_id not a string' => ['client_id', ['s6BhdRkqt3']];
        yield 'client_id a number' => ['client_id', 42];
        yield 'exp not a number' => ['exp', 'later'];
        yield 'exp a numeric string' => ['exp', (string)(time() + 3600)];
        yield 'exp a boolean' => ['exp', true];
        yield 'exp beyond the representable range' => ['exp', 1e100];
        yield 'iat not a number' => ['iat', 'earlier'];
        yield 'iat a numeric string' => ['iat', (string)(time() - 60)];
        yield 'iat in the future' => ['iat', PHP_INT_MAX];
        yield 'nbf a numeric string' => ['nbf', (string)(time() - 60)];
        yield 'jti empty' => ['jti', ''];
        yield 'jti a number' => ['jti', 42];
        yield 'scope a number' => ['scope', 42];
        yield 'scope empty' => ['scope', ''];
        yield 'scope with two spaces' => ['scope', 'openid  profile'];
        yield 'scope with a leading space' => ['scope', ' openid'];
        yield 'scope with a trailing space' => ['scope', 'openid '];
        yield 'scope with a double quote' => ['scope', 'open"id'];
        yield 'scope with a backslash' => ['scope', 'open\\id'];
        yield 'scope not a string' => ['scope', ['openid', 'profile']];
        yield 'auth_time not a number' => ['auth_time', 'earlier'];
        yield 'auth_time a numeric string' => ['auth_time', (string)(time() - 60)];
        yield 'acr empty' => ['acr', ''];
        yield 'acr a number' => ['acr', 1];
        yield 'amr not a list' => ['amr', 'pwd'];
        yield 'amr an object' => ['amr', ['method' => 'pwd']];
        yield 'amr with an empty value' => ['amr', ['pwd', '']];
        yield 'amr with a number' => ['amr', ['pwd', 2]];
        yield 'groups not a list' => ['groups', 'admins'];
        yield 'groups an object' => ['groups', ['value' => 'admins']];
        yield 'roles not a list' => ['roles', 'Student'];
        yield 'entitlements not a list' => ['entitlements', 'library'];
    }


    #[DataProvider('malformedClaimProvider')]
    public function testRejectsAMalformedClaim(string $claim, mixed $value): void
    {
        $this->samplePayload[$claim] = $value;

        $this->expectException(JwsException::class);

        $this->sut();
    }


    public function testRejectsAnExpiredToken(): void
    {
        $this->samplePayload['exp'] = time() - 3600;

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Expiration Time');

        $this->sut();
    }


    /**
     * RFC 9068 section 4: "The current time MUST be before the time represented by the "exp" claim." -- so the
     * second the deadline is reached the token is expired.
     */
    public function testRejectsATokenAtTheSecondOfItsExpiration(): void
    {
        $this->samplePayload['exp'] = time();

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Expiration Time');

        $this->sut();
    }


    public function testTheLeewayExtendsTheExpiration(): void
    {
        $this->samplePayload['exp'] = time() - 30;

        $sut = $this->sut(timestampValidationLeeway: new DateIntervalDecorator(new DateInterval('PT1M')));

        $this->assertSame($this->samplePayload['exp'], $sut->getExpirationTime());
    }


    public function testTheLeewayIsNotUnbounded(): void
    {
        $this->samplePayload['exp'] = time() - 90;

        $this->expectException(JwsException::class);
        $this->expectExceptionMessage('Expiration Time');

        $this->sut(timestampValidationLeeway: new DateIntervalDecorator(new DateInterval('PT1M')));
    }


    /**
     * The fraction is kept for the expiry comparison, so a token which expires later this second is not yet
     * expired, and only the returned value is truncated.
     */
    public function testAFractionOfASecondBeforeExpirationIsNotExpired(): void
    {
        // Holds only within the second the expiry is set in. When the clock ticks before the token is read, it
        // has rightly expired, so that attempt proves nothing and is made again.
        for ($attempt = 0; $attempt < 3; ++$attempt) {
            $exp = time();
            $this->samplePayload['exp'] = $exp + 0.9;

            try {
                $expirationTime = $this->sut()->getExpirationTime();
            } catch (JwsException $jwsException) {
                if (time() !== $exp) {
                    continue;
                }

                throw $jwsException;
            }

            $this->assertSame($exp, $expirationTime);

            return;
        }

        $this->fail('The clock moved on to the next second during every attempt.');
    }


    public function testAFractionalNumericDateIsTruncated(): void
    {
        $exp = time() + 3600;
        $iat = time() - 60;
        $this->samplePayload['exp'] = $exp + 0.5;
        $this->samplePayload['iat'] = $iat + 0.5;

        $sut = $this->sut();

        $this->assertSame($exp, $sut->getExpirationTime());
        $this->assertSame($iat, $sut->getIssuedAt());
    }


    public function testAudienceMayBeAList(): void
    {
        $this->samplePayload['aud'] = ['https://rs.example.com/', 'https://other-rs.example.com/'];

        $this->assertSame(
            ['https://rs.example.com/', 'https://other-rs.example.com/'],
            $this->sut()->getAudience(),
        );
    }


    /**
     * A subject, client or scope of "0" is a value, not an absence; the validation must not mistake one for the
     * other.
     */
    public function testKeepsFalsyStringValues(): void
    {
        $this->samplePayload['sub'] = '0';
        $this->samplePayload['client_id'] = '0';
        $this->samplePayload['scope'] = '0';

        $sut = $this->sut();

        $this->assertSame('0', $sut->getSubject());
        $this->assertSame('0', $sut->getClientId());
        $this->assertSame('0', $sut->getScope());
        $this->assertSame(['0'], $sut->getScopes());
    }


    /**
     * RFC 6749 section 3.3: "scope-token = 1*( %x21 / %x23-5B / %x5D-7E )", so everything printable except
     * space, double quote and backslash.
     */
    public function testAcceptsEveryCharacterTheScopeGrammarAllows(): void
    {
        $this->samplePayload['scope'] = 'read:email !#$%&\'()*+,-./0123456789:;<=>?@[]^_`{|}~ openid';

        $sut = $this->sut();

        $this->assertSame(
            ['read:email', '!#$%&\'()*+,-./0123456789:;<=>?@[]^_`{|}~', 'openid'],
            $sut->getScopes(),
        );
    }


    public function testCanGetTheClaimsThisProfileDoesNotName(): void
    {
        $this->samplePayload['nbf'] = $this->samplePayload['iat'];
        $this->samplePayload['email'] = 'user@example.com';

        $sut = $this->sut();

        $this->assertSame($this->samplePayload['iat'], $sut->getNotBefore());
        $this->assertSame('user@example.com', $sut->getPayloadClaim('email'));
    }
}
