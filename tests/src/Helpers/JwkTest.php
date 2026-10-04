<?php

declare(strict_types=1);

namespace SimpleSAML\Test\OpenID\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Exceptions\InvalidValueException;
use SimpleSAML\OpenID\Helpers\Jwk;

#[CoversClass(Jwk::class)]
#[UsesClass(SignatureAlgorithmEnum::class)]
final class JwkTest extends TestCase
{
    /**
     * The public key of the DPoP proof in RFC 9449 section 4.2, Figure 4.
     */
    protected const EC_P256_KEY = [
        'kty' => 'EC',
        'x' => 'l8tFrhx-34tV3hRICRDY9zCkDlpBhF42UQUfWVAWBFs',
        'y' => '9VE4jf_Ok_o64zbTTlcuNJajHmt6v9TDVrU0CdvGRDA',
        'crv' => 'P-256',
    ];

    /**
     * The public key of RFC 8037 appendix A.2.
     */
    protected const OKP_ED25519_KEY = [
        'kty' => 'OKP',
        'crv' => 'Ed25519',
        'x' => '11qYAYKxCrfVS_7TyWQHOg7hcvPapiMlrwIaaPcHURo',
    ];


    protected function sut(): Jwk
    {
        return new Jwk();
    }


    /**
     * An RSA modulus of exactly the given number of bits: the most significant one set, the rest a fixed pattern.
     */
    protected static function modulusOfBits(int $bits, string $prefix = ''): string
    {
        $octets = intdiv($bits + 7, 8);
        $leadingBits = $bits - ($octets - 1) * 8;
        $modulus = $prefix . chr(1 << ($leadingBits - 1)) . str_repeat("\xA5", $octets - 1);

        return rtrim(strtr(base64_encode($modulus), '+/', '-_'), '=');
    }


    /**
     * The product of the odd primes below 752, found here by gmp_prob_prime(), which is exact for numbers that
     * small, rather than by the sieve the code under test uses.
     */
    protected static function smallOddPrimesProduct(): \GMP
    {
        $product = gmp_init(1);

        for ($candidate = 3; $candidate < 752; $candidate += 2) {
            if (gmp_prob_prime($candidate) > 0) {
                $product = gmp_mul($product, $candidate);
            }
        }

        return $product;
    }


    /**
     * An odd number of exactly the given number of bits with no prime factor below 752 and not a perfect power,
     * the first such from 2 ** ($bits - 1) + 1: as far as the checks on a modulus go, an RSA modulus.
     */
    protected static function factorFreeModulus(int $bits): \GMP
    {
        $product = self::smallOddPrimesProduct();
        $modulus = gmp_add(gmp_pow(2, $bits - 1), 1);

        while (gmp_cmp(gmp_gcd($modulus, $product), 1) !== 0 || gmp_perfect_power($modulus)) {
            $modulus = gmp_add($modulus, 2);
        }

        return $modulus;
    }


    protected static function encodeNumber(\GMP $number): string
    {
        return self::encode(gmp_export($number));
    }


    /**
     * @return array<string,string>
     */
    protected static function rsaKey(int $bits = 2048): array
    {
        return [
            'kty' => 'RSA',
            'n' => self::encodeNumber(self::factorFreeModulus($bits)),
            'e' => 'AQAB',
        ];
    }


