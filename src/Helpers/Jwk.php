<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Helpers;

use SimpleSAML\OpenID\Algorithms\SignatureAlgorithmEnum;
use SimpleSAML\OpenID\Exceptions\InvalidValueException;

/**
 * Checks on a JSON Web Key received from someone else, such as the "jwk" header parameter of a proof of
 * possession, which names the key its own signature is to be verified with.
 *
 * @see \SimpleSAML\Test\OpenID\Helpers\JwkTest
 */
class Jwk
{
    /**
     * The members RFC 7517 sections 4.1 to 4.9 define for a key of any type. None of them is key material.
     */
    public const COMMON_MEMBERS = [
        'kty',
        'use',
        'key_ops',
        'alg',
        'kid',
        'x5u',
        'x5c',
        'x5t',
        'x5t#S256',
    ];

    /**
     * The members which make up the public key, per asymmetric key type: RFC 7518 section 6.2.1 for "EC" ("crv",
     * "x", "y"), section 6.3.1 for "RSA" ("n", "e"), and RFC 8037 section 2 for "OKP" ("crv", "x"). With "kty",
     * these are the members RFC 7638 section 3.2 (and RFC 8037 section 2 for "OKP") has a JWK Thumbprint computed
     * over.
     *
     * @var array<string,list<string>>
     */
    public const PUBLIC_KEY_MEMBERS_BY_KEY_TYPE = [
        'EC' => ['crv', 'x', 'y'],
        'RSA' => ['n', 'e'],
        'OKP' => ['crv', 'x'],
    ];

    /**
     * RFC 7518 section 3.3 for RS256, RS384 and RS512, and section 3.5 for PS256, PS384 and PS512: "A key of size
     * 2048 bits or larger MUST be used".
     */
    public const MIN_RSA_MODULUS_BITS = 2048;

    /**
     * The largest RSA modulus taken, whatever the algorithm. Not a limit a specification sets: a bound on the
     * work a key sent by anyone can cause, since verifying a signature costs an exponentiation modulo "n" before
     * anything is known about the sender. Twice the 4096 bits of the largest keys in common use.
     */
    public const MAX_RSA_MODULUS_BITS = 8192;

    /**
     * The largest RSA public exponent taken, in bits; for the same reason, the exponentiation costing a step per
     * bit of it. The exponent in common use, 65537, has 17.
     */
    public const MAX_RSA_EXPONENT_BITS = 256;

    /**
     * An RSA modulus with a prime factor below this is refused: a factor that small is found by trial division
     * at once, and with it the private key. Checked with one gcd against the product of the odd primes below it.
     */
    public const MIN_RSA_PRIME_FACTOR = 752;


    /**
     * The product of the odd primes below MIN_RSA_PRIME_FACTOR, computed once.
     */
    protected static ?\GMP $smallOddPrimesProduct = null;


    /**
     * The curves this library can verify a signature on, per key type, each with the length in octets of what
     * the key's "x" (and for "EC", "y") encodes. For "EC", RFC 7518 sections 6.2.1.2 and 6.2.1.3: "The length of
     * this octet string MUST be the full size of a coordinate for the curve specified in the "crv" parameter."
     * For "OKP", "x" is the public key itself (RFC 8037 section 2), of 32 octets for Ed25519 and 57 for Ed448
     * (RFC 8032 section 7).
     *
     * @var array<string,array<string,int>>
     */
    protected const KEY_OCTETS_BY_CURVE = [
        'EC' => [
            'P-256' => 32,
            'P-384' => 48,
            'P-521' => 66,
        ],
        'OKP' => [
            'Ed25519' => 32,
            'Ed448' => 57,
        ],
    ];

    /**
     * The curve each ECDSA algorithm is defined on, RFC 7518 section 3.1: "ECDSA using P-256 and SHA-256", and
     * so on.
     */
    protected const CURVE_BY_ECDSA_ALGORITHM = [
        'ES256' => 'P-256',
        'ES384' => 'P-384',
        'ES512' => 'P-521',
    ];

    /**
     * The key subtypes RFC 8037 section 3.1 defines for EdDSA.
     */
    protected const EDDSA_CURVES = ['Ed25519', 'Ed448'];

    /**
     * The base64url alphabet of RFC 4648 section 5 without padding, which RFC 7518 has the coordinates and the
     * RSA modulus and exponent encoded in.
     */
    protected const BASE64URL_PATTERN = '/^[A-Za-z0-9_-]+\z/';


