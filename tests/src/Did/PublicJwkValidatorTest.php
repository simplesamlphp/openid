<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Did;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Did\PublicJwkValidator;
use SimpleSAML\OpenID\Exceptions\DidException;

#[CoversClass(PublicJwkValidator::class)]
final class PublicJwkValidatorTest extends TestCase
{
    /** A real 32 byte base64url coordinate. */
    private const X = 'dDfIibQM-949qf4jj-8mBY4Azq34ygSGhzd8AT2mx6s';

    private const Y = 'VUyI8G-OYirMrrsCB9lvUbr6Wjq2ef73ne_paBqLPxw';

    private const COORDINATES = ['x' => self::X, 'y' => self::Y];

    private const EC_JWK = [
        'kty' => 'EC',
        'crv' => 'P-256',
        'x' => self::X,
        'y' => self::Y,
    ];

    private const OKP_JWK = [
        'kty' => 'OKP',
        'crv' => 'Ed25519',
        'x' => self::X,
    ];

    private const RSA_JWK = [
        'kty' => 'RSA',
        'n' => 'sXchDaQe1Nqm0dnAAKPTQrfC1QIDAQAB',
        'e' => 'AQAB',
    ];


    protected function sut(): PublicJwkValidator
    {
        return new PublicJwkValidator();
    }


    /**
     * @param array<array-key, mixed> $jwk
     */
    #[DataProvider('validJwkDataProvider')]
    public function testAcceptsValidPublicJwk(array $jwk): void
    {
        $this->sut()->validate($jwk);

        $this->addToAssertionCount(1);
    }


    public static function validJwkDataProvider(): \Iterator
    {
        yield 'EC P-256' => [self::EC_JWK];
        yield 'EC P-256 with alg' => [self::EC_JWK + ['alg' => 'ES256']];
        yield 'EC P-256 with optional members' => [
            self::EC_JWK + ['kid' => 'key-1', 'use' => 'sig', 'key_ops' => ['verify'], 'x5c' => ['abc']],
        ];
        yield 'EC secp256k1' => [['kty' => 'EC', 'crv' => 'secp256k1'] + self::COORDINATES];
        yield 'OKP Ed25519' => [self::OKP_JWK];
        yield 'OKP Ed25519 with EdDSA' => [self::OKP_JWK + ['alg' => 'EdDSA']];
        yield 'OKP X25519' => [['kty' => 'OKP', 'crv' => 'X25519', 'x' => self::X, 'use' => 'enc']];
        yield 'RSA' => [self::RSA_JWK];
        yield 'RSA with RS256' => [self::RSA_JWK + ['alg' => 'RS256']];
        yield 'RSA with PS512' => [self::RSA_JWK + ['alg' => 'PS512']];
        yield 'EC with a key management alg' => [self::EC_JWK + ['alg' => 'ECDH-ES']];
        yield 'RSA with a key management alg' => [self::RSA_JWK + ['alg' => 'RSA-OAEP-256']];
        // An algorithm this map does not recognise is left alone rather than guessed at.
        yield 'EC with an unrecognised alg' => [self::EC_JWK + ['alg' => 'A-FUTURE-ALG']];
    }