    /**
     * @return \Iterator<string, array{array<string,mixed>}>
     */
    public static function publicKeyProvider(): \Iterator
    {
        yield 'EC' => [self::EC_P256_KEY];
        yield 'RSA' => [self::rsaKey()];
        yield 'OKP' => [self::OKP_ED25519_KEY];
        yield 'EC on P-384' => [
            [
                'crv' => 'P-384',
                'x' => self::encode(str_repeat("\x01", 48)),
                'y' => self::encode(str_repeat("\x02", 48)),
            ] + self::EC_P256_KEY,
        ];
        yield 'EC on P-521, whose coordinates may start with a zero octet' => [
            [
                'crv' => 'P-521',
                'x' => self::encode("\0" . str_repeat("\x01", 65)),
                'y' => self::encode(str_repeat("\x02", 66)),
            ] + self::EC_P256_KEY,
        ];
        yield 'OKP on Ed448' => [
            ['crv' => 'Ed448', 'x' => self::encode(str_repeat("\x03", 57))] + self::OKP_ED25519_KEY,
        ];
        yield 'RSA with a 4096-bit modulus and a larger exponent' => [['e' => 'AQAAAQ'] + self::rsaKey(4096)];
        yield 'RSA with the largest modulus taken' => [self::rsaKey(8192)];
        yield 'RSA with the smallest exponent taken, 3' => [['e' => 'Aw'] + self::rsaKey()];
        yield 'RSA with the largest exponent taken, 256 bits' => [
            ['e' => self::encode("\x80" . str_repeat("\0", 30) . "\x01")] + self::rsaKey(),
        ];
        // The first prime above the bound, so the bound itself is what is tested.
        yield 'RSA with a factor of 757, above the bound' => [
            ['n' => self::encodeNumber(gmp_mul(757, self::factorFreeModulus(2040)))] + self::rsaKey(),
        ];
        yield 'with every member RFC 7517 defines for any key' => [
            self::EC_P256_KEY + [
                'use' => 'sig',
                'key_ops' => ['verify'],
                'alg' => 'ES256',
                'kid' => 'key-1',
                'x5u' => 'https://example.com/cert.pem',
                'x5c' => ['MIIB'],
                'x5t' => 'dGh1bWI',
                'x5t#S256' => 'dGh1bWI',
            ],
        ];
    }


    /**
     * @param array<string,mixed> $jwk
     */
    #[DataProvider('publicKeyProvider')]
    public function testAcceptsAPublicKey(array $jwk): void
    {
        $this->assertSame($jwk, $this->sut()->enforcePublicKey($jwk, 'jwk'));
    }


