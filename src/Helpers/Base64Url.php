<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Helpers;

/**
 * Utility class for Base64URL encoding and decoding.
 * Base64URL is a URL and filename safe variant of Base64 encoding.
 * It replaces '+' with '-', '/' with '_' and removes padding '='.
 */
class Base64Url
{
    /**
     * Encode data to Base64URL format.
     *
     * @param string $data The data to encode
     * @return string Base64URL encoded string
     */
    public function encode(string $data): string
    {
        // First encode using standard base64
        $base64 = base64_encode($data);

        // Convert to Base64URL by replacing characters and removing padding
        return rtrim(strtr($base64, '+/', '-_'), '=');
    }

    /**
     * Decode data from Base64URL format.
     *
     * @param string $data The Base64URL encoded string
     * @return string The decoded data
     */
    public function decode(string $data): string
    {
        // Convert from Base64URL to standard Base64
        $base64 = strtr($data, '-_', '+/');

        // Add padding if necessary
        $remainder = strlen($base64) % 4;
        if ($remainder !== 0) {
            $base64 .= str_repeat('=', 4 - $remainder);
        }

        // Decode using standard base64
        $decoded = base64_decode($base64, true);

        if ($decoded === false) {
            throw new \InvalidArgumentException('Invalid Base64URL encoded data');
        }

        return $decoded;
    }
}
