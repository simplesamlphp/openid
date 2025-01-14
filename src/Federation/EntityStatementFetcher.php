<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use Psr\Log\LoggerInterface;
use SimpleSAML\OpenID\Codebooks\ClaimsEnum;
use SimpleSAML\OpenID\Codebooks\ContentTypesEnum;
use SimpleSAML\OpenID\Codebooks\WellKnownEnum;
use SimpleSAML\OpenID\Decorators\DateIntervalDecorator;
use SimpleSAML\OpenID\Exceptions\EntityStatementException;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\Federation\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Helpers;
use SimpleSAML\OpenID\Jws\JwsFetcher;
use SimpleSAML\OpenID\Utils\ArtifactFetcher;

class EntityStatementFetcher extends JwsFetcher
{
    public function __construct(
        private readonly EntityStatementFactory $parsedJwsFactory,
        ArtifactFetcher $artifactFetcher,
        DateIntervalDecorator $maxCacheDuration,
        Helpers $helpers,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($parsedJwsFactory, $artifactFetcher, $maxCacheDuration, $helpers, $logger);
    }

    protected function buildJwsInstance(string $token): EntityStatement
    {
        return $this->parsedJwsFactory->fromToken($token);
    }

    public function getExpectedContentTypeHttpHeader(): string
    {
        return ContentTypesEnum::ApplicationEntityStatementJwt->value;
    }

    /**
     * Fetch entity statement from a well-known URI. By default, this will be openid-federation (entity configuration).
     * Fetch will first check if the entity statement is available in cache. If not, it will do a network fetch.
     *
     * @param non-empty-string $entityId
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromCacheOrWellKnownEndpoint(
        string $entityId,
        WellKnownEnum $wellKnownEnum = WellKnownEnum::OpenIdFederation,
    ): EntityStatement {
        $wellKnownUri = $wellKnownEnum->uriFor($entityId);
        $this->logger?->debug(
            'Entity statement fetch from cache or well-known endpoint.',
            ['entityId' => $entityId, 'wellKnownUri' => $wellKnownUri, 'wellKnownEnum' => $wellKnownEnum],
        );

        return $this->fromCacheOrNetwork($wellKnownUri);
    }

    /**
     * @param \SimpleSAML\OpenID\Federation\EntityStatement $entityConfiguration Entity from which to use the fetch
     * endpoint (issuer).
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromCacheOrFetchEndpoint(
        string $subjectId,
        EntityStatement $entityConfiguration,
    ): EntityStatement {
        $fetchEndpointUri = $entityConfiguration->getFederationFetchEndpoint() ??
        throw new EntityStatementException('No fetch endpoint found in entity configuration.');

        $this->logger?->debug(
            'Entity statement fetch from cache or fetch endpoint.',
            ['subjectId' => $subjectId, 'fetchEndpointUri' => $fetchEndpointUri],
        );

        return $this->fromCacheOrNetwork(
            $this->helpers->url()->withParams(
                $fetchEndpointUri,
                [
                    ClaimsEnum::Sub->value => $subjectId,
                ],
            ),
        );
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromCacheOrNetwork(string $uri): EntityStatement
    {
        return $this->fromCache($uri) ?? $this->fromNetwork($uri);
    }

    /**
     * Fetch entity statement from cache, if available. URI is used as cache key.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromCache(string $uri): ?EntityStatement
    {
        $entityStatement = parent::fromCache($uri);

        if (is_null($entityStatement)) {
            return null;
        }

        if ($entityStatement instanceof \SimpleSAML\OpenID\Federation\EntityStatement) {
            return $entityStatement;
        }

        // @codeCoverageIgnoreStart
        $message = 'Unexpected entity statement instance encountered for cache fetch.';
        $this->logger?->error(
            $message,
            ['uri' => $uri, 'entityStatement' => $entityStatement],
        );

        throw new FetchException($message);
        // @codeCoverageIgnoreEnd
    }

    /**
     * Fetch entity statement from network. Each successful fetch will be cached, with URI being used as a cache key.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromNetwork(string $uri): EntityStatement
    {
        $entityStatement = parent::fromNetwork($uri);

        if ($entityStatement instanceof \SimpleSAML\OpenID\Federation\EntityStatement) {
            return $entityStatement;
        }

        // @codeCoverageIgnoreStart
        $message = 'Unexpected entity statement instance encountered for network fetch.';
        $this->logger?->error(
            $message,
            ['uri' => $uri, 'entityStatement' => $entityStatement],
        );

        throw new FetchException($message);
        // @codeCoverageIgnoreEnd
    }
}