    /**
     * @return \Iterator<string, array{mixed, string}>
     */
    public static function notAPublicKeyProvider(): \Iterator
    {
        yield 'a string' => ['{"kty":"EC"}', 'not a JSON object'];
        yield 'null' => [null, 'not a JSON object'];
        yield 'an empty object' => [[], 'not a JSON object'];
        yield 'a list' => [[self::EC_P256_KEY], 'not a JSON object'];
        yield 'no key type' => [array_diff_key(self::EC_P256_KEY, ['kty' => true]), 'not of an asymmetric key type'];
        yield 'a key type that is not a string' => [
            ['kty' => ['EC']] + self::EC_P256_KEY,
            'not of an asymmetric key type',
        ];
        yield 'a symmetric key' => [['kty' => 'oct', 'k' => 'c2VjcmV0'], 'not of an asymmetric key type'];
        yield 'an unknown key type' => [['kty' => 'ec'] + self::EC_P256_KEY, 'not of an asymmetric key type'];
        yield 'an EC private key' => [
            self::EC_P256_KEY + ['d' => 'jpsQnnGQmL-YBIffH1136cspYG6-0iY7X1fCE9-E9LI'],
            'members which are not part of a public EC key',
        ];
        yield 'an RSA private key' => [
            self::rsaKey() + ['d' => 'AQAB', 'p' => 'AQAB', 'q' => 'AQAB', 'dp' => 'AQ', 'dq' => 'AQ', 'qi' => 'AQ'],
            'members which are not part of a public RSA key',
        ];
        yield 'an RSA key with other primes' => [
            self::rsaKey() + ['oth' => [['r' => 'AQAB', 'd' => 'AQAB', 't' => 'AQAB']]],
            'members which are not part of a public RSA key',
        ];
        yield 'an OKP private key' => [
            self::OKP_ED25519_KEY + ['d' => 'nWGxne_9WmC6hEr0kuwsxERJxWl7MmkZcDusAxyuf2A'],
            'members which are not part of a public OKP key',
        ];
        yield 'a member of another key type' => [
            self::OKP_ED25519_KEY + ['y' => self::EC_P256_KEY['y']],
            'members which are not part of a public OKP key',
        ];
        yield 'a member nobody defines' => [
            self::EC_P256_KEY + ['private' => 'yes'],
            'members which are not part of a public EC key',
        ];
        yield 'a numeric member name' => [
            self::EC_P256_KEY + [0 => 'x'],
            'members which are not part of a public EC key',
        ];
        yield 'no y' => [array_diff_key(self::EC_P256_KEY, ['y' => true]), 'no y member'];
        yield 'no curve' => [array_diff_key(self::OKP_ED25519_KEY, ['crv' => true]), 'no crv member'];
        yield 'no modulus' => [array_diff_key(self::rsaKey(), ['n' => true]), 'no n member'];
        yield 'no exponent' => [array_diff_key(self::rsaKey(), ['e' => true]), 'no e member'];
        yield 'an empty coordinate' => [['x' => ''] + self::EC_P256_KEY, 'no x member'];
        yield 'a coordinate that is not a string' => [['x' => 42] + self::EC_P256_KEY, 'no x member'];
        yield 'a curve that is not a string' => [['crv' => ['P-256']] + self::EC_P256_KEY, 'no crv member'];
        yield 'a coordinate in base64 rather than base64url' => [
            ['x' => 'l8tFrhx+34tV3hRICRDY9zCkDlpBhF42UQUfWVAWBFs'] + self::EC_P256_KEY,
            'member x is not in canonical base64url encoding',
        ];
        yield 'a padded coordinate' => [
            ['y' => '9VE4jf_Ok_o64zbTTlcuNJajHmt6v9TDVrU0CdvGRDA='] + self::EC_P256_KEY,
            'member y is not in canonical base64url encoding',
        ];
        yield 'a modulus with a trailing newline' => [
            ['n' => self::modulusOfBits(2048) . "\n"] + self::rsaKey(),
            'member n is not in canonical base64url encoding',
        ];
        // The last character of the coordinate with a bit set after its last whole octet: a lenient decoder
        // reads the same 32 octets, while the thumbprint, taken over the string, would differ.
        yield 'a coordinate with stray bits after its last octet' => [
            ['x' => 'l8tFrhx-34tV3hRICRDY9zCkDlpBhF42UQUfWVAWBFt'] + self::EC_P256_KEY,
            'member x is not in canonical base64url encoding',
        ];
        // 342 characters encode the 256 octets; 345, one more than a multiple of four, encode nothing.
        yield 'a modulus of a length no encoding has' => [
            ['n' => self::modulusOfBits(2048) . 'AAA'] + self::rsaKey(),
            'member n is not in canonical base64url encoding',
        ];
        yield 'a coordinate with a leading zero octet' => [
            ['x' => self::encode("\0" . self::decode(self::EC_P256_KEY['x']))] + self::EC_P256_KEY,
            'member x is not the 32 octets a P-256 key takes',
        ];
        yield 'a coordinate an octet short' => [
            ['y' => self::encode(substr(self::decode(self::EC_P256_KEY['y']), 1))] + self::EC_P256_KEY,
            'member y is not the 32 octets a P-256 key takes',
        ];
        yield 'P-256 coordinates on P-384' => [
            ['crv' => 'P-384'] + self::EC_P256_KEY,
            'member x is not the 48 octets a P-384 key takes',
        ];
        yield 'an Ed25519 key an octet long' => [
            ['x' => self::encode(self::decode(self::OKP_ED25519_KEY['x']) . "\x01")] + self::OKP_ED25519_KEY,
            'member x is not the 32 octets a Ed25519 key takes',
        ];
        yield 'a modulus with a leading zero octet' => [
            ['n' => self::modulusOfBits(2048, "\0")] + self::rsaKey(),
            'member n is not in the minimum number of octets',
        ];
        // RFC 7638 section 7's own example of a second spelling of a key.
        yield 'an exponent with a leading zero octet' => [
            ['e' => 'AAEAAQ'] + self::rsaKey(),
            'member e is not in the minimum number of octets',
        ];
        // With an exponent of 1 a signature is its own message encoding: computable by anyone.
        yield 'an RSA exponent of 1' => [['e' => 'AQ'] + self::rsaKey(), 'member e is not an odd RSA exponent'];
        yield 'an RSA exponent of 2' => [['e' => 'Ag'] + self::rsaKey(), 'member e is not an odd RSA exponent'];
        yield 'an even RSA exponent' => [['e' => 'AQAA'] + self::rsaKey(), 'member e is not an odd RSA exponent'];
        yield 'an RSA exponent over 256 bits' => [
            ['e' => self::encode("\x01" . str_repeat("\0", 31) . "\x01")] + self::rsaKey(),
            'member e is not an odd RSA exponent of at least 3 and at most 256 bits',
        ];
        yield 'an even RSA modulus' => [
            ['n' => self::encode("\x80" . str_repeat("\xA5", 254) . "\xA4")] + self::rsaKey(),
            'member n is not an odd RSA modulus',
        ];
        yield 'an RSA modulus over 8192 bits' => [
            self::rsaKey(8193),
            'member n is not an odd RSA modulus of at most 8192 bits',
        ];
        // The square of a prime: its integer square root is the private key.
        yield 'an RSA modulus that is a square' => [
            ['n' => self::encodeNumber(gmp_pow(gmp_nextprime(gmp_mul(3, gmp_pow(2, 1022))), 2))] + self::rsaKey(),
            'member n is not an RSA modulus whose factors are secret',
        ];
        yield 'an RSA modulus that is a cube' => [
            ['n' => self::encodeNumber(gmp_pow(gmp_nextprime(gmp_pow(2, 682)), 3))] + self::rsaKey(),
            'member n is not an RSA modulus whose factors are secret',
        ];
        yield 'an RSA modulus with a factor of 3' => [
            ['n' => self::encodeNumber(gmp_mul(3, self::factorFreeModulus(2046)))] + self::rsaKey(),
            'member n is not an RSA modulus whose factors are secret',
        ];
        yield 'an RSA modulus with a factor of 751, the largest prime below the bound' => [
            ['n' => self::encodeNumber(gmp_mul(751, self::factorFreeModulus(2040)))] + self::rsaKey(),
            'member n is not an RSA modulus whose factors are secret',
        ];
        yield 'an EC curve this library does not know' => [
            ['crv' => 'secp256k1'] + self::EC_P256_KEY,
            'not on a curve this library knows for a EC key',
        ];
        yield 'an OKP curve for key agreement' => [
            ['crv' => 'X25519'] + self::OKP_ED25519_KEY,
            'not on a curve this library knows for a OKP key',
        ];
        yield 'an EC curve name of another key type' => [
            ['crv' => 'Ed25519'] + self::EC_P256_KEY,
            'not on a curve this library knows for a EC key',
        ];
    }