    /**
     * Enforces that the value is a JWK holding a public key of an asymmetric key type, in its one canonical
     * representation, and nothing else; returns it as given.
     *
     * The members are checked against an allowlist: those of COMMON_MEMBERS, plus the public key members of the
     * key's type. An allowlist rather than a list of private members to refuse, because the refusal has to hold
     * for a member nobody thought to list; a key carrying a member no specification here defines is refused
     * along with it, which RFC 7517 section 4 would have a recipient ignore instead ("if not understood by
     * implementations encountering them, they MUST be ignored"). A symmetric ("oct") key is refused: it is not
     * a public key at all, and its one member is the secret.
     *
     * The key material has to be in the representation RFC 7518 defines, since RFC 7638 section 7 has "JWK
     * Thumbprint values can only be relied upon to be unique for a given key if the implementation also
     * validates that the correct representation of the key is used": a curve this library knows ("EC": P-256,
     * P-384, P-521; "OKP": Ed25519, Ed448), its "x" and "y" exactly the octets the curve's coordinates take, an
     * RSA "n" and "e" in the minimum number of octets, and each of them the one base64url string its octets
     * encode to, so neither a leading zero octet nor stray bits after the last octet give the same key a second
     * spelling. An RSA key has to be one a signature proves possession of, at a bounded cost: see
     * enforceUsableRsaKey(). Which curve may sign with which algorithm, and the smallest RSA modulus, are
     * enforceKeyFitsSignatureAlgorithm()'s to say.
     *
     * The refusal does not quote the members it found: as far as this library knows they are attacker-chosen
     * text, or the private key itself.
     *
     * @return array<string,mixed>
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function enforcePublicKey(mixed $jwk, ?string $context = null): array
    {
        $suffix = is_null($context) ? '' : sprintf(' (%s)', $context);

        if (!is_array($jwk) || $jwk === [] || array_is_list($jwk)) {
            throw new InvalidValueException('JWK is not a JSON object' . $suffix . '.');
        }

        $keyType = $jwk['kty'] ?? null;

        if (!is_string($keyType) || !array_key_exists($keyType, self::PUBLIC_KEY_MEMBERS_BY_KEY_TYPE)) {
            throw new InvalidValueException(
                'JWK is not of an asymmetric key type: EC, RSA or OKP' . $suffix . '.',
            );
        }

        $keyMembers = self::PUBLIC_KEY_MEMBERS_BY_KEY_TYPE[$keyType];

        $memberNames = array_map(strval(...), array_keys($jwk));
        $unexpectedMembers = array_diff($memberNames, self::COMMON_MEMBERS, $keyMembers);

        if ($unexpectedMembers !== []) {
            throw new InvalidValueException(
                sprintf(
                    'JWK carries members which are not part of a public %s key%s.',
                    $keyType,
                    $suffix,
                ),
            );
        }

        foreach ($keyMembers as $member) {
            $value = $jwk[$member] ?? null;

            if (!is_string($value) || $value === '') {
                throw new InvalidValueException(
                    sprintf('JWK has no %s member, or it is not a non-empty string%s.', $member, $suffix),
                );
            }
        }

        if ($keyType === 'RSA') {
            $this->enforceUsableRsaKey(
                $this->decodeCanonicalBase64Url($jwk['n'], 'n', $suffix),
                $this->decodeCanonicalBase64Url($jwk['e'], 'e', $suffix),
                $suffix,
            );

            /** @var array<string,mixed> $jwk */
            return $jwk;
        }

        $curve = $jwk['crv'];
        $keyOctets = is_string($curve) ? (self::KEY_OCTETS_BY_CURVE[$keyType][$curve] ?? null) : null;

        if (is_null($keyOctets)) {
            throw new InvalidValueException(
                sprintf('JWK is not on a curve this library knows for a %s key%s.', $keyType, $suffix),
            );
        }

        foreach (array_diff($keyMembers, ['crv']) as $member) {
            $octets = $this->decodeCanonicalBase64Url($jwk[$member], $member, $suffix);

            if (strlen($octets) !== $keyOctets) {
                throw new InvalidValueException(
                    sprintf(
                        'JWK member %s is not the %d octets a %s key takes%s.',
                        $member,
                        $keyOctets,
                        (string)$curve,
                        $suffix,
                    ),
                );
            }
        }

        /** @var array<string,mixed> $jwk */
        return $jwk;
    }


    /**
     * The members of a public key RFC 7638 section 3.2 calls required: "kty" and the key material, without the
     * metadata of COMMON_MEMBERS. A key's "key_ops", "use" or "alg" describe what its holder does with it, and a
     * private signing key's "key_ops": ["sign"] would make its public form unusable for verifying.
     *
     * @param array<string,mixed> $publicJwk One enforcePublicKey() accepted.
     * @return array<string,mixed>
     */
    public function getRequiredMembers(array $publicJwk): array
    {
        $keyType = $publicJwk['kty'] ?? null;
        $keyMembers = is_string($keyType) ? (self::PUBLIC_KEY_MEMBERS_BY_KEY_TYPE[$keyType] ?? []) : [];

        return array_intersect_key($publicJwk, array_flip(['kty', ...$keyMembers]));
    }


    /**
     * Enforces that a public key, one enforcePublicKey() accepted, is one the algorithm can be used with: an RSA
     * key of at least MIN_RSA_MODULUS_BITS for RS256 to PS512, an EC key on the curve RFC 7518 section 3.1 pairs
     * the ECDSA algorithm with, and an OKP key of an EdDSA subtype (RFC 8037 section 3.1) for EdDSA. "none" fits
     * no key.
     *
     * A key's own "alg" member is not looked at here; the signature verification refuses a key whose "alg" names
     * another algorithm.
     *
     * @param array<string,mixed> $publicJwk
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function enforceKeyFitsSignatureAlgorithm(
        array $publicJwk,
        SignatureAlgorithmEnum $signatureAlgorithm,
        ?string $context = null,
    ): void {
        $suffix = is_null($context) ? '' : sprintf(' (%s)', $context);
        $keyType = $publicJwk['kty'] ?? null;
        $curve = $publicJwk['crv'] ?? null;

        switch ($signatureAlgorithm) {
            case SignatureAlgorithmEnum::RS256:
            case SignatureAlgorithmEnum::RS384:
            case SignatureAlgorithmEnum::RS512:
            case SignatureAlgorithmEnum::PS256:
            case SignatureAlgorithmEnum::PS384:
            case SignatureAlgorithmEnum::PS512:
                if ($keyType !== 'RSA') {
                    break;
                }

                $modulusBits = $this->getRsaModulusBitLength($publicJwk, $context);

                if ($modulusBits < self::MIN_RSA_MODULUS_BITS) {
                    throw new InvalidValueException(
                        sprintf(
                            'RSA key of %d bits is shorter than the %d bits %s requires%s.',
                            $modulusBits,
                            self::MIN_RSA_MODULUS_BITS,
                            $signatureAlgorithm->value,
                            $suffix,
                        ),
                    );
                }

                return;
            case SignatureAlgorithmEnum::ES256:
            case SignatureAlgorithmEnum::ES384:
            case SignatureAlgorithmEnum::ES512:
                if ($keyType === 'EC' && $curve === self::CURVE_BY_ECDSA_ALGORITHM[$signatureAlgorithm->value]) {
                    return;
                }

                break;
            case SignatureAlgorithmEnum::EdDSA:
                if ($keyType === 'OKP' && in_array($curve, self::EDDSA_CURVES, true)) {
                    return;
                }

                break;
            case SignatureAlgorithmEnum::none:
                break;
        }

        throw new InvalidValueException(
            sprintf('JWK is not a key algorithm %s can be used with%s.', $signatureAlgorithm->value, $suffix),
        );
    }


    /**
     * The length in bits of the modulus "n" of an RSA public key: the position of its most significant set bit.
     * Leading zero octets do not count; enforcePublicKey() refuses a key that has them.
     *
     * @param array<string,mixed> $publicJwk
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function getRsaModulusBitLength(array $publicJwk, ?string $context = null): int
    {
        $suffix = is_null($context) ? '' : sprintf(' (%s)', $context);
        $modulus = $publicJwk['n'] ?? null;

        $octets = is_string($modulus) ? $this->decodeBase64Url($modulus) : null;

        if (is_null($octets)) {
            throw new InvalidValueException('JWK has no base64url encoded RSA modulus' . $suffix . '.');
        }

        $octets = ltrim($octets, "\0");

        if ($octets === '') {
            return 0;
        }

        $bits = (strlen($octets) - 1) * 8;
        $mostSignificantOctet = ord($octets[0]);

        while ($mostSignificantOctet > 0) {
            ++$bits;
            $mostSignificantOctet >>= 1;
        }

        return $bits;
    }


    /**
     * Enforces that an RSA public key, given as the octets of "n" and "e", is in the representation RFC 7518
     * defines and is one a signature can prove possession of at a bounded cost.
     *
     * Representation: neither integer has a leading zero octet (RFC 7518 section 2, Base64urlUInt: "The octet
     * sequence MUST utilize the minimum number of octets needed to represent the value.").
     *
     * Possession: the exponent is odd and at least 3. With an exponent of 1 the "signature" operation is the
     * identity, so a signature for that key can be computed from public data, and the proof shows nothing; an
     * even exponent is no RSA exponent at all, sharing a factor with the even (p - 1)(q - 1). The modulus, a
     * product of two odd primes, is odd, is not a perfect power (the root of n = p^2 gives p), and has no prime
     * factor below MIN_RSA_PRIME_FACTOR: each of those would give anyone the private key.
     *
     * Not checked, deliberately: that the modulus is not prime (whose private exponent is e^-1 mod (n - 1)), or
     * otherwise easy to factor. A primality test costs tens to hundreds of milliseconds for a large modulus, more
     * than verifying the signature by two orders of magnitude, so testing every proof would hand anyone sending
     * prime moduli a lever on the verifier's time. And such a key weakens only the binding to itself: a token is
     * bound to the key its client presented, so only a client that chose a weak key holds tokens it exposes.
     *
     * Cost: the modulus has at most MAX_RSA_MODULUS_BITS and the exponent at most MAX_RSA_EXPONENT_BITS. (The
     * exponent is then below any modulus enforceKeyFitsSignatureAlgorithm() lets an algorithm use.) The checks
     * here take well under a millisecond.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    protected function enforceUsableRsaKey(string $modulus, string $exponent, string $suffix): void
    {
        foreach (['n' => $modulus, 'e' => $exponent] as $member => $octets) {
            if ($octets === '' || $octets[0] === "\0") {
                throw new InvalidValueException(
                    sprintf('JWK member %s is not in the minimum number of octets%s.', $member, $suffix),
                );
            }
        }

        if (strlen($modulus) > intdiv(self::MAX_RSA_MODULUS_BITS, 8) || (ord($modulus[-1]) & 1) === 0) {
            throw new InvalidValueException(
                sprintf(
                    'JWK member n is not an odd RSA modulus of at most %d bits%s.',
                    self::MAX_RSA_MODULUS_BITS,
                    $suffix,
                ),
            );
        }

        if (
            strlen($exponent) > intdiv(self::MAX_RSA_EXPONENT_BITS, 8) ||
            (ord($exponent[-1]) & 1) === 0 ||
            (strlen($exponent) === 1 && ord($exponent) < 3)
        ) {
            throw new InvalidValueException(
                sprintf(
                    'JWK member e is not an odd RSA exponent of at least 3 and at most %d bits%s.',
                    self::MAX_RSA_EXPONENT_BITS,
                    $suffix,
                ),
            );
        }

        $modulusNumber = gmp_import($modulus);

        if (
            gmp_perfect_power($modulusNumber) ||
            gmp_cmp(gmp_gcd($modulusNumber, self::smallOddPrimesProduct()), 1) !== 0
        ) {
            throw new InvalidValueException(
                sprintf(
                    'JWK member n is not an RSA modulus whose factors are secret: it is a perfect power or has a ' .
                    'prime factor below %d%s.',
                    self::MIN_RSA_PRIME_FACTOR,
                    $suffix,
                ),
            );
        }
    }


    protected static function smallOddPrimesProduct(): \GMP
    {
        if (self::$smallOddPrimesProduct instanceof \GMP) {
            return self::$smallOddPrimesProduct;
        }

        $product = gmp_init(1);

        for ($candidate = 3; $candidate < self::MIN_RSA_PRIME_FACTOR; $candidate += 2) {
            for ($divisor = 3; $divisor * $divisor <= $candidate; $divisor += 2) {
                if ($candidate % $divisor === 0) {
                    continue 2;
                }
            }

            $product = gmp_mul($product, $candidate);
        }

        return self::$smallOddPrimesProduct = $product;
    }


    /**
     * The octets a member encodes, provided the member is the one base64url string (RFC 4648 section 5, without
     * padding) that encodes them: decoding and encoding again has to give the member back. That refuses the
     * spellings a lenient decoder maps to the same octets, such as set bits after the last whole octet.
     *
     * @return non-empty-string
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    protected function decodeCanonicalBase64Url(mixed $value, string $member, string $suffix): string
    {
        $octets = is_string($value) ? $this->decodeBase64Url($value) : null;

        if (is_null($octets) || $octets === '' || $this->encodeBase64Url($octets) !== $value) {
            throw new InvalidValueException(
                sprintf('JWK member %s is not in canonical base64url encoding%s.', $member, $suffix),
            );
        }

        return $octets;
    }


    /**
     * Null for a value outside the base64url alphabet, or of a length no encoding has.
     */
    protected function decodeBase64Url(string $value): ?string
    {
        if (preg_match(self::BASE64URL_PATTERN, $value) !== 1) {
            return null;
        }

        $base64 = strtr($value, '-_', '+/');
        $remainder = strlen($base64) % 4;

        if ($remainder !== 0) {
            $base64 .= str_repeat('=', 4 - $remainder);
        }

        $octets = base64_decode($base64, true);

        return $octets === false ? null : $octets;
    }


    protected function encodeBase64Url(string $octets): string
    {
        return rtrim(strtr(base64_encode($octets), '+/', '-_'), '=');
    }
}
