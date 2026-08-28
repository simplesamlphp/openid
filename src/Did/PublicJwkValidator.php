<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Did;

use SimpleSAML\OpenID\Exceptions\DidException;

/**
 * Validates that a JWK taken from a DID document carries public key material only, and that its members agree
 * with one another.
 *
 * Membership is decided by an allowlist rather than by rejecting known private member names. DID Core forbids
 * private key material in a DID document, and an allowlist fails closed for members nobody has thought of yet,
 * which a denylist does not.
 *
 * @see https://www.w3.org/TR/did-core/#verification-material
 * @see \SimpleSAML\Test\OpenID\Did\PublicJwkValidatorTest
 */
class PublicJwkValidator
{
    /**
     * Members permitted regardless of key type.
     */
    protected const COMMON_MEMBERS = [
        'kty',
        'kid',
        'alg',
        'use',
        'key_ops',
        'x5u',
        'x5c',
        'x5t',
        'x5t#S256',
    ];

    /**
     * Public members permitted for each supported key type, and required unless listed as optional below.
     */
    protected const MEMBERS_BY_KEY_TYPE = [
        'EC' => ['crv', 'x', 'y'],
        'OKP' => ['crv', 'x'],
        'RSA' => ['n', 'e'],
    ];

    /**
     * Members which, where present, must carry a string. `alg` is checked separately, against the key itself.
     */
    protected const STRING_MEMBERS = [
        'kid',
        'use',
        'x5u',
        'x5t',
        'x5t#S256',
    ];

    /**
     * Members which, where present, must carry an array of strings.
     */
    protected const STRING_LIST_MEMBERS = [
        'key_ops',
        'x5c',
    ];

    /**
     * Byte length the x and y coordinates must decode to, per curve.
     */
    protected const COORDINATE_LENGTHS_BY_CURVE = [
        'Ed25519' => 32,
        'Ed448' => 57,
        'P-256' => 32,
        'P-384' => 48,
        'P-521' => 66,
        'X25519' => 32,
        'X448' => 56,
        'secp256k1' => 32,
    ];

    /**
     * Members carrying base64url encoded key material.
     */
    protected const KEY_MATERIAL_MEMBERS = [
        'e',
        'n',
        'x',
        'y',
    ];

    /**
     * Curves each key type may declare.
     */
    protected const CURVES_BY_KEY_TYPE = [
        'EC' => ['P-256', 'P-384', 'P-521', 'secp256k1'],
        'OKP' => ['Ed25519', 'Ed448', 'X25519', 'X448'],
    ];

    /**
     * Algorithms requiring a symmetric key, or no key at all. Since only EC, OKP and RSA keys are accepted
     * here, none of these can ever legitimately appear, and treating them as merely unrecognised would let a
     * MAC algorithm ride along on a public key.
     */
    protected const NON_ASYMMETRIC_ALGORITHMS = [
        'A128GCMKW',
        'A128KW',
        'A192GCMKW',
        'A192KW',
        'A256GCMKW',
        'A256KW',
        'HS256',
        'HS384',
        'HS512',
        'PBES2-HS256+A128KW',
        'PBES2-HS384+A192KW',
        'PBES2-HS512+A256KW',
        'dir',
        'none',
    ];

    /**
     * Algorithms mapped to the key types, and where applicable the curves, they may be declared with. A null
     * curve list means the constraint is on the key type alone. Algorithms outside this map are left alone,
     * since constraining an algorithm nobody here recognises would be guesswork.
     */
    protected const ALGORITHM_CONSTRAINTS = [
        'ECDH-ES' => [['EC', 'OKP'], ['P-256', 'P-384', 'P-521', 'X25519', 'X448', 'secp256k1']],
        'ECDH-ES+A128KW' => [['EC', 'OKP'], ['P-256', 'P-384', 'P-521', 'X25519', 'X448', 'secp256k1']],
        'ECDH-ES+A192KW' => [['EC', 'OKP'], ['P-256', 'P-384', 'P-521', 'X25519', 'X448', 'secp256k1']],
        'ECDH-ES+A256KW' => [['EC', 'OKP'], ['P-256', 'P-384', 'P-521', 'X25519', 'X448', 'secp256k1']],
        'ES256' => [['EC'], ['P-256']],
        'ES256K' => [['EC'], ['secp256k1']],
        'ES384' => [['EC'], ['P-384']],
        'ES512' => [['EC'], ['P-521']],
        'EdDSA' => [['OKP'], ['Ed25519', 'Ed448']],
        'PS256' => [['RSA'], null],
        'PS384' => [['RSA'], null],
        'PS512' => [['RSA'], null],
        'RS256' => [['RSA'], null],
        'RS384' => [['RSA'], null],
        'RS512' => [['RSA'], null],
        'RSA-OAEP' => [['RSA'], null],
        'RSA-OAEP-256' => [['RSA'], null],
        'RSA-OAEP-384' => [['RSA'], null],
        'RSA-OAEP-512' => [['RSA'], null],
        'RSA1_5' => [['RSA'], null],
    ];


