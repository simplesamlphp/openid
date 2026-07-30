<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\TokenStatusList;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Exceptions\OpenIdException;
use SimpleSAML\OpenID\Exceptions\StatusListTokenException;

/**
 * Relying Party side resolution of the status of a Referenced Token.
 *
 * https://datatracker.ietf.org/doc/html/draft-ietf-oauth-status-list#name-validation-rules
 *
 * Note that the validation of the Referenced Token itself comes first and is not this class's concern: if the
 * Referenced Token is invalid on its own terms, the specification requires that its status is not resolved at all.
 * Only once that has passed does the reference it carries get handed here.
 *
 * Key resolution is likewise the caller's: the specification mandates no method for binding a Status List Token to
 * a key, so the key set to verify against is a parameter rather than something resolved here.
 *
 * @see \SimpleSAML\Test\OpenID\TokenStatusList\StatusResolverTest
 */
class StatusResolver
{
    public function __construct(
        protected readonly StatusListTokenFetcher $statusListTokenFetcher,
        protected readonly ?LoggerInterface $logger = null,
    ) {
    }


    /**
     * Fetches the Status List Token the reference points at and resolves the status of the Referenced Token.
     *
     * @param mixed[] $jwks Key set the Status List Token is verified against.
     * @param int $maxDecompressedBytes Largest Status List the caller is willing to decompress. See
     * StatusListFactory::fromEncoded().
     * @param ?float $deadlineTimestamp Point in time the network fetch must not run past, if one is reached for.
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function resolve(
        StatusReference $statusReference,
        array $jwks,
        int $maxDecompressedBytes,
        ?int $maxCompressedBytes = null,
        ?float $deadlineTimestamp = null,
    ): StatusResult {
        $this->logger?->debug(
            'Resolving Referenced Token status.',
            ['uri' => $statusReference->getUri(), 'idx' => $statusReference->getIdx()],
        );

        try {
            $cachedStatusListToken = $this->statusListTokenFetcher->fromCache($statusReference->getUri());

            if ($cachedStatusListToken instanceof StatusListToken) {
                return $this->resolveWithToken(
                    $statusReference,
                    $cachedStatusListToken,
                    $jwks,
                    $maxDecompressedBytes,
                    $maxCompressedBytes,
                );
            }
        } catch (OpenIdException $openIdException) {
            // A cached entry that cannot be read, or that no longer resolves, is worth no more than an empty
            // cache, so it is treated as a miss and the current representation is fetched once. Failing outright
            // instead would let a key rotation at the Status Provider make every Referenced Token pointing at
            // this URI unresolvable until the entry expired, which is up to the configured maximum cache
            // duration after the provider itself was healthy again. The read is inside the attempt for the same
            // reason: whatever is in the cache under this URI failing to parse is no more of an answer about the
            // Referenced Token than a stale token is.
            $this->logger?->warning(
                'Cached Status List Token was unusable, discarding it and fetching a current one.',
                ['uri' => $statusReference->getUri(), 'error' => $openIdException->getMessage()],
            );
        }

        $statusListToken = $this->statusListTokenFetcher->fromNetworkWithoutCaching(
            $statusReference->getUri(),
            $deadlineTimestamp,
        );

        $statusResult = $this->resolveWithToken(
            $statusReference,
            $statusListToken,
            $jwks,
            $maxDecompressedBytes,
            $maxCompressedBytes,
        );

        // Cached only now, once the token has been verified and bound to the URI it was fetched from. Caching on
        // arrival would let one bad response from a Status Provider keep being served back for the whole cache
        // period, rejecting every Referenced Token that points at it long after the provider itself recovered.
        // This also replaces whatever unusable entry the fetch above was reached for.
        $this->statusListTokenFetcher->cacheIt($statusListToken, $statusReference->getUri());

        return $statusResult;
    }


    /**
     * Resolves the status of a Referenced Token against a Status List Token the caller already holds, which is
     * what an offline flow or a locally cached token needs.
     *
     * @param mixed[] $jwks Key set the Status List Token is verified against.
     * @param ?\DateTimeImmutable $resolvedAt Time the Status List Token was obtained, defaulting to now. It is
     * what StatusResult::isFreshAt() measures the `ttl` window from, so a caller resolving against a token it has
     * been holding should pass the time it actually obtained that token rather than leaving this to default;
     * otherwise the window restarts and the result can be believed fresh for longer than the issuer allowed.
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\StatusListException
     * @throws \SimpleSAML\OpenID\Exceptions\InvalidValueException
     */
    public function resolveWithToken(
        StatusReference $statusReference,
        StatusListToken $statusListToken,
        array $jwks,
        int $maxDecompressedBytes,
        ?int $maxCompressedBytes = null,
        ?DateTimeImmutable $resolvedAt = null,
    ): StatusResult {
        // The signature comes before anything is read out of the token.
        $statusListToken->verifyWithKeySet($jwks);

        // Expiration is checked again here, not only when the token was parsed. A token handed to this method may
        // have been parsed long ago -- an offline flow, or one held across requests -- and an expired Status List
        // must not be what decides a Referenced Token's status. The getter compares against the time of the call,
        // so this throws for a token that has expired since.
        $statusListToken->getExpirationTime();

        $subject = $statusListToken->getSubject();

        // The subject must be the very URI the Referenced Token pointed at. Compared as given, since a token
        // served from a URI other than the one the credential names is exactly what this check is for.
        if ($subject !== $statusReference->getUri()) {
            $message = 'Status List Token subject does not match the URI of the Referenced Token.';
            $this->logger?->error(
                $message,
                ['subject' => $subject, 'uri' => $statusReference->getUri()],
            );

            throw new StatusListTokenException(
                sprintf(
                    '%s Subject was %s, expected %s.',
                    $message,
                    var_export($subject, true),
                    var_export($statusReference->getUri(), true),
                ),
            );
        }

        $status = $statusListToken->getStatusList($maxDecompressedBytes, $maxCompressedBytes)
            ->get($statusReference->getIdx());

        $this->logger?->debug(
            'Resolved Referenced Token status.',
            ['uri' => $statusReference->getUri(), 'idx' => $statusReference->getIdx(), 'status' => $status],
        );

        return new StatusResult(
            $status,
            $statusReference,
            $statusListToken,
            $resolvedAt ?? new DateTimeImmutable(),
        );
    }
}
