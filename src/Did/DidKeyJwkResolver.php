<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Did;

use SimpleSAML\OpenID\Exceptions\DidException;
use SimpleSAML\OpenID\Helpers;

/**
 * Utility class for resolving DID Key values to JWK format.
 * Based on the W3C DID Key specification: https://w3c-ccg.github.io/did-key-spec/
 */
class DidKeyJwkResolver
{
    public function __construct(
        protected readonly Helpers $helpers,
    ) {
    }

    /**
     * Extract JWK from a did:key value.
     *
     * @param string $didKey The did:key value (e.g., did:key:z2dmzD81cgPx8Vki7JbuuMmFYrWPgYoytykUZ3eyqht1j9Kbp7R1FUvzP1s9pLTKP21oYQNWMJFzgVGWYb5WmD3ngVmjMeTABs9MjYUaRfzTWg9dLdPw6o16UeakmtE7tHDMug3XgcJptPxRYuwFdVJXa6KAMUBhkmouMZisDJYMGbaGAp)
     * @return mixed[] The JWK representation of the key
     * @throws \SimpleSAML\OpenID\Exceptions\DidException If the did:key format is invalid or unsupported
     */
    public function extractJwkFromDidKey(string $didKey): array
    {
        // Validate the did:key format
        if (!str_starts_with($didKey, 'did:key:')) {
            throw new DidException('Invalid did:key format. Must start with "did:key:"');
        }

        // Extract the multibase-encoded public key
        $multibaseKey = substr($didKey, 8); // Remove 'did:key:'

        // Check if it's a base58btc encoded key (starts with 'z')
        if (!str_starts_with($multibaseKey, 'z')) {
            throw new DidException(
                'Unsupported multibase encoding. Only base58btc (z-prefixed) is currently supported.',
            );
        }

        // Remove the multibase prefix ('z')
        $base58Key = substr($multibaseKey, 1);

        try {
            // Decode the base58 key
            $decodedKey = $this->base58BtcDecode($base58Key);

            // Get the multicodec identifier and its length
            [$multicodecIdentifier, $prefixLength] = $this->varintDecode($decodedKey);

            // Extract the actual key bytes (skip the multicodec bytes)
            $keyBytes = substr($decodedKey, $prefixLength);

            // Determine the key type based on the multicodec identifier
            // See: https://github.com/multiformats/multicodec/blob/master/table.csv
            return match ($multicodecIdentifier) {
                // Ed25519 public key (0xed in multicodec table)
                0xed => $this->createEd25519Jwk($keyBytes),
                // X25519 public key (0xec in multicodec table)
                0xec => $this->createX25519Jwk($keyBytes),
                // Secp256k1 public key (multicodec 0xe7)
                // The original code had `0xe70, 0x01e7`. If the varint bytes are `\xe7\x01`,
                // varintDecode will return 231 (0xe7). If the intended multicodec value is 0x01e7 (487),
                // its varint encoding would be different (e.g., \xDF\x03).
                // Assuming the multicodec code itself is 0xe7 (231).
                0xe7 => $this->createSecp256k1Jwk($keyBytes),
                // P-256 (NIST) public key (multicodec 0x1200 for uncompressed, 0x1201 for compressed - typically
                // 0x1200 used with JWK). Also adding 0x1102 as another possible identifier for P-256 keys
                0x1200, 0x1201, 0x1102 => $this->createP256Jwk($keyBytes),
                // P-384 (NIST) public key (multicodec 0x1202)
                0x1202 => $this->createP384Jwk($keyBytes),
                // P-521 (NIST) public key (multicodec 0x1203)
                0x1203 => $this->createP521Jwk($keyBytes),
                // JSON JWK public key (0xeb51 in multicodec table)
                0xeb51 => $this->createJwkFromRawJson($keyBytes),
                default => throw new DidException(
                    sprintf('Unsupported key type with multicodec identifier: 0x%04x', $multicodecIdentifier),
                ),
            };
        } catch (\Exception $exception) {
            // It's good practice to re-throw with context, but avoid concatenating messages directly
            // if the original exception message might contain sensitive info or be too verbose.
            // Wrapping it is generally better.
            throw new DidException('Error processing did:key: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Decode a variable integer (varint) from bytes.
     *
     * This follows the multiformats varint specification where each byte uses 7 bits for the value
     * and 1 bit to indicate if there are more bytes in the varint.
     * As per the specification, varints must be encoded with the minimum number of bytes necessary.
     *
     * @see https://github.com/multiformats/unsigned-varint
     *
     * @param string $bytes Binary string containing a varint
     * @return array{0: int, 1: int} Array containing [decoded value, number of bytes consumed]
     * @throws \SimpleSAML\OpenID\Exceptions\DidException If the varint has invalid format
     */
    public function varintDecode(string $bytes): array
    {
        if ($bytes === '') {
            throw new DidException('Invalid varint: input is empty');
        }

        $result = 0;
        $shift = 0;
        $bytesRead = 0;

        for ($i = 0; $i < strlen($bytes); ++$i) {
            $byte = ord($bytes[$i]);
            ++$bytesRead;

            // Implementations typically support up to 10 bytes for u64.
            // This implementation limits to 9 bytes, fitting PHP_INT_MAX (2^63-1).
            if ($bytesRead > 9) {
                throw new DidException('Invalid varint: too many bytes (max 9 for this implementation).');
            }

            $valuePart = $byte & 0x7F;

            // Add value part to result.
            // PHP integers are signed 64-bit. Max shift is 7*8=56 for the 9th byte.
            // (X << 56) is safe. Result should not overflow PHP_INT_MAX for valid multicodec IDs.
            $result |= ($valuePart << $shift);

            if (($byte & 0x80) === 0) { // This is the last byte in the varint sequence
                // Check for overlong encoding (violates minimality constraint):
                // 1. If the varint is multi-byte (bytesRead > 1) and the last byte is 0x00.
                //    This covers cases like `\x81\x00` for 1, or `\x80\x00` for 0.
                // 2. The value 0 must be encoded as a single `\x00`. If result is 0 and bytesRead > 1,
                //    it's an overlong encoding of 0 (e.g. `\x80\x00`), which is caught by the first condition.
                if ($byte === 0x00 && $bytesRead > 1) {
                    throw new DidException('Invalid varint: overlong encoding (minimality constraint violated).');
                }

                return [$result, $bytesRead];
            }

            $shift += 7;
            // Check if the next shift would overflow standard integer types (more relevant for fixed-size integers).
            // For PHP's arbitrary precision integers (when they become floats/GMP objects), this is less of an issue,
            // but for multicodec IDs, values are small and fit in standard integers.
        }

        // If the loop finishes, it means the last byte had its MSB set, indicating an incomplete sequence.
        throw new DidException('Invalid varint: incomplete sequence (unterminated).');
    }

    /**
     * Decode a base58 encoded string.
     */
    // In SimpleSAML\OpenID\Did\DidKeyJwkResolver.php
    public function base58BtcDecode(string $base58encodedString): string
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base = strlen($alphabet);

//        if ($base58encodedString === 'ABnLTmg5e1PhaB9S2qAvL9L3Q') {
//            error_log("[base58BtcDecode] Input: " . $base58encodedString);
//            error_log("[base58BtcDecode] Alphabet: " . $alphabet);
//            error_log("[base58BtcDecode] Alphabet Length (base): " . $base);
//        }

        $num = gmp_init(0);
        for ($i = 0; $i < strlen($base58encodedString); ++$i) {
            $char = $base58encodedString[$i];
            $pos = strpos($alphabet, $char);

            if ($pos === false) {
                throw new \InvalidArgumentException('Invalid character in base58 string: ' . $char);
            }

//            if ($base58encodedString === 'ABnLTmg5e1PhaB9S2qAvL9L3Q') {
//                error_log("[base58BtcDecode] char: '{$char}', pos: {$pos}");
//            }

            $num = gmp_add(gmp_mul($num, $base), $pos);
        }

//        if ($base58encodedString === 'ABnLTmg5e1PhaB9S2qAvL9L3Q') {
//            error_log("[base58BtcDecode] Calculated GMP num (hex): " . gmp_strval($num, 16));
//        }

        // ... rest of the method ...
        $result = '';
        /** @phpstan-ignore argument.type */
        while (gmp_cmp($num, 0) > 0) {
            /** @phpstan-ignore argument.type */
            [$numQuotient, $remainder] = gmp_div_qr($num, 256); // Use a different variable for quotient
            $num = $numQuotient; // Reassign $num
            /** @phpstan-ignore argument.type */
            $result = chr(gmp_intval($remainder)) . $result;
        }

        // Add leading zeros
        for ($j = 0; $j < strlen($base58encodedString) && $base58encodedString[$j] === '1'; ++$j) {
            $result = "\0" . $result;
        }

        return $result;
    }


    /**
     * Create a JWK for an Ed25519 public key.
     *
     * @param string $rawKeyBytes The raw key bytes
     * @return mixed[] The JWK representation
     */
    public function createEd25519Jwk(string $rawKeyBytes): array
    {
        return [
            'kty' => 'OKP',
            'crv' => 'Ed25519',
            'x' => $this->helpers->base64Url()->encode($rawKeyBytes),
            'use' => 'sig',
        ];
    }

    /**
     * Create a JWK for an X25519 public key.
     *
     * @param string $rawKeyBytes The raw key bytes
     * @return mixed[] The JWK representation
     */
    public function createX25519Jwk(string $rawKeyBytes): array
    {
        return [
            'kty' => 'OKP',
            'crv' => 'X25519',
            'x' => $this->helpers->base64Url()->encode($rawKeyBytes),
            'use' => 'enc',
        ];
    }

    /**
     * Create a JWK for a Secp256k1 public key.
     *
     * @param string $rawKeyBytes The raw key bytes
     * @return mixed[] The JWK representation
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function createSecp256k1Jwk(string $rawKeyBytes): array
    {
        // For Secp256k1, we need to extract x and y coordinates from the compressed or uncompressed point
        $firstByte = ord($rawKeyBytes[0]);

        if ($firstByte === 0x04 && strlen($rawKeyBytes) === 65) {
            // Uncompressed point format (0x04 || x || y)
            $x = substr($rawKeyBytes, 1, 32);
            $y = substr($rawKeyBytes, 33, 32);
        } elseif (($firstByte === 0x02 || $firstByte === 0x03) && strlen($rawKeyBytes) === 33) {
            // Compressed point format - would need to decompress
            // This is complex and requires secp256k1 library support
            throw new DidException('Compressed Secp256k1 keys are not currently supported');
        } else {
            throw new DidException('Invalid Secp256k1 public key format');
        }

        return [
            'kty' => 'EC',
            'crv' => 'secp256k1',
            'x' => $this->helpers->base64Url()->encode($x),
            'y' => $this->helpers->base64Url()->encode($y),
            'use' => 'sig',
        ];
    }

    /**
     * Create a JWK for a P-256 (NIST) public key.
     *
     * @param string $rawKeyBytes The raw key bytes
     * @return mixed[] The JWK representation
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function createP256Jwk(string $rawKeyBytes): array
    {
        // Similar to Secp256k1, we need to extract x and y coordinates
        $firstByte = ord($rawKeyBytes[0]);

        if ($firstByte === 0x04 && strlen($rawKeyBytes) === 65) {
            // Uncompressed point format (0x04 || x || y)
            $x = substr($rawKeyBytes, 1, 32);
            $y = substr($rawKeyBytes, 33, 32);
        } elseif (($firstByte === 0x02 || $firstByte === 0x03) && strlen($rawKeyBytes) === 33) {
            // Compressed point format - would need to decompress
            // This is complex and requires specific library support
            throw new DidException('Compressed P-256 keys are not currently supported');
        } elseif (strlen($rawKeyBytes) === 64) {
            // Some implementations might not include the leading 0x04 byte
            // Try to interpret as raw x || y coordinates
            $x = substr($rawKeyBytes, 0, 32);
            $y = substr($rawKeyBytes, 32, 32);
        } else {
            throw new DidException('Invalid P-256 public key format');
        }

        return [
            'kty' => 'EC',
            'crv' => 'P-256',
            'x' => $this->helpers->base64Url()->encode($x),
            'y' => $this->helpers->base64Url()->encode($y),
            'use' => 'sig',
        ];
    }

    /**
     * Create a JWK for a P-384 (NIST) public key.
     *
     * @param string $rawKeyBytes The raw key bytes
     * @return mixed[] The JWK representation
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function createP384Jwk(string $rawKeyBytes): array
    {
        $firstByte = ord($rawKeyBytes[0]);

        if ($firstByte === 0x04 && strlen($rawKeyBytes) === 97) {
            // Uncompressed point format (0x04 || x || y)
            $x = substr($rawKeyBytes, 1, 48);
            $y = substr($rawKeyBytes, 49, 48);
        } else {
            throw new DidException('Invalid P-384 public key format');
        }

        return [
            'kty' => 'EC',
            'crv' => 'P-384',
            'x' => $this->helpers->base64Url()->encode($x),
            'y' => $this->helpers->base64Url()->encode($y),
            'use' => 'sig',
        ];
    }

    /**
     * Create a JWK for a P-521 (NIST) public key.
     *
     * @return mixed[] The JWK representation
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function createP521Jwk(string $rawKeyBytes): array
    {
        $firstByte = ord($rawKeyBytes[0]);

        if ($firstByte === 0x04 && strlen($rawKeyBytes) === 133) {
            // Uncompressed point format (0x04 || x || y)
            $x = substr($rawKeyBytes, 1, 66);
            $y = substr($rawKeyBytes, 67, 66);
        } else {
            throw new DidException('Invalid P-521 public key format');
        }

        return [
            'kty' => 'EC',
            'crv' => 'P-521',
            'x' => $this->helpers->base64Url()->encode($x),
            'y' => $this->helpers->base64Url()->encode($y),
            'use' => 'sig',
        ];
    }

    /**
     * Create a JWK from raw JSON data.
     *
     * Used for multicodec identifier 0xeb51, which represents a JSON object containing
     * only the required members of a JWK (RFC 7518 and RFC 7517) representing the public key.
     * Serialization is based on JCS (RFC 8785).
     *
     * @param string $rawJsonBytes The raw JSON bytes
     * @return mixed[] The JWK representation
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     */
    public function createJwkFromRawJson(string $rawJsonBytes): array
    {
        try {
            $jwk = $this->helpers->json()->decode($rawJsonBytes);

            // Validate that this is a valid JWK
            if (!is_array($jwk) || !isset($jwk['kty'])) {
                throw new DidException('Invalid JWK format: missing required "kty" property');
            }

            // For EC keys, validate required parameters
            if ($jwk['kty'] === 'EC') {
                if (!isset($jwk['crv'], $jwk['x'], $jwk['y'])) {
                    throw new DidException('Invalid EC JWK format: missing required properties');
                }
            } elseif ($jwk['kty'] === 'OKP') {
                if (!isset($jwk['crv'], $jwk['x'])) {
                    throw new DidException('Invalid OKP JWK format: missing required properties');
                }
            } elseif ($jwk['kty'] === 'RSA') {
                if (!isset($jwk['n'], $jwk['e'])) {
                    throw new DidException('Invalid RSA JWK format: missing required properties');
                }
            }

            // Default to 'sig' use if not specified
            if (!isset($jwk['use'])) {
                $jwk['use'] = 'sig';
            }

            return $jwk;
        } catch (\JsonException $jsonException) {
            throw new DidException('Failed to parse JWK JSON: ' . $jsonException->getMessage());
        }
    }
}