    /**
     * @param array<array-key, mixed> $jwk
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function validate(array $jwk): void
    {
        $keyType = $this->requireStringMember($jwk, 'kty');

        if (!array_key_exists($keyType, self::MEMBERS_BY_KEY_TYPE)) {
            throw new DidException(sprintf('Unsupported JWK key type: %s.', $keyType));
        }

        $typeMembers = self::MEMBERS_BY_KEY_TYPE[$keyType];
        $allowedMembers = array_merge(self::COMMON_MEMBERS, $typeMembers);

        foreach (array_keys($jwk) as $member) {
            if (!in_array($member, $allowedMembers, true)) {
                throw new DidException(
                    sprintf(
                        'JWK member "%s" is not permitted for key type %s. A DID document must not carry ' .
                        'private key material.',
                        (string)$member,
                        $keyType,
                    ),
                );
            }
        }

        foreach ($typeMembers as $member) {
            $this->requireStringMember($jwk, $member);
        }

        foreach (self::STRING_MEMBERS as $member) {
            if (array_key_exists($member, $jwk)) {
                $this->requireStringMember($jwk, $member);
            }
        }

        foreach (self::STRING_LIST_MEMBERS as $member) {
            if (array_key_exists($member, $jwk)) {
                $this->requireStringListMember($jwk, $member);
            }
        }

        $this->validateCurve($jwk, $keyType);
        $this->validateAlgorithm($jwk, $keyType);
        $this->validateKeyMaterial($jwk);
    }


    /**
     * @param array<array-key, mixed> $jwk
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function validateKeyMaterial(array $jwk): void
    {
        $curve = $jwk['crv'] ?? null;
        $curve = is_string($curve) ? $curve : null;

        $coordinateLength = $curve === null ? null : (self::COORDINATE_LENGTHS_BY_CURVE[$curve] ?? null);

        foreach (self::KEY_MATERIAL_MEMBERS as $member) {
            if (!array_key_exists($member, $jwk)) {
                continue;
            }

            $decoded = $this->decodeBase64Url($this->requireStringMember($jwk, $member), $member);

            // Only the coordinates have a length their curve fixes; RSA moduli and exponents vary.
            if ($coordinateLength === null || !in_array($member, ['x', 'y'], true)) {
                continue;
            }

            if (strlen($decoded) !== $coordinateLength) {
                throw new DidException(
                    sprintf(
                        'JWK member "%s" must decode to %d bytes for curve %s, but it decoded to %d.',
                        $member,
                        $coordinateLength,
                        $curve,
                        strlen($decoded),
                    ),
                );
            }
        }
    }


    /**
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function decodeBase64Url(string $value, string $member): string
    {
        $error = sprintf('JWK member "%s" must be base64url encoded.', $member);

        if (preg_match('/^[A-Za-z0-9_-]+$/', $value) !== 1) {
            throw new DidException($error);
        }

        $base64 = strtr($value, '-_', '+/');
        $remainder = strlen($base64) % 4;

        if ($remainder !== 0) {
            $base64 .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode($base64, true);

        if ($decoded === false) {
            throw new DidException($error);
        }

        return $decoded;
    }


    /**
     * @param array<array-key, mixed> $jwk
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function validateCurve(array $jwk, string $keyType): void
    {
        if (!array_key_exists($keyType, self::CURVES_BY_KEY_TYPE)) {
            return;
        }

        $curve = $this->requireStringMember($jwk, 'crv');
        $curves = self::CURVES_BY_KEY_TYPE[$keyType];

        if (!in_array($curve, $curves, true)) {
            throw new DidException(sprintf('Curve "%s" is not valid for key type %s.', $curve, $keyType));
        }
    }


    /**
     * @param array<array-key, mixed> $jwk
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function validateAlgorithm(array $jwk, string $keyType): void
    {
        if (!array_key_exists('alg', $jwk)) {
            return;
        }

        $algorithm = $this->requireStringMember($jwk, 'alg');

        if (in_array($algorithm, self::NON_ASYMMETRIC_ALGORITHMS, true)) {
            throw new DidException(
                sprintf(
                    'JWK algorithm "%s" needs a symmetric key or no key at all, so it cannot belong to a key ' .
                    'of type %s.',
                    $algorithm,
                    $keyType,
                ),
            );
        }

        if (!array_key_exists($algorithm, self::ALGORITHM_CONSTRAINTS)) {
            return;
        }

        [$expectedKeyTypes, $expectedCurves] = self::ALGORITHM_CONSTRAINTS[$algorithm];

        if (!in_array($keyType, $expectedKeyTypes, true)) {
            throw new DidException(
                sprintf(
                    'JWK algorithm "%s" requires key type %s, but the key is %s.',
                    $algorithm,
                    implode(' or ', $expectedKeyTypes),
                    $keyType,
                ),
            );
        }

        if ($expectedCurves === null) {
            return;
        }

        $curve = $this->requireStringMember($jwk, 'crv');

        if (!in_array($curve, $expectedCurves, true)) {
            throw new DidException(
                sprintf('JWK algorithm "%s" is not valid for curve "%s".', $algorithm, $curve),
            );
        }
    }


    /**
     * @param array<array-key, mixed> $jwk
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function requireStringListMember(array $jwk, string $member): void
    {
        $value = $jwk[$member] ?? null;
        $error = sprintf('JWK member "%s" must be a non-empty array of strings.', $member);

        if (!is_array($value) || !array_is_list($value) || $value === []) {
            throw new DidException($error);
        }

        foreach ($value as $entry) {
            if (!is_string($entry) || $entry === '') {
                throw new DidException($error);
            }
        }
    }


    /**
     * @param array<array-key, mixed> $jwk
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    protected function requireStringMember(array $jwk, string $member): string
    {
        $value = $jwk[$member] ?? null;

        if (!is_string($value) || $value === '') {
            throw new DidException(sprintf('JWK member "%s" must be a non-empty string.', $member));
        }

        return $value;
    }
}
