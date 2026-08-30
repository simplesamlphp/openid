<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Did;

use SimpleSAML\OpenID\Exceptions\DidException;
use SimpleSAML\OpenID\Helpers;

/**
 * Utility class for resolving DID JWK values to JWK format.
 * @see \SimpleSAML\Test\OpenID\Did\DidJwkResolverTest
 */
class DidJwkResolver
{
    /** What every did:jwk value starts with. */
    public const PREFIX = 'did:jwk:';

    /**
     * Longest encoded JWK this will decode.
     *
     * Nothing legitimate comes close: the largest public JWK any supported key type produces is an
     * RSA-4096 one, which is a few hundred base64url characters. The bound exists because the value
     * arrives in a proof key identifier chosen by whoever is being authenticated, and without it a
     * base64url decode and a JSON decode are both run over a string as long as the request allowed.
     * The did:key path is bounded the same way, by MultibaseKeyDecoder::MAX_BASE58_LENGTH.
     */
    public const MAX_ENCODED_JWK_LENGTH = 4096;


    public function __construct(
        protected readonly Helpers $helpers,
    ) {
    }


    /**
     * Extract JWK from a did:jwk value.
     *
     * @param string $didJwk The did:jwk value (e.g., did:jwk:eyJrdHkiOiJPS1AiLCJjcnYiOiJFZDI1NTE5IiwidXNlIjoic2lnIiwieCI6IjExLU9fSjZfSzhfbXUyXzVfSzhfbXUyXzVfSzhfbXUyXzUifQ)
     * @return mixed[] The JWK representation of the key
     * @throws \SimpleSAML\OpenID\Exceptions\DidException If the did:jwk format is invalid or
         unsupported, or the encoded value is longer than any supported key needs
     */
    public function extractJwkFromDidJwk(string $didJwk): array
    {
        // Validate the did:jwk format
        if (!str_starts_with($didJwk, self::PREFIX)) {
            throw new DidException('Invalid did:jwk format. Must start with "did:jwk:"');
        }

        // Extract the base64url-encoded JWK
        $encodedJwk = substr($didJwk, strlen(self::PREFIX));

        // Refused before anything decodes it, since both decodes below allocate in proportion to the
        // length of a value that whoever is being authenticated chose.
        if (strlen($encodedJwk) > self::MAX_ENCODED_JWK_LENGTH) {
            throw new DidException(
                sprintf(
                    'Encoded did:jwk value is longer than the %d characters any supported key needs.',
                    self::MAX_ENCODED_JWK_LENGTH,
                ),
            );
        }

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


    /**
     * Generate a did:jwk value from a JWK array.
     *
     * @param mixed[] $jwk The JWK representation of the key
     * @return string The did:jwk value (e.g., did:jwk:eyJrdHkiOiJPS1AiLCJjcnYiOiJFZDI1NTE5IiwidXNlIjoic2lnIiwieCI6IjExLU9fSjZfSzhfbXUyXzVfSzhfbXUyXzVfSzhfbXUyXzUifQ)
     * @throws \JsonException If the JWK cannot be encoded to JSON
     * @throws \SimpleSAML\OpenID\Exceptions\DidException If the JWK encodes to more than a did:jwk value
         may carry
     */
    public function generateDidJwkFromJwk(array $jwk): string
    {
        $jsonJwk = $this->helpers->json()->encode($jwk);
        $encodedJwk = $this->helpers->base64Url()->encode($jsonJwk);

        // The same bound extraction applies, so this class can never emit an identifier it would then
        // refuse to resolve. Raising the bound instead would weaken a check that exists for values
        // supplied by whoever is being authenticated, in order to carry members - a certificate chain,
        // most of all - that a verification method has no use for.
        if (strlen($encodedJwk) > self::MAX_ENCODED_JWK_LENGTH) {
            throw new DidException(
                sprintf(
                    'The JWK encodes to more than the %d characters a did:jwk value may carry. Remove ' .
                    'members a verification method does not need, such as a certificate chain.',
                    self::MAX_ENCODED_JWK_LENGTH,
                ),
            );
        }

        return self::PREFIX . $encodedJwk;
    }
}