    protected static function encode(string $octets): string
    {
        return rtrim(strtr(base64_encode($octets), '+/', '-_'), '=');
    }


    protected static function decode(string $value): string
    {
        return (string)base64_decode(strtr($value, '-_', '+/'), true);
    }


    #[DataProvider('notAPublicKeyProvider')]
    public function testRefusesWhatIsNotAPublicKey(mixed $jwk, string $message): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage($message);

        $this->sut()->enforcePublicKey($jwk);
    }


    public function testTheRefusalNamesTheContextButNotTheMembers(): void
    {
        $privateValue = 'jpsQnnGQmL-YBIffH1136cspYG6-0iY7X1fCE9-E9LI';

        try {
            $this->sut()->enforcePublicKey(self::EC_P256_KEY + ['d' => $privateValue], 'jwk');
            $this->fail('A private key was accepted.');
        } catch (InvalidValueException $invalidValueException) {
            $this->assertStringContainsString('(jwk)', $invalidValueException->getMessage());
            $this->assertStringNotContainsString($privateValue, $invalidValueException->getMessage());
            $this->assertStringNotContainsString('"d"', $invalidValueException->getMessage());
            $this->assertStringNotContainsString(' d ', $invalidValueException->getMessage());
        }
    }


    public function testCanTakeTheRequiredMembersOfAPublicKey(): void
    {
        $metadata = ['kid' => 'key-1', 'use' => 'sig', 'key_ops' => ['sign'], 'alg' => 'ES256', 'x5c' => ['MIIB']];

        $this->assertSame(self::EC_P256_KEY, $this->sut()->getRequiredMembers(self::EC_P256_KEY + $metadata));
        $this->assertSame(self::rsaKey(), $this->sut()->getRequiredMembers(self::rsaKey() + $metadata));
        $this->assertSame(
            self::OKP_ED25519_KEY,
            $this->sut()->getRequiredMembers($metadata + self::OKP_ED25519_KEY),
        );
    }


    /**
     * @return \Iterator<string, array{array<string,mixed>, \SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum}>
     */
    public static function fittingKeyProvider(): \Iterator
    {
        foreach (['RS256', 'RS384', 'RS512', 'PS256', 'PS384', 'PS512'] as $algorithm) {
            yield $algorithm . ' with a 2048-bit key' => [self::rsaKey(), SignatureAlgorithmEnum::from($algorithm)];
        }

        yield 'RS256 with a 4096-bit key' => [self::rsaKey(4096), SignatureAlgorithmEnum::RS256];
        yield 'ES256 with P-256' => [self::EC_P256_KEY, SignatureAlgorithmEnum::ES256];
        yield 'ES384 with P-384' => [['crv' => 'P-384'] + self::EC_P256_KEY, SignatureAlgorithmEnum::ES384];
        yield 'ES512 with P-521' => [['crv' => 'P-521'] + self::EC_P256_KEY, SignatureAlgorithmEnum::ES512];
        yield 'EdDSA with Ed25519' => [self::OKP_ED25519_KEY, SignatureAlgorithmEnum::EdDSA];
        yield 'EdDSA with Ed448' => [['crv' => 'Ed448'] + self::OKP_ED25519_KEY, SignatureAlgorithmEnum::EdDSA];
    }


    /**
     * @param array<string,mixed> $jwk
     */
    #[DataProvider('fittingKeyProvider')]
    public function testAcceptsAKeyTheAlgorithmCanBeUsedWith(array $jwk, SignatureAlgorithmEnum $algorithm): void
    {
        $this->sut()->enforceKeyFitsSignatureAlgorithm($jwk, $algorithm);

        $this->addToAssertionCount(1);
    }


    /**
     * @return \Iterator<
     *     string,
     *     array{array<string,mixed>, \SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum, string}
     * >
     */
    public static function unfittingKeyProvider(): \Iterator
    {
        yield 'RS256 with an EC key' => [self::EC_P256_KEY, SignatureAlgorithmEnum::RS256, 'not a key algorithm RS256'];
        yield 'PS256 with an OKP key' => [
            self::OKP_ED25519_KEY,
            SignatureAlgorithmEnum::PS256,
            'not a key algorithm PS256',
        ];
        yield 'RS256 with a 2047-bit key' => [self::rsaKey(2047), SignatureAlgorithmEnum::RS256, '2047 bits'];
        yield 'PS512 with a 1024-bit key' => [self::rsaKey(1024), SignatureAlgorithmEnum::PS512, '1024 bits'];
        yield 'ES256 with an RSA key' => [self::rsaKey(), SignatureAlgorithmEnum::ES256, 'not a key algorithm ES256'];
        yield 'ES256 with P-384' => [
            ['crv' => 'P-384'] + self::EC_P256_KEY,
            SignatureAlgorithmEnum::ES256,
            'not a key algorithm ES256',
        ];
        yield 'ES384 with P-256' => [self::EC_P256_KEY, SignatureAlgorithmEnum::ES384, 'not a key algorithm ES384'];
        yield 'ES512 with P-512, which is not a curve' => [
            ['crv' => 'P-512'] + self::EC_P256_KEY,
            SignatureAlgorithmEnum::ES512,
            'not a key algorithm ES512',
        ];
        yield 'ES256 with secp256k1' => [
            ['crv' => 'secp256k1'] + self::EC_P256_KEY,
            SignatureAlgorithmEnum::ES256,
            'not a key algorithm ES256',
        ];
        yield 'ES256 with an Ed25519 key' => [
            self::OKP_ED25519_KEY,
            SignatureAlgorithmEnum::ES256,
            'not a key algorithm ES256',
        ];
        yield 'EdDSA with an EC key' => [self::EC_P256_KEY, SignatureAlgorithmEnum::EdDSA, 'not a key algorithm EdDSA'];
        yield 'EdDSA with X25519, which is not for signing' => [
            ['crv' => 'X25519'] + self::OKP_ED25519_KEY,
            SignatureAlgorithmEnum::EdDSA,
            'not a key algorithm EdDSA',
        ];
        yield 'none' => [self::EC_P256_KEY, SignatureAlgorithmEnum::none, 'not a key algorithm none'];
    }


    /**
     * @param array<string,mixed> $jwk
     */
    #[DataProvider('unfittingKeyProvider')]
    public function testRefusesAKeyTheAlgorithmCanNotBeUsedWith(
        array $jwk,
        SignatureAlgorithmEnum $algorithm,
        string $message,
    ): void {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage($message);

        $this->sut()->enforceKeyFitsSignatureAlgorithm($jwk, $algorithm, 'jwk');
    }


    /**
     * @return \Iterator<string, array{string, int}>
     */
    public static function modulusProvider(): \Iterator
    {
        yield '2048 bits' => [self::modulusOfBits(2048), 2048];
        yield '2047 bits' => [self::modulusOfBits(2047), 2047];
        yield '2041 bits' => [self::modulusOfBits(2041), 2041];
        yield '2040 bits' => [self::modulusOfBits(2040), 2040];
        yield '4096 bits' => [self::modulusOfBits(4096), 4096];
        yield '2048 bits with a leading zero octet' => [self::modulusOfBits(2048, "\0"), 2048];
        yield '2047 bits with two leading zero octets' => [self::modulusOfBits(2047, "\0\0"), 2047];
        yield 'one bit' => ['AQ', 1];
        yield 'zero' => ['AA', 0];
    }


    #[DataProvider('modulusProvider')]
    public function testCanMeasureTheModulus(string $modulus, int $bits): void
    {
        $this->assertSame(
            $bits,
            $this->sut()->getRsaModulusBitLength(['kty' => 'RSA', 'n' => $modulus, 'e' => 'AQAB']),
        );
    }


    /**
     * @return \Iterator<string, array{mixed}>
     */
    public static function unreadableModulusProvider(): \Iterator
    {
        yield 'none' => [null];
        yield 'not a string' => [2048];
        yield 'base64 rather than base64url' => ['ab+/'];
        yield 'a length base64 can not have' => ['AAAAA'];
    }


    #[DataProvider('unreadableModulusProvider')]
    public function testRefusesAModulusItCanNotRead(mixed $modulus): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('no base64url encoded RSA modulus');

        $this->sut()->getRsaModulusBitLength(['kty' => 'RSA', 'n' => $modulus, 'e' => 'AQAB']);
    }
}