    /**
     * @param array<array-key, mixed> $jwk
     */
    #[DataProvider('invalidJwkDataProvider')]
    public function testRejectsInvalidJwk(array $jwk, string $expectedExceptionMessage): void
    {
        $this->expectException(DidException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->sut()->validate($jwk);
    }


    public static function invalidJwkDataProvider(): \Iterator
    {
        yield 'missing kty' => [
            ['crv' => 'P-256', 'x' => 'abc', 'y' => 'def'],
            'JWK member "kty" must be a non-empty string.',
        ];
        yield 'non-string kty' => [
            ['kty' => 1],
            'JWK member "kty" must be a non-empty string.',
        ];
        yield 'unsupported kty' => [
            ['kty' => 'oct', 'k' => 'abc'],
            'Unsupported JWK key type: oct.',
        ];
        yield 'EC private member d' => [
            self::EC_JWK + ['d' => 'secret'],
            'JWK member "d" is not permitted for key type EC.',
        ];
        yield 'OKP private member d' => [
            self::OKP_JWK + ['d' => 'secret'],
            'JWK member "d" is not permitted for key type OKP.',
        ];
        yield 'RSA private member p' => [
            self::RSA_JWK + ['p' => 'secret'],
            'JWK member "p" is not permitted for key type RSA.',
        ];
        yield 'RSA private member qi' => [
            self::RSA_JWK + ['qi' => 'secret'],
            'JWK member "qi" is not permitted for key type RSA.',
        ];
        yield 'unknown member' => [
            self::EC_JWK + ['surprise' => 'value'],
            'JWK member "surprise" is not permitted for key type EC.',
        ];
        yield 'crv on an RSA key' => [
            self::RSA_JWK + ['crv' => 'P-256'],
            'JWK member "crv" is not permitted for key type RSA.',
        ];
        yield 'EC without y' => [
            ['kty' => 'EC', 'crv' => 'P-256', 'x' => self::X],
            'JWK member "y" must be a non-empty string.',
        ];
        yield 'EC with empty x' => [
            ['kty' => 'EC', 'crv' => 'P-256', 'x' => '', 'y' => self::Y],
            'JWK member "x" must be a non-empty string.',
        ];
        yield 'EC with non-string x' => [
            ['kty' => 'EC', 'crv' => 'P-256', 'x' => 42, 'y' => self::Y],
            'JWK member "x" must be a non-empty string.',
        ];
        yield 'OKP without x' => [
            ['kty' => 'OKP', 'crv' => 'Ed25519'],
            'JWK member "x" must be a non-empty string.',
        ];
        yield 'RSA without e' => [
            ['kty' => 'RSA', 'n' => self::X],
            'JWK member "e" must be a non-empty string.',
        ];
        yield 'EC with an OKP curve' => [
            ['kty' => 'EC', 'crv' => 'Ed25519'] + self::COORDINATES,
            'Curve "Ed25519" is not valid for key type EC.',
        ];
        yield 'OKP with an EC curve' => [
            ['kty' => 'OKP', 'crv' => 'P-256', 'x' => self::X],
            'Curve "P-256" is not valid for key type OKP.',
        ];
        yield 'ES256 with the wrong curve' => [
            ['kty' => 'EC', 'crv' => 'P-384', 'alg' => 'ES256'] + self::COORDINATES,
            'JWK algorithm "ES256" is not valid for curve "P-384".',
        ];
        yield 'ES256 with the wrong key type' => [
            self::OKP_JWK + ['alg' => 'ES256'],
            'JWK algorithm "ES256" requires key type EC, but the key is OKP.',
        ];
        yield 'EdDSA with the wrong key type' => [
            self::EC_JWK + ['alg' => 'EdDSA'],
            'JWK algorithm "EdDSA" requires key type OKP, but the key is EC.',
        ];
        yield 'RS256 with the wrong key type' => [
            self::EC_JWK + ['alg' => 'RS256'],
            'JWK algorithm "RS256" requires key type RSA, but the key is EC.',
        ];
        yield 'key_ops is not an array' => [
            self::EC_JWK + ['key_ops' => 'verify'],
            'JWK member "key_ops" must be a non-empty array of strings.',
        ];
        yield 'x5c is not an array' => [
            self::EC_JWK + ['x5c' => 42],
            'JWK member "x5c" must be a non-empty array of strings.',
        ];
        yield 'x5c holds a non-string' => [
            self::EC_JWK + ['x5c' => ['abc', 42]],
            'JWK member "x5c" must be a non-empty array of strings.',
        ];
        yield 'key_ops is empty' => [
            self::EC_JWK + ['key_ops' => []],
            'JWK member "key_ops" must be a non-empty array of strings.',
        ];
        yield 'HS256 on an EC key' => [
            self::EC_JWK + ['alg' => 'HS256'],
            'JWK algorithm "HS256" needs a symmetric key or no key at all, so it cannot belong to a key of ' .
            'type EC.',
        ];
        yield 'none on an RSA key' => [
            self::RSA_JWK + ['alg' => 'none'],
            'JWK algorithm "none" needs a symmetric key or no key at all, so it cannot belong to a key of ' .
            'type RSA.',
        ];
        yield 'dir on an OKP key' => [
            self::OKP_JWK + ['alg' => 'dir'],
            'JWK algorithm "dir" needs a symmetric key or no key at all, so it cannot belong to a key of ' .
            'type OKP.',
        ];
        yield 'x is not base64url' => [
            ['kty' => 'EC', 'crv' => 'P-256', 'x' => '!!!!', 'y' => self::Y],
            'JWK member "x" must be base64url encoded.',
        ];
        // base64_decode tolerates whitespace even in strict mode, and PCRE lets $ match before a trailing
        // newline, so without the D modifier this decoded to the right bytes and passed every later check.
        yield 'x with a trailing line feed' => [
            ['kty' => 'EC', 'crv' => 'P-256', 'x' => self::X . "\n", 'y' => self::Y],
            'JWK member "x" must be base64url encoded.',
        ];
        yield 'n with a trailing line feed' => [
            ['kty' => 'RSA', 'n' => "sXchDaQe1Nqm0dnAAKPTQrfC1QIDAQAB\n", 'e' => 'AQAB'],
            'JWK member "n" must be base64url encoded.',
        ];
        yield 'Ed25519 x decodes to the wrong length' => [
            ['kty' => 'OKP', 'crv' => 'Ed25519', 'x' => 'AA'],
            'JWK member "x" must decode to 32 bytes for curve Ed25519, but it decoded to 1.',
        ];
        yield 'P-384 coordinates sized for P-256' => [
            ['kty' => 'EC', 'crv' => 'P-384'] + self::COORDINATES,
            'JWK member "x" must decode to 48 bytes for curve P-384, but it decoded to 32.',
        ];
        yield 'P-521 coordinates sized for P-256' => [
            ['kty' => 'EC', 'crv' => 'P-521'] + self::COORDINATES,
            'JWK member "x" must decode to 66 bytes for curve P-521, but it decoded to 32.',
        ];
        yield 'Ed448 x sized for Ed25519' => [
            ['kty' => 'OKP', 'crv' => 'Ed448', 'x' => self::X],
            'JWK member "x" must decode to 57 bytes for curve Ed448, but it decoded to 32.',
        ];
        yield 'X448 x sized for X25519' => [
            ['kty' => 'OKP', 'crv' => 'X448', 'x' => self::X],
            'JWK member "x" must decode to 56 bytes for curve X448, but it decoded to 32.',
        ];
        yield 'RSA-OAEP on an EC key' => [
            self::EC_JWK + ['alg' => 'RSA-OAEP'],
            'JWK algorithm "RSA-OAEP" requires key type RSA, but the key is EC.',
        ];
        yield 'ECDH-ES on an RSA key' => [
            self::RSA_JWK + ['alg' => 'ECDH-ES'],
            'JWK algorithm "ECDH-ES" requires key type EC or OKP, but the key is RSA.',
        ];
        yield 'ECDH-ES on a signing curve' => [
            ['kty' => 'OKP', 'crv' => 'Ed25519', 'x' => self::X, 'alg' => 'ECDH-ES'],
            'JWK algorithm "ECDH-ES" is not valid for curve "Ed25519".',
        ];
        yield 'non-string use' => [
            self::EC_JWK + ['use' => 1],
            'JWK member "use" must be a non-empty string.',
        ];
        yield 'non-string kid' => [
            self::EC_JWK + ['kid' => ['key-1']],
            'JWK member "kid" must be a non-empty string.',
        ];
        yield 'non-string alg' => [
            self::EC_JWK + ['alg' => 256],
            'JWK member "alg" must be a non-empty string.',
        ];
    }
}
