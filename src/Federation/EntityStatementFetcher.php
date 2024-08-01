<?php

declare(strict_types=1);

namespace SimpleSAML\OpenID\Federation;

use DateInterval;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use SimpleSAML\Module\oidc\Codebooks\HttpHeaderValues\ContentTypeEnum;
use SimpleSAML\OpenID\Codebooks\ClaimNamesEnum;
use SimpleSAML\OpenID\Codebooks\EntityTypeEnum;
use SimpleSAML\OpenID\Codebooks\HttpHeadersEnum;
use SimpleSAML\OpenID\Codebooks\HttpMethodsEnum;
use SimpleSAML\OpenID\Codebooks\WellKnownEnum;
use SimpleSAML\OpenID\Exceptions\FetchException;
use SimpleSAML\OpenID\Exceptions\JwsException;
use SimpleSAML\OpenID\Factories\EntityStatementFactory;
use SimpleSAML\OpenID\Helpers;
use Throwable;

class EntityStatementFetcher
{
    public function __construct(
        protected readonly Client $httpClient,
        protected readonly DateInterval $maxCacheDuration = new DateInterval('PT6H'),
        protected readonly ?CacheInterface $cache = null,
        protected readonly ?LoggerInterface $logger = null,
        protected readonly Helpers $helpers = new Helpers(),
        protected readonly EntityStatementFactory $entityStatementFactory = new EntityStatementFactory(),
    ) {
    }

    /**
     * Fetch entity statement from a well-known URI. By default, this will be openid-federation (entity configuration).
     * Fetch will first check if the entity statement is available in cache. If not, it will do a network fetch.
     *
     * @param non-empty-string $entityId
     * @param \SimpleSAML\OpenID\Codebooks\WellKnownEnum $wellKnownEnum
     * @return \SimpleSAML\OpenID\Federation\EntityStatement
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
            compact('entityId', 'wellKnownUri', 'wellKnownEnum'),
        );

        return $this->fromCacheOrNetwork($wellKnownUri);
    }

    /**
     * @param string $subjectId
     * @param \SimpleSAML\OpenID\Federation\EntityStatement $entityConfiguration Entity from which to use the fetch
     * endpoint (issuer).
     * @return \SimpleSAML\OpenID\Federation\EntityStatement
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     */
    public function fromCacheOrFetchEndpoint(
        string $subjectId,
        EntityStatement $entityConfiguration,
    ): EntityStatement
    {
        $entityConfigurationPayload = $entityConfiguration->getPayload();

        $fetchEndpointUri = (string)($entityConfigurationPayload[ClaimNamesEnum::Metadata->value]
            [EntityTypeEnum::FederationEntity->value]
            [ClaimNamesEnum::FederationFetchEndpoint->value] ??
            throw new JwsException('No fetch endpoint found in entity configuration.'));
        $issuer = $entityConfiguration->getIssuer();

        $this->logger?->debug(
            'Entity statement fetch from cache or fetch endpoint.',
            compact('subjectId', 'fetchEndpointUri', 'issuer'),
        );

        return $this->fromCacheOrNetwork(
            $this->helpers->url()->withParams(
                $fetchEndpointUri,
                [
                    ClaimNamesEnum::Subject->value => $subjectId,
                    ClaimNamesEnum::Issuer->value => $issuer,
                ]
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
     * @param string $uri
     * @return \SimpleSAML\OpenID\Federation\EntityStatement|null
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromCache(string $uri): ?EntityStatement
    {
        $cacheKey = $this->helpers->cache()->keyFor($uri);

        try {
            /** @var ?string $jws */
            $jws = $this->cache?->get($cacheKey);
        } catch (Throwable $exception) {
            $this->logger?->error(
                'Error trying to get entity statement from cache: ' . $exception->getMessage(),
                compact('uri', 'cacheKey'),
            );
            return null;
        }

        return is_string($jws) ? $this->prepareEntityStatement($jws) : null;
    }

    /**
     * Fetch entity statement from network. Each successful fetch will be cached, with URI being used as a cache key.
     *
     * @throws \SimpleSAML\OpenID\Exceptions\FetchException
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    public function fromNetwork(string $uri): EntityStatement
    {
        try {
            $response = $this->httpClient->request(HttpMethodsEnum::GET->value, $uri);
        } catch (Throwable $e) {
            $message = sprintf(
                'Error sending HTTP request to %s. Error was: %s',
                $uri,
                $e->getMessage(),
            );
            $this->logger?->error($message);
            throw new FetchException($message, (int)$e->getCode(), $e);
        }

        if ($response->getStatusCode() !== 200) {
            $message = sprintf(
                'Unexpected HTTP response for entity statement fetch, status code: %s, reason: %s.',
                $response->getStatusCode(),
                $response->getReasonPhrase(),
            );
            $this->logger?->error($message);
            throw new FetchException($message);
        }

        /** @psalm-suppress InvalidLiteralArgument */
        if (
            !str_contains(
                ContentTypeEnum::ApplicationEntityStatementJwt->value,
                $response->getHeaderLine(HttpHeadersEnum::ContentType->value),
            )
        ) {
            $message = sprintf(
                'Unexpected content type in response for entity statement fetch: %s, expected: %s.',
                $response->getHeaderLine(HttpHeadersEnum::ContentType->value),
                ContentTypeEnum::ApplicationEntityStatementJwt->value,
            );
            $this->logger?->error($message);
            throw new FetchException($message);
        }

        $jws = $response->getBody()->getContents();
        $this->logger?->info('Successful HTTP response for entity statement fetch.', compact('uri', 'jws'));

        $entityStatement = $this->entityStatementFactory->fromToken($jws);

        // Cache it
        $expiration = (int)($entityStatement->getPayloadClaim(ClaimNamesEnum::ExpirationTime->value) ?? 0);
        $duration = $this->helpers->cache()->maxDuration($this->maxCacheDuration, $expiration);
        $cacheKey = $this->helpers->cache()->keyFor($uri);
        try {
            $this->cache?->set($cacheKey, $jws, $duration);
        } catch (Throwable $exception) {
            $this->logger?->error(
                'Error setting entity statement to cache: ' . $exception->getMessage(),
                compact('uri', 'cacheKey'),
            );
        }

        return $entityStatement;
    }

    /**
     * @throws \SimpleSAML\OpenID\Exceptions\JwsException
     */
    protected function prepareEntityStatement(string $jws): EntityStatement
    {
        // TODO mivanci Important Validate header, iat, exp.
        return $this->entityStatementFactory->fromToken($jws);
    }
}
