<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\ContentTypesEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\EntityStatementException;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\Federation\Factories\TrustMarkFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jws\JwsFetcher;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

class TrustMarkFetcher extends JwsFetcher
{
    public function __construct(
        private readonly TrustMarkFactory $parsedJwsFactory,
        ArtifactFetcher $artifactFetcher,
        DateIntervalDecorator $maxCacheDuration,
        Helpers $helpers,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($parsedJwsFactory, $artifactFetcher, $maxCacheDuration, $helpers, $logger);
    }

    protected function buildJwsInstance(string $token): TrustMark
    {
        return $this->parsedJwsFactory->fromToken($token);
    }

    public function getExpectedContentTypeHttpHeader(): string
    {
        return ContentTypesEnum::ApplicationTrustMarkJwt->value;
    }

    /**
     * @param \SimpleSAML\OpenID\Federation\EntityStatement $entityConfiguration Entity from which to use the
     * federation_trust_mark_endpoint (issuer).
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     * @throws \SimpleSAML\OpenID\Exceptions\OpenIdException
     */
    public function fromCacheOrFederationTrustMarkEndpoint(
        string $trustMarkId,
        string $subjectId,
        EntityStatement $entityConfiguration,
    ): TrustMark {
        $trustMarkEndpoint = $entityConfiguration->getFederationTrustMarkEndpoint() ??
        throw new EntityStatementException('No federation trust mark endpoint found in entity configuration.');

        $this->logger?->debug(
            'Trust Mark fetch from cache or federation trust mark endpoint.',
            ['trustMarkId' => $trustMarkId, 'subjectId' => $subjectId, 'trustMarkEndpoint' => $trustMarkEndpoint],
        );

        return $this->fromCacheOrNetwork(
            $this->helpers->url()->withParams(
                $trustMarkEndpoint,
                [
                    ClaimsEnum::TrustMarkId->value => $trustMarkId,
                    ClaimsEnum::Sub->value => $subjectId,
                ],
            ),
        );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromCacheOrNetwork(string $uri): TrustMark
    {
        return $this->fromCache($uri) ?? $this->fromNetwork($uri);
    }

    /**
     * Fetch Trust Mark from cache, if available. URI is used as cache key.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromCache(string $uri): ?TrustMark
    {
        $trustMark = parent::fromCache($uri);

        if (is_null($trustMark)) {
            return null;
        }

        if ($trustMark instanceof TrustMark) {
            return $trustMark;
        }

        // @codeCoverageIgnoreStart
        $message = 'Unexpected Trust Mark instance encountered for cache fetch.';
        $this->logger?->error(
            $message,
            ['uri' => $uri, 'trustMark' => $trustMark],
        );

        throw new FetchException($message);
        // @codeCoverageIgnoreEnd
    }

    /**
     * Fetch Trust Mark from network. Each successful fetch will be cached, with URI being used as a cache key.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromNetwork(string $uri): TrustMark
    {
        $trustMark = parent::fromNetwork($uri);

        if ($trustMark instanceof TrustMark) {
            return $trustMark;
        }

        // @codeCoverageIgnoreStart
        $message = 'Unexpected Trust Mark instance encountered for network fetch.';
        $this->logger?->error(
            $message,
            ['uri' => $uri, 'trustMark' => $trustMark],
        );

        throw new FetchException($message);
        // @codeCoverageIgnoreEnd
    }
}
