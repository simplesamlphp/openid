<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Did;

use SimpleSAML\OpenID\Exceptions\DidException;
use SimpleSAML\OpenID\Helpers;

/**
 * Utility class for resolving DID JWK values to JWK format.
 */
class DidJwkResolver
{
    public function __construct(
        protected readonly Helpers $helpers,
    ) {
    }


    /**
     * Extract JWK from a did:jwk value.
     *
     * @param string $didJwk The did:jwk value (e.g., did:jwk:eyJrdHkiOiJPS1AiLCJjcnYiOiJFZDI1NTE5IiwidXNlIjoic2lnIiwieCI6IjExLU9fSjZfSzhfbXUyXzVfSzhfbXUyXzVfSzhfbXUyXzUifQ)
     * @return mixed[] The JWK representation of the key
     * @throws \SimpleSAML\OpenID\Exceptions\DidException If the did:jwk format is invalid or unsupported
     */
    public function extractJwkFromDidJwk(string $didJwk): array
    {
        // Validate the did:jwk format
        if (!str_starts_with($didJwk, 'did:jwk:')) {
            throw new DidException('Invalid did:jwk format. Must start with "did:jwk:"');
        }

        // Extract the base64url-encoded JWK
        $encodedJwk = substr($didJwk, 8); // Remove 'did:jwk:'

        try {
            // Decode the base64url encoded string
            $jsonJwk = $this->helpers->base64Url()->decode($encodedJwk);

            // Parse the JSON JWK
            $jwk = $this->helpers->json()->decode($jsonJwk);

            if (!is_array($jwk)) {
                throw new DidException('Decoded did:jwk is not a valid JSON object.');
            }

            return $jwk;
        } catch (\Exception $exception) {
            throw new DidException('Error processing did:jwk: ' . $exception->getMessage(), 0, $exception);
        }
    }
}
