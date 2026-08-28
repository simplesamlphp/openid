<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Did;

use SimpleSAML\OpenID\Exceptions\DidException;
use SimpleSAML\OpenID\Helpers;

/**
 * Utility class for resolving DID Key values to JWK format.
 * Based on the W3C DID Key specification: https://w3c-ccg.github.io/did-key-spec/
 *
 * The multibase / multicodec decoding itself lives in \SimpleSAML\OpenID\Did\MultibaseKeyDecoder. The methods
 * exposing it here remain as delegating wrappers for backward compatibility.
 *
 * @see \SimpleSAML\Test\OpenID\Did\DidKeyJwkResolverTest
 */
class DidKeyJwkResolver
{
    protected readonly MultibaseKeyDecoder $multibaseKeyDecoder;


    public function __construct(
        protected readonly Helpers $helpers,
        ?MultibaseKeyDecoder $multibaseKeyDecoder = null,
    ) {
        $this->multibaseKeyDecoder = $multibaseKeyDecoder ?? new MultibaseKeyDecoder($this->helpers);
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
     * @param string $bytes Binary string containing a varint
     * @return array{0: int, 1: int} Array containing [decoded value, number of bytes consumed]
     * @throws \SimpleSAML\OpenID\Exceptions\DidException If the varint has invalid format
     *
     * @see \SimpleSAML\OpenID\Did\MultibaseKeyDecoder::varintDecode()
     */
    public function varintDecode(string $bytes): array
    {
        return $this->multibaseKeyDecoder->varintDecode($bytes);
    }


    /**
     * Decode a base58 encoded string.
     *
     * @see \SimpleSAML\OpenID\Did\MultibaseKeyDecoder::base58BtcDecode()
     */
    public function base58BtcDecode(string $base58encodedString): string
    {
        return $this->multibaseKeyDecoder->base58BtcDecode($base58encodedString);
    }


    /**
     * Create a JWK for an Ed25519 public key.
     *
     * @param string $rawKeyBytes The raw key bytes
     * @return mixed[] The JWK representation
     *
     * @see \SimpleSAML\OpenID\Did\MultibaseKeyDecoder::createEd25519Jwk()
     */
    public function createEd25519Jwk(string $rawKeyBytes): array
    {
        return $this->multibaseKeyDecoder->createEd25519Jwk($rawKeyBytes);
    }


    /**
     * Create a JWK for an X25519 public key.
     *
     * @param string $rawKeyBytes The raw key bytes
     * @return mixed[] The JWK representation
     *
     * @see \SimpleSAML\OpenID\Did\MultibaseKeyDecoder::createX25519Jwk()
     */
    public function createX25519Jwk(string $rawKeyBytes): array
    {
        return $this->multibaseKeyDecoder->createX25519Jwk($rawKeyBytes);
    }


    /**
     * Create a JWK for a Secp256k1 public key.
     *
     * @param string $rawKeyBytes The raw key bytes
     * @return mixed[] The JWK representation
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     *
     * @see \SimpleSAML\OpenID\Did\MultibaseKeyDecoder::createSecp256k1Jwk()
     */
    public function createSecp256k1Jwk(string $rawKeyBytes): array
    {
        return $this->multibaseKeyDecoder->createSecp256k1Jwk($rawKeyBytes);
    }


    /**
     * Create a JWK for a P-256 (NIST) public key.
     *
     * @param string $rawKeyBytes The raw key bytes
     * @return mixed[] The JWK representation
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     *
     * @see \SimpleSAML\OpenID\Did\MultibaseKeyDecoder::createP256Jwk()
     */
    public function createP256Jwk(string $rawKeyBytes): array
    {
        return $this->multibaseKeyDecoder->createP256Jwk($rawKeyBytes);
    }


    /**
     * Create a JWK for a P-384 (NIST) public key.
     *
     * @param string $rawKeyBytes The raw key bytes
     * @return mixed[] The JWK representation
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     *
     * @see \SimpleSAML\OpenID\Did\MultibaseKeyDecoder::createP384Jwk()
     */
    public function createP384Jwk(string $rawKeyBytes): array
    {
        return $this->multibaseKeyDecoder->createP384Jwk($rawKeyBytes);
    }


    /**
     * Create a JWK for a P-521 (NIST) public key.
     *
     * @return mixed[] The JWK representation
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     *
     * @see \SimpleSAML\OpenID\Did\MultibaseKeyDecoder::createP521Jwk()
     */
    public function createP521Jwk(string $rawKeyBytes): array
    {
        return $this->multibaseKeyDecoder->createP521Jwk($rawKeyBytes);
    }


    /**
     * Create a JWK from raw JSON data.
     *
     * @param string $rawJsonBytes The raw JSON bytes
     * @return mixed[] The JWK representation
     * @throws \SimpleSAML\OpenID\Exceptions\DidException
     *
     * @see \SimpleSAML\OpenID\Did\MultibaseKeyDecoder::createJwkFromRawJson()
     */
    public function createJwkFromRawJson(string $rawJsonBytes): array
    {
        return $this->multibaseKeyDecoder->createJwkFromRawJson($rawJsonBytes);
    }
}
